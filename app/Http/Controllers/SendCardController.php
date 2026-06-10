<?php

namespace App\Http\Controllers;

use App\Models\ContributionCardCaption;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventSMSCard;
use App\Models\EventSMSReminder;
use App\Models\EventSMSThankyou;
use App\Models\GuestPdf;
use App\Models\SendMessageCard;
use App\Models\SendMessageReminder;
use App\Models\SendMessageThankyou;
use App\Models\SendWhatsappCard;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;

class SendCardController extends Controller
{
    /**
     * Send generated card image through WhatsApp Cloud API.
     *
     * Important:
     * The card image link must be publicly accessible.
     * Localhost links will not work on WhatsApp Cloud API.
     */
    public function sendWhatsappCard($eventId)
    {
        $eventId = $this->resolveId($eventId);

        if (! $eventId) {
            Alert::error('Error', 'Invalid event ID.');
            return redirect()->back();
        }

        try {
            $event = Event::findOrFail($eventId);
            $eventCode = $this->getEventCode($event);

            if (! $eventCode) {
                Alert::error('Error', 'Event code is missing.');
                return redirect()->back();
            }

            $accessToken = env('WHATSAPP_ACCESS_TOKEN');
            $phoneNumberId = env('WHATSAPP_PHONE_NUMBER_ID');
            $apiVersion = env('WHATSAPP_GRAPH_VERSION', 'v23.0');

            if (! $accessToken) {
                Log::error('WhatsApp access token missing.');
                Alert::error('Error', 'WHATSAPP_ACCESS_TOKEN is missing in .env.');
                return redirect()->back();
            }

            if (! $phoneNumberId) {
                Log::error('WhatsApp phone number ID missing.');
                Alert::error('Error', 'WHATSAPP_PHONE_NUMBER_ID is missing in .env.');
                return redirect()->back();
            }

            /*
             * Ensure every guest with a generated card has a WhatsApp sending record.
             * This prevents the bulk WhatsApp button from saying "No unsent WhatsApp cards"
             * when cards exist but SendWhatsappCard rows were not created.
             */
            $this->ensureWhatsappCardRows($eventId);

            $sendWhatsappCards = SendWhatsappCard::where('event_id', $eventId)
                ->where(function ($query) {
                    $query->whereNull('sent_status')
                        ->orWhere('sent_status', '')
                        ->orWhere('sent_status', 'not sent')
                        ->orWhere('sent_status', 'pending')
                        ->orWhere('sent_status', 'failed');
                })
                ->get();

            if ($sendWhatsappCards->isEmpty()) {
                Alert::info('Info', 'No pending or failed WhatsApp cards found for this event.');
                return redirect()->back();
            }

            $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

            $sentCount = 0;
            $failedCount = 0;
            $skippedCount = 0;

            foreach ($sendWhatsappCards as $sendWhatsappCard) {
                try {
                    $guest = EventGuest::find($sendWhatsappCard->event_guests_id);

                    if (! $guest) {
                        $skippedCount++;
                        $this->markWhatsappFailed($sendWhatsappCard, null, 'Guest record not found.');

                        Log::warning('WhatsApp card skipped: guest missing.', [
                            'send_whatsapp_card_id' => $sendWhatsappCard->id,
                            'guest_id' => $sendWhatsappCard->event_guests_id,
                        ]);

                        continue;
                    }

                    $guestPdf = $this->getLatestGuestPdf($guest, $sendWhatsappCard);

                    if (! $guestPdf || empty($guestPdf->pdf_name)) {
                        $skippedCount++;
                        $this->markWhatsappFailed($sendWhatsappCard, null, 'Generated card is missing.');

                        Log::warning('WhatsApp card skipped: generated card missing.', [
                            'guest_id' => $guest->id,
                            'event_id' => $eventId,
                        ]);

                        continue;
                    }

                    $receiverPhone = $this->formatTanzaniaPhone($guest->guest_phone);

                    if (! $receiverPhone) {
                        $failedCount++;
                        $this->markWhatsappFailed($sendWhatsappCard, null, 'Invalid phone number.');

                        Log::warning('WhatsApp card skipped: invalid phone.', [
                            'guest_id' => $guest->id,
                            'phone' => $guest->guest_phone,
                        ]);

                        continue;
                    }

                    $cardImageLink = url("/storage/cards/PDFCards/{$eventCode}/{$guestPdf->pdf_name}");

                    /*
                     * WhatsApp Cloud API cannot access localhost or 127.0.0.1 links.
                     */
                    if (str_contains($cardImageLink, '127.0.0.1') || str_contains($cardImageLink, 'localhost')) {
                        $failedCount++;
                        $this->markWhatsappFailed($sendWhatsappCard, null, 'Card URL is local. Host the system before sending WhatsApp cards.');

                        Log::error('WhatsApp card skipped: local card URL cannot be used by WhatsApp.', [
                            'guest_id' => $guest->id,
                            'card_image_link' => $cardImageLink,
                        ]);

                        continue;
                    }

                    $payload = $this->buildWhatsAppTemplatePayload($event, $guest, $receiverPhone, $cardImageLink);

                    $response = $this->sendCurlJson($url, $payload, [
                        'Authorization: Bearer ' . $accessToken,
                        'Content-Type: application/json',
                    ]);

                    $responseData = $response['json'] ?? [];
                    $messageId = $responseData['messages'][0]['id'] ?? null;

                    if (! $response['success'] || ! $messageId) {
                        $failedCount++;

                        $errorMessage = $responseData['error']['message']
                            ?? $response['error']
                            ?? $response['raw']
                            ?? 'WhatsApp API request failed.';

                        $this->markWhatsappFailed($sendWhatsappCard, $messageId, $errorMessage);

                        Log::error('WhatsApp API error.', [
                            'guest_id' => $guest->id,
                            'phone' => $receiverPhone,
                            'card_image_link' => $cardImageLink,
                            'http_code' => $response['http_code'] ?? null,
                            'response' => $responseData ?: ($response['raw'] ?? null),
                        ]);

                        continue;
                    }

                    $sendWhatsappCard->whatsapp_sender_id = $responseData['contacts'][0]['input'] ?? $receiverPhone;
                    $sendWhatsappCard->message_id = $messageId;
                    $sendWhatsappCard->sent_status = $this->normalizeWhatsappStatus(
                        $responseData['messages'][0]['message_status'] ?? 'sent'
                    );
                    $sendWhatsappCard->delivery_status_time = now();
                    $sendWhatsappCard->error_message = null;
                    $sendWhatsappCard->save();

                    $sentCount++;

                    Log::info('WhatsApp card sent successfully.', [
                        'guest_id' => $guest->id,
                        'phone' => $receiverPhone,
                        'message_id' => $messageId,
                    ]);
                } catch (\Throwable $e) {
                    $failedCount++;

                    if (isset($sendWhatsappCard)) {
                        $this->markWhatsappFailed($sendWhatsappCard, null, $e->getMessage());
                    }

                    Log::error('Error processing WhatsApp card.', [
                        'send_whatsapp_card_id' => $sendWhatsappCard->id ?? null,
                        'guest_id' => $sendWhatsappCard->event_guests_id ?? null,
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                }
            }

            Alert::success(
                'WhatsApp Completed',
                "Sent: {$sentCount}, Failed: {$failedCount}, Skipped: {$skippedCount}"
            );

            return redirect()->back();
        } catch (\Throwable $e) {
            Log::error('Fatal error in sendWhatsappCard.', [
                'event_id' => $eventId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            Alert::error('Error', 'WhatsApp sending failed: ' . $e->getMessage());

            return redirect()->back();
        }
    }

    /**
     * Send SMS with card link.
     */
    public function sendMessageCard(Request $request, $eventId)
    {
        $eventId = $this->resolveId($eventId);

        if (! $eventId) {
            Alert::error('Error', 'Invalid event ID.');
            return redirect()->back();
        }

        try {
            $event = Event::findOrFail($eventId);
            $card = EventSMSCard::where('event_id', $eventId)->first();

            if (! $card || empty($card->SMS_card)) {
                Alert::info('Error', 'SMS card template not found. Please save your Card Message first.');
                return redirect()->back();
            }

            $guests = EventGuest::where('event_id', $eventId)
                ->whereDoesntHave('sendmessagecard')
                ->get();

            if ($guests->isEmpty()) {
                Alert::info('No Cards', 'No new SMS cards to send.');
                return redirect()->back();
            }

            $sentCount = 0;
            $failedCount = 0;
            $skippedCount = 0;

            foreach ($guests as $guest) {
                try {
                    $generatedCard = GuestPdf::where('event_guests_id', $guest->id)->latest()->first();

                    if (! $generatedCard) {
                        $skippedCount++;

                        Log::warning('SMS card skipped: generated card missing.', [
                            'guest_id' => $guest->id,
                            'event_id' => $eventId,
                        ]);

                        continue;
                    }

                    $message = $this->buildCardMessage($card->SMS_card, $event, $guest);
                    $smsResult = $this->sendSmsToApi($guest->guest_phone, $message);

                    $updateDetails = new SendMessageCard();
                    $updateDetails->event_id = $event->id;
                    $updateDetails->event_guests_id = $guest->id;
                    $updateDetails->request_id = $smsResult['message_id'] ?? 'unknown';
                    $updateDetails->sent_status = $smsResult['success'] ? 'sent' : 'failed';
                    $updateDetails->card_message = $message;
                    $updateDetails->save();

                    $smsResult['success'] ? $sentCount++ : $failedCount++;
                } catch (\Throwable $e) {
                    $failedCount++;

                    Log::error('SMS card sending failed for guest.', [
                        'guest_id' => $guest->id ?? null,
                        'event_id' => $eventId,
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                }
            }

            Alert::success(
                'SMS Completed',
                "Sent: {$sentCount}, Failed: {$failedCount}, Skipped: {$skippedCount}"
            );

            return redirect()->back();
        } catch (\Throwable $e) {
            Log::error('Fatal error in sendMessageCard.', [
                'event_id' => $eventId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            Alert::error('Error', 'SMS card sending failed: ' . $e->getMessage());

            return redirect()->back();
        }
    }

    /**
     * Send reminder SMS.
     */
    public function sendremindersms($eventId)
    {
        $eventId = $this->resolveId($eventId);

        if (! $eventId) {
            Alert::error('Error', 'Invalid event ID.');
            return redirect()->back();
        }

        try {
            $event = Event::findOrFail($eventId);
            $card = EventSMSReminder::where('event_id', $eventId)->first();

            if (! $card || empty($card->SMS_reminder)) {
                Alert::info('Error', 'SMS reminder template not found.');
                return redirect()->back();
            }

            $guests = EventGuest::where('event_id', $eventId)
                ->whereDoesntHave('sendmessagereminder')
                ->get();

            if ($guests->isEmpty()) {
                Alert::info('Already', 'No new guests to send reminder SMS.');
                return redirect()->back();
            }

            $sentCount = 0;
            $failedCount = 0;

            foreach ($guests as $guest) {
                try {
                    $message = $this->buildSimpleEventMessage($card->SMS_reminder, $event, $guest);
                    $smsResult = $this->sendSmsToApi($guest->guest_phone, $message);

                    $updateDetails = new SendMessageReminder();
                    $updateDetails->event_id = $event->id;
                    $updateDetails->event_guests_id = $guest->id;
                    $updateDetails->request_id = $smsResult['message_id'] ?? 'unknown';
                    $updateDetails->sent_status = $smsResult['success'] ? 'sent' : 'failed';
                    $updateDetails->reminder_message = $message;
                    $updateDetails->save();

                    $smsResult['success'] ? $sentCount++ : $failedCount++;
                } catch (\Throwable $e) {
                    $failedCount++;

                    Log::error('Reminder SMS failed for guest.', [
                        'guest_id' => $guest->id ?? null,
                        'event_id' => $eventId,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            Alert::success('Reminder SMS Completed', "Sent: {$sentCount}, Failed: {$failedCount}");

            return redirect()->back();
        } catch (\Throwable $e) {
            Log::error('Fatal error in sendremindersms.', [
                'event_id' => $eventId,
                'message' => $e->getMessage(),
            ]);

            Alert::error('Error', 'Reminder SMS failed: ' . $e->getMessage());

            return redirect()->back();
        }
    }

    /**
     * Send thank you SMS.
     */
    public function sendthankyousms($eventId)
    {
        $eventId = $this->resolveId($eventId);

        if (! $eventId) {
            Alert::error('Error', 'Invalid event ID.');
            return redirect()->back();
        }

        try {
            $event = Event::findOrFail($eventId);
            $card = EventSMSThankyou::where('event_id', $eventId)->first();

            if (! $card || empty($card->SMS_thankyou)) {
                Alert::info('Error', 'Thank you SMS template not found.');
                return redirect()->back();
            }

            $guests = EventGuest::where('event_id', $eventId)
                ->whereDoesntHave('sendmessagethankyou')
                ->get();

            if ($guests->isEmpty()) {
                Alert::info('Already', 'No new guests to send thank you SMS.');
                return redirect()->back();
            }

            $sentCount = 0;
            $failedCount = 0;

            foreach ($guests as $guest) {
                try {
                    $message = $this->buildSimpleEventMessage($card->SMS_thankyou, $event, $guest);
                    $smsResult = $this->sendSmsToApi($guest->guest_phone, $message);

                    $updateDetails = new SendMessageThankyou();
                    $updateDetails->event_id = $event->id;
                    $updateDetails->event_guests_id = $guest->id;
                    $updateDetails->request_id = $smsResult['message_id'] ?? 'unknown';
                    $updateDetails->sent_status = $smsResult['success'] ? 'sent' : 'failed';
                    $updateDetails->thankyou_message = $message;
                    $updateDetails->save();

                    $smsResult['success'] ? $sentCount++ : $failedCount++;
                } catch (\Throwable $e) {
                    $failedCount++;

                    Log::error('Thank you SMS failed for guest.', [
                        'guest_id' => $guest->id ?? null,
                        'event_id' => $eventId,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            Alert::success('Thank You SMS Completed', "Sent: {$sentCount}, Failed: {$failedCount}");

            return redirect()->back();
        } catch (\Throwable $e) {
            Log::error('Fatal error in sendthankyousms.', [
                'event_id' => $eventId,
                'message' => $e->getMessage(),
            ]);

            Alert::error('Error', 'Thank you SMS failed: ' . $e->getMessage());

            return redirect()->back();
        }
    }

    /**
     * Create missing WhatsApp sending records for guests with generated cards.
     */
    private function ensureWhatsappCardRows(int $eventId): void
    {
        $guests = EventGuest::where('event_id', $eventId)->get();

        foreach ($guests as $guest) {
            $guestPdf = GuestPdf::where('event_guests_id', $guest->id)->latest()->first();

            if (! $guestPdf) {
                continue;
            }

            SendWhatsappCard::firstOrCreate(
                [
                    'event_id' => $eventId,
                    'event_guests_id' => $guest->id,
                ],
                [
                    'guest_pdf_id' => $guestPdf->id,
                    'sent_status' => 'pending',
                ]
            );
        }
    }

    /**
     * Build WhatsApp template payload based on event type.
     */
    private function buildWhatsAppTemplatePayload(Event $event, EventGuest $guest, string $receiverPhone, string $cardImageLink): array
    {
        $receiverName = $guest->guest_name ?: '.';
        $note = $guest->note ?: '.';
        $cardType = $guest->card_type ?: '.';

        if (($event->event_type ?? null) === 'contribution') {
            $contributionCardCaption = ContributionCardCaption::where('event_id', $event->id)->first();

            $templateName = env('WHATSAPP_CONTRIBUTION_TEMPLATE', 'contribution_card_template');
            $languageCode = env('WHATSAPP_TEMPLATE_LANGUAGE', 'en_GB');

            return [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $receiverPhone,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => $languageCode,
                    ],
                    'components' => [
                        [
                            'type' => 'header',
                            'parameters' => [
                                [
                                    'type' => 'image',
                                    'image' => [
                                        'link' => $cardImageLink,
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'body',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $receiverName,
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $contributionCardCaption->caption ?? '.',
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        $templateName = env('WHATSAPP_INVITATION_TEMPLATE', 'invitation_card_template');
        $languageCode = env('WHATSAPP_TEMPLATE_LANGUAGE', 'en_GB');

        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $receiverPhone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode,
                ],
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            [
                                'type' => 'image',
                                'image' => [
                                    'link' => $cardImageLink,
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $receiverName,
                            ],
                            [
                                'type' => 'text',
                                'text' => $event->name ?? '.',
                            ],
                            [
                                'type' => 'text',
                                'text' => $cardType,
                            ],
                            [
                                'type' => 'text',
                                'text' => $note,
                            ],
                            [
                                'type' => 'text',
                                'text' => $event->place ?? '.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get latest generated card for guest.
     */
    private function getLatestGuestPdf(EventGuest $guest, ?SendWhatsappCard $sendWhatsappCard = null): ?GuestPdf
    {
        if ($sendWhatsappCard && ! empty($sendWhatsappCard->guest_pdf_id)) {
            $guestPdf = GuestPdf::find($sendWhatsappCard->guest_pdf_id);

            if ($guestPdf) {
                return $guestPdf;
            }
        }

        return GuestPdf::where('event_guests_id', $guest->id)->latest()->first();
    }

    /**
     * Save failed WhatsApp status.
     */
    private function markWhatsappFailed(SendWhatsappCard $sendWhatsappCard, ?string $messageId = null, ?string $errorMessage = null): void
    {
        $sendWhatsappCard->message_id = $messageId;
        $sendWhatsappCard->sent_status = 'failed';
        $sendWhatsappCard->delivery_status_time = now();

        /*
         * This column may not exist in old databases.
         * If it does not exist, the save will throw an error.
         * To avoid breaking old installations, remove this line if your table has no error_message column.
         */
        if ($this->modelHasAttribute($sendWhatsappCard, 'error_message')) {
            $sendWhatsappCard->error_message = $errorMessage;
        }

        $sendWhatsappCard->save();
    }

    /**
     * Normalize WhatsApp API status to simple table values.
     */
    private function normalizeWhatsappStatus(?string $status): string
    {
        $status = strtolower((string) $status);

        return match ($status) {
            'accepted', 'sent', 'delivered', 'read' => 'sent',
            'failed', 'error' => 'failed',
            default => 'sent',
        };
    }

    /**
     * Send SMS API request.
     */
    private function sendSmsToApi(?string $rawPhone, string $message): array
    {
        $phone = $this->formatTanzaniaPhone($rawPhone);

        if (! $phone) {
            Log::warning('SMS skipped: invalid phone number.', [
                'raw_phone' => $rawPhone,
            ]);

            return [
                'success' => false,
                'message' => 'Invalid phone number.',
                'message_id' => null,
            ];
        }

        $url = env('ELIVE_SMS_URL', 'https://message.elive.co.tz/api/v1/vendor/message/send');
        $senderId = env('ELIVE_SMS_SENDER_ID', 'elive card');
        $apiKey = env('ELIVE_SMS_API_KEY', 'elive card');
        $apiSecret = env('ELIVE_SMS_API_SECRET');
        $deliveryReportUrl = env('ELIVE_SMS_DELIVERY_REPORT_URL', url('/delivery-callback'));

        if (! $apiSecret) {
            Log::error('SMS API secret missing in .env.');

            return [
                'success' => false,
                'message' => 'SMS API secret missing in .env.',
                'message_id' => null,
            ];
        }

        $payload = [
            'senderId' => $senderId,
            'messageType' => 'text',
            'message' => $message,
            'contacts' => $phone,
            'deliveryReportUrl' => $deliveryReportUrl,
        ];

        $response = $this->sendCurlJson($url, $payload, [
            'Content-Type: application/json',
            'api_key: ' . $apiKey,
            'api_secret: ' . $apiSecret,
        ]);

        $responseData = $response['json'] ?? [];
        $messageId = $responseData['data']['shootId'] ?? null;

        $isSuccess = $response['success'] && (($responseData['success'] ?? true) === true);

        Log::info('SMS API response.', [
            'phone' => $phone,
            'success' => $isSuccess,
            'http_code' => $response['http_code'] ?? null,
            'response' => $responseData ?: ($response['raw'] ?? null),
        ]);

        return [
            'success' => $isSuccess,
            'message' => $responseData['message'] ?? ($response['error'] ?? 'SMS request processed.'),
            'message_id' => $messageId,
        ];
    }

    /**
     * Send JSON request using cURL.
     */
    private function sendCurlJson(string $url, array $payload, array $headers): array
    {
        $ch = curl_init($url);

        if ($ch === false) {
            return [
                'success' => false,
                'http_code' => 0,
                'error' => 'Failed to initialize cURL.',
                'json' => null,
                'raw' => null,
            ];
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $raw = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $json = json_decode($raw ?: '', true);

        return [
            'success' => empty($curlError) && $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'error' => $curlError ?: null,
            'json' => is_array($json) ? $json : null,
            'raw' => $raw,
        ];
    }

    /**
     * Format Tanzania phone number to 255xxxxxxxxx.
     */
    private function formatTanzaniaPhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $phone = preg_replace('/\D/', '', $phone);

        if (! $phone) {
            return null;
        }

        if (str_starts_with($phone, '255') && strlen($phone) === 12) {
            return $phone;
        }

        if (str_starts_with($phone, '0') && strlen($phone) === 10) {
            return '255' . substr($phone, 1);
        }

        if ((str_starts_with($phone, '7') || str_starts_with($phone, '6')) && strlen($phone) === 9) {
            return '255' . $phone;
        }

        return null;
    }

    /**
     * Build SMS card message from the user's designed/saved template.
     */
    private function buildCardMessage(string $template, Event $event, EventGuest $guest): string
    {
        $downloadCode = strrev((string) $guest->invitation_code);
        $cardLink = url('/invitee/download/event-card/' . $downloadCode);

        return str_replace(
            [
                '#NAME#',
                '#B#',
                '#INVITEE#',
                '#EVENT#',
                '#CARD#',
                '#CARDTYPE#',
                '#CODE#',
                '#NOTE#',
                '#TAREHE#',
                '#DATE#',
                '#VENUE#',
                '#PLACE#',
                '#UKUMBI#',
                '#MUDA#',
                '#TIME#',
                '#CARDLINK#',
                '#LINK#',
            ],
            [
                strtoupper((string) $guest->guest_name),
                strtoupper((string) $guest->guest_name),
                strtoupper((string) $guest->guest_name),
                $event->name,
                strtoupper((string) $guest->card_type),
                strtoupper((string) $guest->card_type),
                $guest->invitation_code,
                $guest->note ?: '',
                $this->formatEventDate($event),
                $this->formatEventDate($event),
                $event->place,
                $event->place,
                $event->place,
                $this->formatEventTime($event),
                $this->formatEventTime($event),
                $cardLink,
                $cardLink,
            ],
            $template
        );
    }

    /**
     * Build reminder/thank you SMS message from the user's designed/saved template.
     */
    private function buildSimpleEventMessage(string $template, Event $event, EventGuest $guest): string
    {
        $downloadCode = strrev((string) $guest->invitation_code);
        $cardLink = url('/invitee/download/event-card/' . $downloadCode);

        return str_replace(
            [
                '#NAME#',
                '#B#',
                '#INVITEE#',
                '#EVENT#',
                '#TAREHE#',
                '#DATE#',
                '#VENUE#',
                '#PLACE#',
                '#UKUMBI#',
                '#CARD#',
                '#CARDTYPE#',
                '#CODE#',
                '#NOTE#',
                '#MUDA#',
                '#TIME#',
                '#CARDLINK#',
                '#LINK#',
            ],
            [
                strtoupper((string) $guest->guest_name),
                strtoupper((string) $guest->guest_name),
                strtoupper((string) $guest->guest_name),
                $event->name,
                $this->formatEventDate($event),
                $this->formatEventDate($event),
                $event->place,
                $event->place,
                $event->place,
                strtoupper((string) $guest->card_type),
                strtoupper((string) $guest->card_type),
                $guest->invitation_code,
                $guest->note ?: '',
                $this->formatEventTime($event),
                $this->formatEventTime($event),
                $cardLink,
                $cardLink,
            ],
            $template
        );
    }

    /**
     * Format event date safely.
     */
    private function formatEventDate(Event $event): string
    {
        $date = $event->date
            ?? $event->event_date
            ?? $event->start_date
            ?? null;

        if (empty($date)) {
            return '';
        }

        try {
            return date('d-m-Y', strtotime($date));
        } catch (\Throwable $e) {
            return (string) $date;
        }
    }

    /**
     * Format event time safely.
     */
    private function formatEventTime(Event $event): string
    {
        $time = $event->time
            ?? $event->event_time
            ?? $event->start_time
            ?? $event->starting_time
            ?? null;

        if (empty($time)) {
            return '';
        }

        try {
            return date('H:i', strtotime($time));
        } catch (\Throwable $e) {
            return (string) $time;
        }
    }

    /**
     * Accept encrypted IDs and plain numeric IDs safely.
     */
    private function resolveId($id): ?int
    {
        if (is_numeric($id)) {
            return (int) $id;
        }

        try {
            $decrypted = decrypt($id);

            return is_numeric($decrypted) ? (int) $decrypted : null;
        } catch (DecryptException $e) {
            Log::warning('Invalid encrypted ID received in SendCardController.', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::warning('ID resolution failed in SendCardController.', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get event code from different possible column names.
     */
    private function getEventCode(Event $event): ?string
    {
        return $event->code
            ?? $event->event_code
            ?? $event->eventCode
            ?? null;
    }

    /**
     * Safely check if a model has an attribute/column loaded.
     */
    private function modelHasAttribute($model, string $attribute): bool
    {
        return array_key_exists($attribute, $model->getAttributes())
            || in_array($attribute, $model->getFillable(), true);
    }
}
