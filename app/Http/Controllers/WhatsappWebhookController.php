<?php

namespace App\Http\Controllers;

use App\Models\SendWhatsappCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WhatsappWebhookController extends Controller
{
    /**
     * WhatsApp webhook handler.
     *
     * GET  = Meta webhook verification.
     * POST = Receive WhatsApp delivery status updates and invitee replies.
     */
    public function handleWebhook(Request $request)
    {
        if ($request->isMethod('GET')) {
            return $this->verifyWebhook($request);
        }

        if ($request->isMethod('POST')) {
            return $this->processWebhook($request);
        }

        return response('Method not allowed', 405);
    }

    /**
     * Verify webhook when setting callback URL in Meta Developer Dashboard.
     */
    private function verifyWebhook(Request $request)
    {
        $verifyToken = env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'my-verify-token');

        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        if ($mode === 'subscribe' && hash_equals((string) $verifyToken, (string) $token)) {
            Log::info('WhatsApp webhook verified successfully.');
            return response($challenge, 200);
        }

        Log::warning('WhatsApp webhook verification failed.', [
            'mode' => $mode,
            'token_received' => $token,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Process incoming WhatsApp webhook POST data.
     */
    private function processWebhook(Request $request)
    {
        $data = $request->all();

        Log::info('WhatsApp webhook received.', [
            'payload' => $data,
        ]);

        $entries = $data['entry'] ?? [];

        if (empty($entries) || ! is_array($entries)) {
            Log::warning('WhatsApp webhook received without entry.', [
                'payload' => $data,
            ]);

            // Meta expects 200 for received webhooks. Returning 400 can cause repeated retries.
            return response('Webhook received without entry', 200);
        }

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $value = $change['value'] ?? [];

                foreach (($value['statuses'] ?? []) as $statusData) {
                    $this->handleStatusUpdate($statusData);
                }

                foreach (($value['messages'] ?? []) as $messageData) {
                    $this->handleIncomingMessage($messageData);
                }
            }
        }

        return response('Webhook processed', 200);
    }

    /**
     * Save WhatsApp delivery status updates.
     */
    private function handleStatusUpdate(array $statusData): void
    {
        $messageId = $statusData['id'] ?? null;
        $status = strtolower((string) ($statusData['status'] ?? 'pending'));
        $recipientId = $this->normalizePhone($statusData['recipient_id'] ?? null);
        $timestamp = $this->formatWhatsappTimestamp($statusData['timestamp'] ?? null);
        $error = $this->extractErrorDetails($statusData);

        Log::info('WhatsApp message status update.', [
            'message_id' => $messageId,
            'status' => $status,
            'recipient_id' => $recipientId,
            'timestamp' => $timestamp,
            'error_code' => $error['code'],
            'error_message' => $error['message'],
        ]);

        if (! $messageId) {
            Log::warning('WhatsApp webhook status missing message ID.', [
                'status_data' => $statusData,
            ]);

            return;
        }

        try {
            if (! Schema::hasTable('send_whatsapp_cards')) {
                Log::warning('send_whatsapp_cards table not found while processing WhatsApp webhook.');
                return;
            }

            $record = $this->findWhatsappRecordByMessageId($messageId);

            if (! $record && $recipientId) {
                // Last fallback for older records where message_id was not saved correctly.
                $record = $this->findLatestWhatsappRecordByPhone($recipientId);
            }

            if (! $record) {
                Log::warning('No SendWhatsappCard record found for webhook message ID.', [
                    'message_id' => $messageId,
                    'status' => $status,
                    'recipient_id' => $recipientId,
                ]);

                return;
            }

            $this->setColumn($record, 'message_id', $messageId);
            $this->setColumn($record, 'whatsapp_message_id', $messageId);
            $this->setColumn($record, 'delivery_status', $status ?: 'pending');
            $this->setColumn($record, 'status', $status ?: 'pending');
            $this->setColumn($record, 'delivery_status_time', $timestamp);
            $this->setColumn($record, 'status_updated_at', $timestamp);

            if ($status === 'failed') {
                $this->setColumn($record, 'sent_status', 'failed');
                $this->setColumn($record, 'error_code', $error['code']);
                $this->setColumn($record, 'error_message', $error['message']);
                $this->setColumn($record, 'failure_reason', $error['message']);

                // Only use reply_message for error if your current table has no dedicated error column.
                if (! Schema::hasColumn('send_whatsapp_cards', 'error_message')) {
                    $this->setColumn($record, 'reply_message', $error['message']);
                }
            } else {
                if (in_array($status, ['sent', 'delivered', 'read'], true)) {
                    $this->setColumn($record, 'sent_status', 'success');
                }
            }

            $record->save();

            Log::info('SendWhatsappCard webhook status updated successfully.', [
                'send_whatsapp_card_id' => $record->id,
                'message_id' => $messageId,
                'status' => $status,
                'error_code' => $error['code'],
                'error_message' => $error['message'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to update WhatsApp status from webhook.', [
                'message_id' => $messageId,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log and save incoming WhatsApp replies from invitees.
     */
    private function handleIncomingMessage(array $messageData): void
    {
        $from = $this->normalizePhone($messageData['from'] ?? null);
        $messageId = $messageData['id'] ?? null;
        $timestamp = $this->formatWhatsappTimestamp($messageData['timestamp'] ?? null);
        $text = $this->extractIncomingMessageText($messageData);

        Log::info('Incoming WhatsApp message.', [
            'from' => $from,
            'message_id' => $messageId,
            'text' => $text,
            'timestamp' => $timestamp,
        ]);

        if (! $from || ! $text) {
            return;
        }

        try {
            if (! Schema::hasTable('send_whatsapp_cards')) {
                Log::warning('send_whatsapp_cards table not found while saving WhatsApp reply.');
                return;
            }

            $record = $this->findLatestWhatsappRecordByPhone($from);

            if (! $record) {
                Log::warning('No SendWhatsappCard record found for incoming reply phone.', [
                    'from' => $from,
                    'message_id' => $messageId,
                    'text' => $text,
                ]);

                return;
            }

            // reply_message should contain only invitee reply, not delivery failure error.
            $this->setColumn($record, 'reply_message', $text);
            $this->setColumn($record, 'reply_status', 'received');
            $this->setColumn($record, 'reply_message_id', $messageId);
            $this->setColumn($record, 'reply_received_at', $timestamp);
            $this->setColumn($record, 'delivery_status_time', $timestamp);

            $rsvpStatus = $this->detectRsvpStatus($text);
            if ($rsvpStatus) {
                $this->setColumn($record, 'rsvp_status', $rsvpStatus);
            }

            $record->save();

            Log::info('Incoming WhatsApp reply saved successfully.', [
                'send_whatsapp_card_id' => $record->id,
                'from' => $from,
                'reply' => $text,
                'rsvp_status' => $rsvpStatus,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to save incoming WhatsApp reply.', [
                'from' => $from,
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Find record using WhatsApp message ID.
     */
    private function findWhatsappRecordByMessageId(string $messageId): ?SendWhatsappCard
    {
        $query = SendWhatsappCard::query();

        if (Schema::hasColumn('send_whatsapp_cards', 'message_id')) {
            $record = (clone $query)->where('message_id', $messageId)->first();
            if ($record) {
                return $record;
            }
        }

        if (Schema::hasColumn('send_whatsapp_cards', 'whatsapp_message_id')) {
            $record = (clone $query)->where('whatsapp_message_id', $messageId)->first();
            if ($record) {
                return $record;
            }
        }

        return null;
    }

    /**
     * Match latest sent WhatsApp invitation by invitee phone number.
     */
    private function findLatestWhatsappRecordByPhone(string $phone): ?SendWhatsappCard
    {
        $normalized = $this->normalizePhone($phone);

        if (! $normalized) {
            return null;
        }

        $phoneVariants = array_values(array_unique([
            $normalized,
            '+' . $normalized,
            '0' . substr($normalized, -9),
        ]));

        $query = SendWhatsappCard::query();

        $query->where(function ($q) use ($phoneVariants) {
            foreach (['whatsapp_sender_id', 'phone', 'mobile_number', 'recipient_id', 'to'] as $column) {
                if (Schema::hasColumn('send_whatsapp_cards', $column)) {
                    $q->orWhereIn($column, $phoneVariants);
                }
            }
        });

        return $query->latest()->first();
    }

    /**
     * Extract readable WhatsApp error details.
     */
    private function extractErrorDetails(array $statusData): array
    {
        if (empty($statusData['errors'][0])) {
            return [
                'code' => null,
                'message' => null,
            ];
        }

        $error = $statusData['errors'][0];

        $code = isset($error['code']) ? (string) $error['code'] : null;

        $message = trim(implode(' ', array_filter([
            $error['title'] ?? null,
            $error['message'] ?? null,
            $error['error_data']['details'] ?? null,
        ])));

        if ($code && $message) {
            $message = trim($code . ' ' . $message);
        } elseif ($code) {
            $message = $code;
        }

        return [
            'code' => $code,
            'message' => $message ?: null,
        ];
    }

    /**
     * Extract incoming text from different WhatsApp message types.
     */
    private function extractIncomingMessageText(array $messageData): ?string
    {
        $type = $messageData['type'] ?? null;

        if ($type === 'text') {
            return trim((string) ($messageData['text']['body'] ?? '')) ?: null;
        }

        if ($type === 'button') {
            return trim((string) ($messageData['button']['text'] ?? $messageData['button']['payload'] ?? '')) ?: null;
        }

        if ($type === 'interactive') {
            $interactive = $messageData['interactive'] ?? [];

            return trim((string) (
                $interactive['button_reply']['title']
                ?? $interactive['button_reply']['id']
                ?? $interactive['list_reply']['title']
                ?? $interactive['list_reply']['id']
                ?? ''
            )) ?: null;
        }

        return null;
    }

    /**
     * Detect RSVP status from common Swahili/English replies.
     */
    private function detectRsvpStatus(string $reply): ?string
    {
        $reply = Str::lower(trim($reply));

        $attendingWords = [
            'yes', 'y', 'ok', 'okay', 'sawa', 'nakuja', 'nitakuja', 'nitakuwepo',
            'tutakuja', 'tutakuwepo', 'attending', 'confirm', 'confirmed', 'ipo',
        ];

        $notAttendingWords = [
            'no', 'n', 'hapana', 'sitakuja', 'sitaweza', 'not attending',
            'siwezi', 'cancel', 'decline', 'declined',
        ];

        foreach ($attendingWords as $word) {
            if ($reply === $word || str_contains($reply, $word)) {
                return 'attending';
            }
        }

        foreach ($notAttendingWords as $word) {
            if ($reply === $word || str_contains($reply, $word)) {
                return 'not_attending';
            }
        }

        return 'reply_received';
    }

    /**
     * Safely set column only if it exists in current database.
     */
    private function setColumn(SendWhatsappCard $record, string $column, mixed $value): void
    {
        if (Schema::hasColumn('send_whatsapp_cards', $column)) {
            $record->{$column} = $value;
        }
    }

    /**
     * Normalize phone to digits only. Example: +255670461644 => 255670461644.
     */
    private function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $phone = preg_replace('/\D+/', '', $phone);

        return $phone ?: null;
    }

    /**
     * Convert WhatsApp unix timestamp to database datetime string.
     */
    private function formatWhatsappTimestamp(mixed $timestamp): string
    {
        if ($timestamp) {
            return date('Y-m-d H:i:s', (int) $timestamp);
        }

        return now()->format('Y-m-d H:i:s');
    }
}
