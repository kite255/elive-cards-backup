<?php

namespace App\Http\Controllers;

use App\Models\SendWhatsappCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WhatsappWebhookController extends Controller
{
    /**
     * WhatsApp webhook handler.
     *
     * GET  = Meta webhook verification.
     * POST = Receive WhatsApp delivery status updates and replies.
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

        if ($mode === 'subscribe' && $token === $verifyToken) {
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

        if (empty($entries)) {
            Log::warning('WhatsApp webhook received without entry.', [
                'payload' => $data,
            ]);

            return response('Invalid Request', 400);
        }

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $value = $change['value'] ?? [];

                $statuses = $value['statuses'] ?? [];

                foreach ($statuses as $statusData) {
                    $this->handleStatusUpdate($statusData);
                }

                $messages = $value['messages'] ?? [];

                foreach ($messages as $messageData) {
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
        $status = $statusData['status'] ?? null;
        $recipientId = $statusData['recipient_id'] ?? null;

        $timestamp = isset($statusData['timestamp'])
            ? date('Y-m-d H:i:s', (int) $statusData['timestamp'])
            : now()->format('Y-m-d H:i:s');

        $errorMessage = $this->extractErrorMessage($statusData);

        Log::info('WhatsApp message status update.', [
            'message_id' => $messageId,
            'status' => $status,
            'recipient_id' => $recipientId,
            'timestamp' => $timestamp,
            'error' => $errorMessage,
        ]);

        if (! $messageId) {
            Log::warning('WhatsApp webhook status missing message ID.', [
                'status_data' => $statusData,
            ]);

            return;
        }

        try {
            if (! Schema::hasTable('send_whatsapp_cards')) {
                return;
            }

            /*
             * IMPORTANT:
             * WhatsApp webhook "id" is the WhatsApp message ID.
             * In your table, it is stored in send_whatsapp_cards.message_id.
             */
            $record = SendWhatsappCard::where('message_id', $messageId)->first();

            /*
             * Fallback: if older records accidentally stored message ID in another column.
             */
            if (! $record && Schema::hasColumn('send_whatsapp_cards', 'whatsapp_message_id')) {
                $record = SendWhatsappCard::where('whatsapp_message_id', $messageId)->first();
            }

            if (! $record) {
                Log::warning('No SendWhatsappCard record found for webhook message ID.', [
                    'message_id' => $messageId,
                    'status' => $status,
                    'recipient_id' => $recipientId,
                ]);

                return;
            }

            if (Schema::hasColumn('send_whatsapp_cards', 'delivery_status')) {
                $record->delivery_status = $status;
            }

            if (Schema::hasColumn('send_whatsapp_cards', 'delivery_status_time')) {
                $record->delivery_status_time = $timestamp;
            }

            /*
             * Keep sent_status as accepted after API accepted it.
             * But if webhook says failed, mark it failed.
             */
            if ($status === 'failed' && Schema::hasColumn('send_whatsapp_cards', 'sent_status')) {
                $record->sent_status = 'failed';
            }

            /*
             * Store failed reason in available column.
             * Your table has reply_message, so we use it if error_message does not exist.
             */
            if ($errorMessage) {
                if (Schema::hasColumn('send_whatsapp_cards', 'error_message')) {
                    $record->error_message = $errorMessage;
                } elseif (Schema::hasColumn('send_whatsapp_cards', 'reply_message')) {
                    $record->reply_message = $errorMessage;
                }
            }

            $record->save();

            Log::info('SendWhatsappCard webhook status updated successfully.', [
                'send_whatsapp_card_id' => $record->id,
                'message_id' => $messageId,
                'status' => $status,
                'error' => $errorMessage,
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
     * Log and save incoming WhatsApp replies.
     */
    private function handleIncomingMessage(array $messageData): void
    {
        $from = $messageData['from'] ?? null;
        $messageId = $messageData['id'] ?? null;

        $timestamp = isset($messageData['timestamp'])
            ? date('Y-m-d H:i:s', (int) $messageData['timestamp'])
            : now()->format('Y-m-d H:i:s');

        $text = $messageData['text']['body'] ?? null;

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
                return;
            }

            /*
             * Match reply by recipient phone number.
             * whatsapp_sender_id usually stores the receiver phone number, e.g. 255670461644.
             */
            $record = SendWhatsappCard::where('whatsapp_sender_id', $from)
                ->latest()
                ->first();

            if (! $record) {
                Log::warning('No SendWhatsappCard record found for incoming reply phone.', [
                    'from' => $from,
                    'message_id' => $messageId,
                    'text' => $text,
                ]);

                return;
            }

            if (Schema::hasColumn('send_whatsapp_cards', 'reply_message')) {
                $record->reply_message = $text;
            }

            if (Schema::hasColumn('send_whatsapp_cards', 'delivery_status_time')) {
                $record->delivery_status_time = $timestamp;
            }

            $record->save();

            Log::info('Incoming WhatsApp reply saved successfully.', [
                'send_whatsapp_card_id' => $record->id,
                'from' => $from,
                'reply' => $text,
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
     * Extract readable WhatsApp error message.
     */
    private function extractErrorMessage(array $statusData): ?string
    {
        if (empty($statusData['errors'][0])) {
            return null;
        }

        $error = $statusData['errors'][0];

        return trim(
            ($error['code'] ?? '') . ' ' .
            ($error['title'] ?? '') . ' ' .
            ($error['message'] ?? '')
        ) ?: null;
    }
}