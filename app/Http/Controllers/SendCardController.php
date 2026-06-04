<?php

namespace App\Http\Controllers;

use App\Models\ContributionCardCaption;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventSMSCard;
use App\Models\EventSMSReminder;
use App\Models\EventSMSThankyou;
use App\Models\GuestPdf;
use App\Models\SendWhatsappCard;
use App\Models\SendMessageCard;
use App\Models\SendMessageReminder;
use App\Models\SendMessageThankyou;
use Bryceandy\Beem\Facades\Beem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Log;


class SendCardController extends Controller
{
    public function sendWhatsappCard($eventId)
    {
        try {
            //get event code from event table
            $event = Event::findOrFail($eventId);
            if (!$event) {
                Alert::error('Error', 'Event not found');
                return redirect()->back();
            }
            $eventCode = $event->code;

            //get info to send card from tables in db
            $sendWhatsappCards = SendWhatsappCard::where('event_id', $eventId)
                ->where('sent_status', 'not sent')
                ->with(['eventguests', 'guestpdfs'])
                ->get();

            if ($sendWhatsappCards->isEmpty()) {
                Alert::info('info', 'No unsent WhatsApp cards found for this event');
                return redirect()->back();
            }

            foreach ($sendWhatsappCards as $sendWhatsappCard) {
                try {
                    if (!$sendWhatsappCard->eventguests) {
                        Alert::error('Error', 'Event guest not found');
                        return redirect()->back();
                    }
                    $receiverPhone = $sendWhatsappCard->eventguests->guest_phone;
                    $receiverName = $sendWhatsappCard->eventguests->guest_name;
                    $note = $sendWhatsappCard->eventguests->note;
                    $cardType = $sendWhatsappCard->eventguests->card_type;
                    if (!$sendWhatsappCard->guestpdfs || empty($receiverPhone)) {
                        //if guest card not found or phone number is empty,skip to next guest
                        continue;
                    }
                    // Generate full URL for the PDF file
                    $pdfLink = url("/storage/cards/PDFCards/{$eventCode}/{$sendWhatsappCard->guestpdfs->pdf_name}");

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
                                          //  ['type' => 'text', 'text' => $event->venue_map_location_link ? 'LOCATION: ' . $event->venue_map_location_link : '----------------']
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
                    if ($ch === false) {
                        dd('Error: Failed to initialize cURL');
                    }
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
                        dd('cURL Error: ' . curl_error($ch));
                    }
                    $responseData = json_decode($response, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        dd('Error decoding JSON response: ' . json_last_error_msg(), 'Raw response: ' . $response);
                    }
                    if (!isset($responseData['messages'][0]['id'])) {
                        dd('Error in WhatsApp API response:', $responseData);
                    }
                    // Extract individual data from response
                    $messageId = $responseData['messages'][0]['id'] ?? null;
                    $messagingProduct = $responseData['messaging_product'] ?? null;
                    $recipientId = $responseData['contacts'][0]['input'] ?? null;
                    $status = $responseData['messages'][0]['message_status'] ?? null;
                    $updateSendWhatsappCard = SendWhatsappCard::where('event_guests_id', $sendWhatsappCard->event_guests_id)->first();
                    if (!$updateSendWhatsappCard) {
                        dd('Error: Could not find WhatsApp card to update for event_guests_id: ' . $sendWhatsappCard->event_guests_id);
                    }
                    $updateSendWhatsappCard->whatsapp_sender_id = $recipientId;
                    $updateSendWhatsappCard->message_id = $messageId;
                    $updateSendWhatsappCard->sent_status = $status;
                    $updateSendWhatsappCard->delivery_status_time = now();
                    if (!$updateSendWhatsappCard->save()) {
                        dd('Error: Failed to save WhatsApp card update');
                    }
                    curl_close($ch);
                } catch (\Exception $e) {
                    dd('Error processing WhatsApp card: ' . $e->getMessage(), 'Stack trace: ' . $e->getTraceAsString());
                }
            }
            Alert::success('successfully', 'card sent whatsapp successfully');
            return redirect()->back();
        } catch (\Exception $e) {
            Alert::error('Error', 'Fatal error in sendWhatsappCard: ' . $e->getMessage());
            return redirect()->back();
        }
    }

  
    
    
    public function sendMessageCard(Request $request, $eventId)
    {
        //get event details from event table
        $event = Event::findOrFail($eventId);
        if (!$event) {
            Alert::info('error', 'No event found');
            return redirect()->back();
        }

        // get card details from sms card table
        $card = EventSMSCard::where('event_id', $eventId)->first();

        if (!$card) {
            Alert::info('error', 'sms card not found');
            return redirect()->back();
        }

        $messageTemplate = $card->SMS_card;

       // fetch guests to receive card
       
       $guestsQuery = EventGuest::where('event_id', $eventId)
           ->whereDoesntHave('sendmessagecard');
       if ($request->invitees_to_send !== 'all') {
           $guestsQuery->whereHas('sendwhatsappcard', function($query) use ($request) {
               $query->where('delivery_status', $request->invitees_to_send);
           });
       }
       $guests = $guestsQuery->get();



        if ($guests->isEmpty()) {
            Alert::info('No Cards', 'No new cards to send.');
            return redirect()->back();
        }

        $messageCount = 0;

        foreach ($guests as $guest) {
          
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

 

            //  Save message details
            $updateDetails = new SendMessageCard();
            $updateDetails->event_id = $event->id;
            $updateDetails->event_guests_id = $guest->id;
            $updateDetails->request_id = $messageId ?? 'unknown';  // Set a default value
            $updateDetails->sent_status = 'sent';
            $updateDetails->card_message = $message;
            $saved = $updateDetails->save();
            $messageCount++;
        }
        Alert::success('Successfully Sent', $messageCount . ' cards sent successfully');
        return redirect()->back();
    }

    //class to send reminder sms
    public function sendremindersms($eventId)
    {
        //get event details from event table
        $event = Event::findOrFail($eventId);
        if (!$event) {
            Alert::info('error', 'No event found');
            return redirect()->back();
        }

        // get card details from sms card table
        $card = EventSMSReminder::where('event_id', $eventId)->first();

        if (!$card) {
            Alert::info('error', 'sms card not found');
            return redirect()->back();
        }

        $messageTemplate = $card->SMS_reminder;

        // fetch guests to receive card
        $guests = EventGuest::where('event_id', $eventId)
            ->whereDoesntHave('sendmessagereminder')
            ->get();

        if ($guests->isEmpty()) {
            Alert::info('Already', 'No new guests to send reminder sms.');
            return redirect()->back();
        }

        $messageCount = 0;

        foreach ($guests as $guest) {

            // Replace placeholders with actual values
            $message = $messageTemplate;
            $message = str_replace('#NAME#', $guest->guest_name, $message);
            $message = str_replace('#EVENT#', $event->name, $message);
            $message = str_replace('#TAREHE#', date('d-m-Y', strtotime($event->date)), $message);

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


            //  Save message details
            $updateDetails = new SendMessageReminder();
            $updateDetails->event_id = $event->id;
            $updateDetails->event_guests_id = $guest->id;
            $updateDetails->request_id = $messageId ?? 'unknown';  // Set a default value
            $updateDetails->sent_status = 'sent';
            $updateDetails->reminder_message = $message;
            $saved = $updateDetails->save();
            $messageCount++;
        }
        Alert::success('Successfully Sent', $messageCount . ' reminder sms sent successfully');
        return redirect()->back();
    }

    //class to send thank you sms
    public function sendthankyousms($eventId)
    {
        //get event details from event table
        $event = Event::findOrFail($eventId);
        if (!$event) {
            Alert::info('error', 'No event found');
            return redirect()->back();
        }

        // get card details from sms card table
        $card = EventSMSThankyou::where('event_id', $eventId)->first();

        if (!$card) {
            Alert::info('error', 'sms card not found');
            return redirect()->back();
        }

        $messageTemplate = $card->SMS_thankyou;

        // fetch guests to receive card
        $guests = EventGuest::where('event_id', $eventId)
            ->whereDoesntHave('sendmessagethankyou')
            ->get();

        if ($guests->isEmpty()) {
            Alert::info('Already', 'No new guests to send thank you sms.');
            return redirect()->back();
        }

        $messageCount = 0;

        foreach ($guests as $guest) {

            // Replace placeholders with actual values
            $message = $messageTemplate;
            $message = str_replace('#NAME#', $guest->guest_name, $message);
            $message = str_replace('#EVENT#', $event->name, $message);
            $message = str_replace('#TAREHE#', date('d-m-Y', strtotime($event->date)), $message);

          
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


            //  Save message details
            $updateDetails = new SendMessageThankyou();
            $updateDetails->event_id = $event->id;
            $updateDetails->event_guests_id = $guest->id;
             $updateDetails->request_id = $messageId ?? 'unknown';  // Set a default value
            $updateDetails->sent_status = 'sent';
            $updateDetails->thankyou_message = $message;
            $saved = $updateDetails->save();
            $messageCount++;
        }
        Alert::success('Successfully Sent', $messageCount . ' thank you sms sent successfully');
        return redirect()->back();
    }
}
