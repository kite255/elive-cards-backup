<?php

namespace App\Http\Controllers;

use App\Models\ContributionCardCaption;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventSMSCard;
use App\Models\GuestPdf;
use App\Models\SendMessageCard;
use App\Models\SendWhatsappCard;
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;

class ResendcardController extends Controller
{
    /**
     * Resend generated card to one guest through WhatsApp.
     */
    public function resendwhatsappcard($guestId)
    {
        try {
            $guestId = decrypt($guestId);

            $guest = EventGuest::findOrFail($guestId);
            $event = Event::findOrFail($guest->event_id);

            $guestPdf = GuestPdf::where('event_guests_id', $guest->id)->first();

            if (!$guestPdf || empty($guestPdf->pdf_name)) {
                Alert::error('Card not found', 'Please generate this guest card before resending it on WhatsApp.');
                return redirect()->back();
            }

            if (empty($event->code)) {
                Alert::error('Event code missing', 'This event does not have a valid event code.');
                return redirect()->back();
            }

            $receiverPhone = $this->normalizePhone($guest->guest_phone);

            if (!$receiverPhone) {
                Alert::error('Invalid phone', 'The guest phone number is invalid.');
                return redirect()->back();
            }

            $pdfLink = url("/storage/cards/PDFCards/{$event->code}/{$guestPdf->pdf_name}");

            $accessToken = env('WHATSAPP_ACCESS_TOKEN');
            $phoneNumberId = env('WHATSAPP_PHONE_NUMBER_ID', '537191036142145');

            if (empty($accessToken)) {
                Alert::error('WhatsApp not configured', 'Please add WHATSAPP_ACCESS_TOKEN in your .env file.');
                return redirect()->back();
            }

            $url = "https://graph.facebook.com/v23.0/{$phoneNumberId}/messages";
            $data = $this->buildWhatsappPayload($event, $guest, $receiverPhone, $pdfLink);

            if (!$data) {
                Alert::error('Error', 'Event type not found or template data is missing.');
                return redirect()->back();
            }

            $responseData = $this->sendWhatsappRequest($url, $accessToken, $data);

            if (($responseData['success'] ?? false) !== true) {
                Log::error('WhatsApp resend failed', [
                    'guest_id' => $guest->id,
                    'event_id' => $event->id,
                    'response' => $responseData,
                ]);

                Alert::error('WhatsApp failed', $responseData['message'] ?? 'Card was not sent on WhatsApp.');
                return redirect()->back();
            }

            $messageId = $responseData['data']['messages'][0]['id'] ?? null;
            $messagingProduct = $responseData['data']['messaging_product'] ?? null;
            $recipientId = $responseData['data']['contacts'][0]['input'] ?? $receiverPhone;
            $status = $responseData['data']['messages'][0]['message_status'] ?? 'sent';

            $whatsappCard = SendWhatsappCard::firstOrNew([
                'event_guests_id' => $guest->id,
            ]);

            $whatsappCard->event_id = $event->id;
            $whatsappCard->whatsapp_sender_id = $recipientId;
            $whatsappCard->message_id = $messageId;
            $whatsappCard->sent_status = $status;
            $whatsappCard->delivery_status_time = now();
            $whatsappCard->delivery_status = null;
            $whatsappCard->reply_message = null;

            if (property_exists($whatsappCard, 'messaging_product')) {
                $whatsappCard->messaging_product = $messagingProduct;
            }

            $whatsappCard->save();

            Alert::success($guest->guest_name, 'WhatsApp card resent successfully.');
            return redirect()->back();
        } catch (\Throwable $e) {
            Log::error('WhatsApp resend exception', [
                'guest_id_encrypted' => $guestId,
                'error' => $e->getMessage(),
            ]);

            Alert::error('Failed', 'WhatsApp card was not sent. Check laravel.log for details.');
            return redirect()->back();
        }
    }

    /**
     * Resend generated card link/details to one guest through SMS.
     */
    public function resendSMScard($guestId)
    {
        try {
            $guestId = decrypt($guestId);

            $guest = EventGuest::findOrFail($guestId);
            $event = Event::findOrFail($guest->event_id);

            $card = EventSMSCard::where('event_id', $event->id)->first();

            if (!$card || empty($card->SMS_card)) {
                Alert::info('SMS card not found', 'Please create an SMS card template before resending.');
                return redirect()->back();
            }

            $receiverPhone = $this->normalizePhone($guest->guest_phone);

            if (!$receiverPhone) {
                Alert::error('Invalid phone', 'The guest phone number is invalid.');
                return redirect()->back();
            }

            $message = $this->buildSmsMessage($card->SMS_card, $guest, $event);

            $responseData = $this->sendSmsRequest($receiverPhone, $message);

            if (($responseData['success'] ?? false) !== true) {
                Log::error('SMS resend failed', [
                    'guest_id' => $guest->id,
                    'event_id' => $event->id,
                    'response' => $responseData,
                ]);

                Alert::error('SMS failed', $responseData['message'] ?? 'SMS card was not sent.');
                return redirect()->back();
            }

            $messageId = $responseData['data']['data']['shootId']
                ?? $responseData['data']['shootId']
                ?? null;

            $smsCard = SendMessageCard::firstOrNew([
                'event_guests_id' => $guest->id,
            ]);

            $smsCard->event_id = $event->id;
            $smsCard->request_id = $messageId;
            $smsCard->sent_status = !empty($messageId) ? 'sent' : 'pending';
            $smsCard->delivery_status = null;
            $smsCard->card_message = $message;
            $smsCard->save();

            Alert::success($guest->guest_name, 'SMS card resent successfully.');
            return redirect()->back();
        } catch (\Throwable $e) {
            Log::error('SMS resend exception', [
                'guest_id_encrypted' => $guestId,
                'error' => $e->getMessage(),
            ]);

            Alert::error('Failed', 'SMS card was not sent. Check laravel.log for details.');
            return redirect()->back();
        }
    }

    /**
     * Build WhatsApp Cloud API template payload.
     */
    private function buildWhatsappPayload(Event $event, EventGuest $guest, string $receiverPhone, string $pdfLink): ?array
    {
        if ($event->event_type === 'invitation') {
            return [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $receiverPhone,
                'type' => 'template',
                'template' => [
                    'name' => env('WHATSAPP_INVITATION_TEMPLATE', 'invitation_card_template'),
                    'language' => ['code' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'en_GB')],
                    'components' => [
                        [
                            'type' => 'header',
                            'parameters' => [
                                [
                                    'type' => 'image',
                                    'image' => [
                                        'link' => $pdfLink,
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $guest->guest_name ?? '-'],
                                ['type' => 'text', 'text' => $event->name ?? '-'],
                                ['type' => 'text', 'text' => $guest->card_type ?? '-'],
                                ['type' => 'text', 'text' => !empty($guest->note) ? $guest->note : '.'],
                                ['type' => 'text', 'text' => $event->place ?? '-'],
                            ],
                        ],
                        [
                            'type' => 'button',
                            'sub_type' => 'quick_reply',
                            'index' => '0',
                            'parameters' => [
                                [
                                    'type' => 'payload',
                                    'payload' => 'ATTENDING_' . $guest->id,
                                ],
                            ],
                        ],
                        [
                            'type' => 'button',
                            'sub_type' => 'quick_reply',
                            'index' => '1',
                            'parameters' => [
                                [
                                    'type' => 'payload',
                                    'payload' => 'NOT_ATTENDING_' . $guest->id,
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if ($event->event_type === 'contribution') {
            $contributionCardCaption = ContributionCardCaption::where('event_id', $event->id)->first();

            if (!$contributionCardCaption || empty($contributionCardCaption->caption)) {
                Alert::error('Caption missing', 'Insert caption for your contribution card before sending.');
                return null;
            }

            return [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $receiverPhone,
                'type' => 'template',
                'template' => [
                    'name' => env('WHATSAPP_CONTRIBUTION_TEMPLATE', 'contribution_card_template'),
                    'language' => ['code' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'en_GB')],
                    'components' => [
                        [
                            'type' => 'header',
                            'parameters' => [
                                [
                                    'type' => 'image',
                                    'image' => [
                                        'link' => $pdfLink,
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $guest->guest_name ?? '-'],
                                ['type' => 'text', 'text' => $contributionCardCaption->caption],
                            ],
                        ],
                        [
                            'type' => 'button',
                            'sub_type' => 'quick_reply',
                            'index' => '0',
                            'parameters' => [
                                [
                                    'type' => 'payload',
                                    'payload' => 'CONTRIBUTION_' . $guest->id,
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        return null;
    }

    /**
     * Replace SMS template placeholders.
     */
    private function buildSmsMessage(string $template, EventGuest $guest, Event $event): string
    {
        $cardLink = url('/invitee/download/event-card/' . strrev($guest->invitation_code));

        return str_replace(
            [
                '#NAME#',
                '#EVENT#',
                '#CARD#',
                '#CODE#',
                '#NOTE#',
                '#TAREHE#',
                '#VENUE#',
                '#CARDLINK#',
            ],
            [
                $guest->guest_name ?? '',
                $event->name ?? '',
                $guest->card_type ?? '',
                $guest->invitation_code ?? '',
                $guest->note ?? '',
                !empty($event->date) ? date('d-m-Y', strtotime($event->date)) : '',
                $event->place ?? '',
                $cardLink,
            ],
            $template
        );
    }

    /**
     * Send WhatsApp API request.
     */
    private function sendWhatsappRequest(string $url, string $accessToken, array $payload): array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'message' => $curlError,
                'http_code' => $httpCode,
            ];
        }

        $decoded = json_decode($response, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'success' => false,
                'message' => $decoded['error']['message'] ?? 'WhatsApp API request failed.',
                'http_code' => $httpCode,
                'data' => $decoded,
            ];
        }

        return [
            'success' => true,
            'http_code' => $httpCode,
            'data' => $decoded,
        ];
    }

    /**
     * Send SMS API request.
     */
    private function sendSmsRequest(string $receiverPhone, string $message): array
    {
        /*
         * Your .env currently uses ELIVE_SMS_* names.
         * The fallback SMS_* names are also supported so the controller can work
         * with both old and new environment variable naming.
         */
        $url = env('ELIVE_SMS_URL', env('SMS_API_URL', 'https://message.elive.co.tz/api/v1/vendor/message/send'));
        $apiKey = env('ELIVE_SMS_API_KEY', env('SMS_API_KEY', 'elive card'));
        $apiSecret = env('ELIVE_SMS_API_SECRET', env('SMS_API_SECRET'));
        $senderId = env('ELIVE_SMS_SENDER_ID', env('SMS_SENDER_ID', 'elive card'));
        $deliveryReportUrl = env(
            'ELIVE_SMS_DELIVERY_REPORT_URL',
            env('SMS_DELIVERY_REPORT_URL', url('/beem/sms-delivery-report'))
        );

        if (empty($apiSecret)) {
            return [
                'success' => false,
                'message' => 'ELIVE_SMS_API_SECRET is missing in .env file.',
            ];
        }

        $payload = [
            'senderId' => $senderId,
            'messageType' => 'text',
            'message' => $message,
            'contacts' => $receiverPhone,
            'deliveryReportUrl' => $deliveryReportUrl,
        ];

        $headers = [
            'Content-Type: application/json',
            'api_key: ' . $apiKey,
            'api_secret: ' . $apiSecret,
        ];

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 45,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'message' => $curlError,
                'http_code' => $httpCode,
            ];
        }

        $decoded = json_decode($response, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'success' => false,
                'message' => $decoded['message'] ?? 'SMS API request failed.',
                'http_code' => $httpCode,
                'data' => $decoded,
            ];
        }

        return [
            'success' => true,
            'http_code' => $httpCode,
            'data' => $decoded,
        ];
    }

    /**
     * Convert Tanzanian phone numbers to 255XXXXXXXXX format.
     */
    private function normalizePhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $phone = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($phone, '255') && strlen($phone) === 12) {
            return $phone;
        }

        if (str_starts_with($phone, '0') && strlen($phone) === 10) {
            return '255' . substr($phone, 1);
        }

        if (strlen($phone) === 9) {
            return '255' . $phone;
        }

        return null;
    }
}
