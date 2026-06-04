<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventGuest;
use Illuminate\Http\Request;
use Bryceandy\Beem\Facades\Beem;

class ScancardController extends Controller
{
    public function index($id)
    {

        $event = Event::where('code', $id)->first();
        if (!$event) {
            return view('scan-card.event-not-found');
        }

        return view('scan-card.index', compact('event'));
    }

    public function verifycard(Request $request, $id)
    {
        if ($request->scanned_value) {
            $card = $request->scanned_value;
            $event = Event::find($id);

            $guest = EventGuest::where('event_id', $id)
                ->where('invitation_code', $card)
                ->first();

            if (!$guest) {
                return back()->with([
                    'error-message' => 'Guest not found',
                    'event' => $event
                ]);
            }

            $guestCardType = strtolower(trim($guest->card_type));
            $allowedGuests = 1; // default to 1

            if ($guestCardType === 'single') {
                $allowedGuests = 1;
            } elseif ($guestCardType === 'double') {
                $allowedGuests = 2;
            } elseif (preg_match('/^watu\s+(\d+)$/', $guestCardType, $matches)) {
                $allowedGuests = (int)$matches[1];
            }

            $guestScanningTimes = $guest->scanning_times;

            if ($allowedGuests > $guestScanningTimes) {
                // guest is allowed to enter venue & increment the scanning_times of a guest
                $guest->scanning_times = $guest->scanning_times + 1;
                $guest->scanned_time = now();
                $guest->save();

// send welcome sms to the guest
           if (!empty($event->welcomingsms->SMS_welcoming) && $guest->scanning_times == 1) {
                  $message = $event->welcomingsms->SMS_welcoming;
                  $message = str_replace('#NAME#', $guest->guest_name, $message);

// SENDING MESSAGE
  
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
	"api_secret: 9RxPftxFYW0FLg7Jkm6z"
);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);

curl_close($ch);

            }
            
                return back()->with([
                    'success-message' => 'scanned successfully',
                    'guest' => $guest,
                    'event' => $event,
                    'scanning_progress' => [
                        'current' => $guest->scanning_times,
                        'total' => $allowedGuests
                    ]
                ]);
            } else {
                return back()->with([
                    'error-message' => 'card already used',
                    'guest' => $guest,
                    'event' => $event
                ]);
            }
        }

        return back()->with([
            'error-message' => 'please scan card to proceed',
            'event' => Event::find($id)
        ]);
    }
    public function verifyinvitee(Request $request, $id)
    {
        $guest = EventGuest::find($id);
        $event = Event::where('id', $guest->event_id)->first();

        if (!$guest) {
            return back()->with([
                'error-message' => 'Guest not found',
                'event' => $event
            ]);
        }

        $guestCardType = strtolower(trim($guest->card_type));
        $allowedGuests = 1; // default to 1

        if ($guestCardType === 'single') {
            $allowedGuests = 1;
        } elseif ($guestCardType === 'double') {
            $allowedGuests = 2;
        } elseif (preg_match('/^watu\s+(\d+)$/', $guestCardType, $matches)) {
            $allowedGuests = (int)$matches[1];
        }

        $guestScanningTimes = $guest->scanning_times;

        if ($allowedGuests > $guestScanningTimes) {
            // guest is allowed to enter venue & increment the scanning_times of a guest
            $guest->scanning_times = $guest->scanning_times + 1;
            $guest->scanned_time = now();
            $guest->save();
            
// send welcome sms to the guest
           if (!empty($event->welcomingsms->SMS_welcoming) && $guest->scanning_times == 1) {
                  $message = $event->welcomingsms->SMS_welcoming;
                  $message = str_replace('#NAME#', $guest->guest_name, $message);

// SENDING MESSAGE
  
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
	"api_secret: 9RxPftxFYW0FLg7Jkm6z"
);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);

curl_close($ch);

// $response_data = json_decode($response, true); 

// $messageId = $response_data['data']['shootId'] ?? null;

            }
            

            return back()->with([
                'success-message' => 'scanned successfully',
                'guest' => $guest,
                'event' => $event,
                'scanning_progress' => [
                    'current' => $guest->scanning_times,
                    'total' => $allowedGuests
                ]
            ]);
        } else {
            return back()->with([
                'error-message' => 'card already used',
                'guest' => $guest,
                'event' => $event
            ]);
        }
    }
}
