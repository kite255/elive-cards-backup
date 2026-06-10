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
     * POST = Receive WhatsApp delivery status updates.
     */
    public function handleWebhook(Request $request)
    {
        /**
         * 1. Webhook verification from Meta
         */
        if ($request->isMethod('GET')) {
            return $this->verifyWebhook($request);
        }

        /**
         * 2. Webhook status/messages from WhatsApp
         */
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

                /**
                 * Handle message delivery statuses:
                 * sent, delivered, read, failed
                 */
                $statuses = $value['statuses'] ?? [];

                foreach ($statuses as $statusData) {
                    $this->handleStatusUpdate($statusData);
                }

                /**
                 * Handle incoming messages/replies from invitees.
                 */
                $messages = $value['messages'] ?? [];

                foreach ($messages as $messageData) {
                    $this->handleIncomingMessage($messageData);
                }
            }
        }

        return response('Webhook processed', 200);
    }

    /**
     * Save/log WhatsApp status updates.
     */
    private function handleStatusUpdate(array $statusData): void
    {
        $messageId = $statusData['id'] ?? null;
        $status = $statusData['status'] ?? null;
        $recipientId = $statusData['recipient_id'] ?? null;
        $timestamp = isset($statusData['timestamp'])
            ? date('Y-m-d H:i:s', (int) $statusData['timestamp'])
            : now()->format('Y-m-d H:i:s');

        $errorMessage = null;

        if (! empty($statusData['errors'][0])) {
            $error = $statusData['errors'][0];

            $errorMessage = trim(
                ($error['code'] ?? '') . ' ' .
                ($error['title'] ?? '') . ' ' .
                ($error['message'] ?? '')
            );
        }

        Log::info('WhatsApp message status update.', [
            'message_id' => $messageId,
            'status' => $status,
            'recipient_id' => $recipientId,
            'timestamp' => $timestamp,
            'error' => $errorMessage,
        ]);

        /**
         * Optional database update.
         * This tries to update send_whatsapp_cards if your table has matching columns.
         */
        try {
            if (! class_exists(SendWhatsappCard::class)) {
                return;
            }

            if (! Schema::hasTable('send_whatsapp_cards')) {
                return;
            }

            $query = SendWhatsappCard::query();

            /**
             * Match by WhatsApp message ID.
             * Your old code used whatsapp_sender_id to store the WhatsApp message ID.
             */
            if (Schema::hasColumn('send_whatsapp_cards', 'whatsapp_sender_id')) {
                $query->where('whatsapp_sender_id', $messageId);
            } elseif (Schema::hasColumn('send_whatsapp_cards', 'whatsapp_message_id')) {
                $query->where('whatsapp_message_id', $messageId);
            } else {
                return;
            }

            $record = $query->first();

            if (! $record) {
                Log::warning('No SendWhatsappCard record found for webhook message ID.', [
                    'message_id' => $messageId,
                    'status' => $status,
                ]);

                return;
            }

            if (Schema::hasColumn('send_whatsapp_cards', 'status')) {
                $record->status = $status;
            }

            if (Schema::hasColumn('send_whatsapp_cards', 'whatsapp_status')) {
                $record->whatsapp_status = $status;
            }

            if (Schema::hasColumn('send_whatsapp_cards', 'failed_reason') && $errorMessage) {
                $record->failed_reason = $errorMessage;
            }

            if (Schema::hasColumn('send_whatsapp_cards', 'delivered_at') && $status === 'delivered') {
                $record->delivered_at = $timestamp;
            }

            if (Schema::hasColumn('send_whatsapp_cards', 'read_at') && $status === 'read') {
                $record->read_at = $timestamp;
            }

            $record->save();
        } catch (\Throwable $e) {
            Log::error('Failed to update WhatsApp status from webhook.', [
                'message_id' => $messageId,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log incoming WhatsApp replies.
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
    }
}