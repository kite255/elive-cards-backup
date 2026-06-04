<?php

namespace App\Http\Controllers;

use App\Models\ContributionCardCaption;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventSMSCard;
use App\Models\GuestPdf;
use App\Models\SendMessageCard;
use App\Models\SendWhatsappCard;
use Bryceandy\Beem\Facades\Beem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;

class ResendcardController extends Controller
{
    public function resendwhatsappcard($guestId)
    {
        $guestId = decrypt($guestId);
        $guest = EventGuest::findOrFail($guestId);
        $receiverPhone = $guest->guest_phone;
        $receiverName = $guest->guest_name;
        $cardType = $guest->card_type;
        $note = $guest->note;

        // fetch event id from guest details
        $eventId = $guest->event_id;

        $event = Event::findOrFail($eventId);
        $eventCode = $event->code;

        $guestPdf = GuestPdf::where('event_guests_id', $guestId)->first();

        // Generate full URL for the PDF file
        $pdfLink = url("/storage/cards/PDFCards/{$eventCode}/{$guestPdf->pdf_name}");
      
                 
                 //  WhatsApp API configuration
                    $access_token = 'EAANO9ZCIQlT4BP4CFCbZAQnjZC6EcZBNw9rdCbiLgImaCIybwF0Rr60qG9ZAnh1xs7ZAYz6RUuEfMCrMh7mCUh3DkNzJ1NtAhvLLBaIO9Dv2NtblywyYXSUZCrdKFNPMGFoNZAjNz8I3bcjsAjcWVw6WewULDUjZC7pPOouq7shJjJ6fgCGiJrgdq1RA0Mr7RIePo0gZDZD';
                    $url = 'https://graph.facebook.com/v23.0/537191036142145/messages';

                    if ($event->event_type == 'invitation') {
                        $data = [
                            'messaging_product' => 'whatsapp',
                            'recipient_type' => 'individual',
                            'to' => $receiverPhone,
                            'type' => 'template',
                            'template' => [
                                'name' => 'invitation_card_template',
                                'language' => ['code' => 'en_GB'],
                                'components' => [
                                    [
                                        'type' => 'header',
                                        'parameters' => [[
                                            'type' => 'image',
                                            'image' => [
                                                'link' => $pdfLink,
                                            ]
                                        ]]
                                    ],
                                    [
                                        'type' => 'body',
                                        'parameters' => [
                                            ['type' => 'text', 'text' => $receiverName],
                                            ['type' => 'text', 'text' => $event->name],
                                            ['type' => 'text', 'text' => $cardType],
                                            ['type' => 'text', 'text' => !empty($note) ? $note : '.'],
                                            ['type' => 'text', 'text' => $event->place],
                                         //   ['type' => 'text', 'text' => $event->venue_map_location_link ? 'LOCATION: ' . $event->venue_map_location_link : '----------------']
                                        ]
                                    ],
                                    [
                                        'type' => 'button',
                                        'sub_type' => 'quick_reply',
                                        'index' => '0',
                                        'parameters' => [[
                                            'type' => 'payload',
                                            'payload' => 'PAYLOAD'
                                        ]]
                                    ],
                                    [
                                        'type' => 'button',
                                        'sub_type' => 'quick_reply',
                                        'index' => '1',
                                        'parameters' => [[
                                            'type' => 'payload',
                                            'payload' => 'PAYLOAD'
                                        ]]
                                    ]
                                ]
                            ]
                        ];
                    } elseif ($event->event_type == 'contribution') {
                        $contributionCardCaption = ContributionCardCaption::where('event_id', $event->id)->first();
                        if (!$contributionCardCaption) {
                            Alert::error('Error', 'insert caption for your card before sending');
                            return redirect()->back();
                        }
                        $caption = $contributionCardCaption->caption;
                        $data = [
                            'messaging_product' => 'whatsapp',
                            'recipient_type' => 'individual',
                            'to' => $receiverPhone,
                            'type' => 'template',
                            'template' => [
                                'name' => 'contribution_card_template',
                                'language' => ['code' => 'en_GB'],
                                'components' => [
                                    [
                                        'type' => 'header',
                                        'parameters' => [[
                                            'type' => 'image',
                                            'image' => [
                                                'link' => $pdfLink,
                                            ]
                                        ]]
                                    ],
                                    [
                                        'type' => 'body',
                                        'parameters' => [
                                            ['type' => 'text', 'text' => $receiverName],
                                            ['type' => 'text', 'text' => $caption],
                                        ]
                                    ],
                                    [
                                        'type' => 'button',
                                        'sub_type' => 'quick_reply',
                                        'index' => '0',
                                        'parameters' => [[
                                            'type' => 'payload',
                                            'payload' => 'PAYLOAD'
                                        ]]
                                    ]
                                ]
                            ]
                        ];
                    } else {
                        Alert::error('Error', 'Event type not found');
                        return redirect()->back();
                    }
                    

        // Send WhatsApp message
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            // Handle error
            $error = curl_error($ch);
            dd('Error:', $error); // Debug curl errors if any
        }

        $responseData = json_decode($response, true);
        //dd($responseData);
        // Extract individual data from response
        $messageId = $responseData['messages'][0]['id'] ?? null;
        $messagingProduct = $responseData['messaging_product'] ?? null;
        $recipientId = $responseData['contacts'][0]['input'] ?? null;
        $status = $responseData['messages'][0]['message_status'] ?? null;

        //dd($recipientId);
        $updateSendWhatsappCard = SendWhatsappCard::where('event_guests_id', $guest->id)->first();
        $updateSendWhatsappCard->whatsapp_sender_id = $recipientId;
        $updateSendWhatsappCard->message_id = $messageId;
        $updateSendWhatsappCard->sent_status = $status;
        $updateSendWhatsappCard->delivery_status_time = now();
        $updateSendWhatsappCard->delivery_status = null;
        $updateSendWhatsappCard->reply_message = null;
        $updateSendWhatsappCard->save();
        curl_close($ch);
        Alert::success($guest->name, 'successful resend whatsapp card');
        return redirect()->back();
    }



    public function resendSMScard($guestId)
    {
        $guestId = decrypt($guestId);
        // fetch guests to receive card
        $guest = EventGuest::findOrFail($guestId);

        //get event details from event table
        $event = Event::findOrFail($guest->event_id);

        // get card details from sms card table
        $card = EventSMSCard::where('event_id', $event->id)->first();

        if (!$card) {
            Alert::info('Info', 'sms card not found');
            return redirect()->back();
        }

        $messageTemplate = $card->SMS_card;

        try {

            // Replace placeholders with actual values
            $message = $messageTemplate;
            $message = str_replace('#NAME#', $guest->guest_name, $message);
            $message = str_replace('#EVENT#', $event->name, $message);
            $message = str_replace('#CARD#', $guest->card_type, $message);
            $message = str_replace('#CODE#', $guest->invitation_code, $message);
            $message = str_replace('#NOTE#', $guest->note, $message);
            $message = str_replace('#TAREHE#', date('d-m-Y', strtotime($event->date)), $message);
            $message = str_replace('#VENUE#', $event->place, $message);
            $message = str_replace('#CARDLINK#', 'https://staff.elivecard.site/invitee/download/event-card/' . strrev($guest->invitation_code), $message);

        //   sending sms card section
$url = "https://message.elive.co.tz/api/v1/vendor/message/send";
$data = array(
	"senderId" => "elive card",
	"messageType" => "text",
	"message" => $message,
	"contacts" => "255" . $guest->guest_phone,
	"deliveryReportUrl" => "https://your-server.com/delivery-callback"
);

$headers = array(
	"Content-Type: application/json",
	"api_key: elive card",
	"api_secret: FwoVF9fxHt8rJ1hhgprB"
);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);

curl_close($ch);

$response_data = json_decode($response, true); 

$messageId = $response_data['data']['shootId'] ?? null;

          

            // Save message details if no existing record for this guest
            $existingCard = SendMessageCard::where('event_guests_id', $guest->id)->first();

            //insert new card record if no card record present for specific guest
            if (!$existingCard) {
                // Store the card sent info only if request_id is not empty
                if (!empty($messageId)) {
                    $updateDetails = new SendMessageCard();
                    $updateDetails->event_id = $event->id;
                    $updateDetails->event_guests_id = $guest->id;
                    $updateDetails->request_id = $messageId;
                    $updateDetails->sent_status = 'sent';
                    $updateDetails->card_message = $message;
                    $saved = $updateDetails->save();
                }
            } else {
                // Modify existing card details if card details are present
                if (!empty($messageId)) {
                    $existingCard->request_id = $messageId;
                    $existingCard->sent_status = 'sent';
                    $existingCard->delivery_status = null;
                    $existingCard->card_message = $message;
                    $saved = $existingCard->save();
                }
            }


            Alert::success($guest->guest_name, 'sms sent successfully');
            return redirect()->back();
        } catch (\Exception $e) {
            Alert::info('failed', 'card not sent to ' . $guest->name);
            return redirect()->back();
        }
    }
}
