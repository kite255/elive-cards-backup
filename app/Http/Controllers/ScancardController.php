<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventGuest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScancardController extends Controller
{
    public function index($id)
    {
        $event = Event::where('code', $id)->first();

        if (! $event) {
            $event = Event::find($id);
        }

        if (! $event) {
            return view('scan-card.event-not-found');
        }

        return view('scan-card.index', compact('event'));
    }

    /**
     * Supports both old and new scan flows:
     *
     * OLD:
     * /verify-card/{eventId}?scanned_value=INVITATION_CODE
     *
     * NEW:
     * /verify-card/{invitationCode}
     */
    public function verifycard(Request $request, $id)
    {
        $scannedValue = trim((string) $request->input('scanned_value', ''));

        if ($scannedValue !== '') {
            $event = Event::find($id);

            if (! $event) {
                return back()->with([
                    'error-message' => 'Event not found',
                ]);
            }

            $guest = EventGuest::where('event_id', $event->id)
                ->where('invitation_code', $scannedValue)
                ->first();

            if (! $guest) {
                return back()->with([
                    'error-message' => 'Guest not found',
                    'event' => $event,
                ]);
            }

            return $this->processGuestScan($guest, $event);
        }

        /**
         * New simple scan flow.
         * Here $id is the scanned invitation code from /verify-card/{code}
         */
        $cardCode = trim((string) $id);

        if ($cardCode === '') {
            return back()->with([
                'error-message' => 'Please scan card to proceed',
            ]);
        }

        $guest = EventGuest::where('invitation_code', $cardCode)->first();

        /**
         * Safe fallback:
         * Some old QR links may contain guest ID instead of invitation_code.
         */
        if (! $guest && is_numeric($cardCode)) {
            $guest = EventGuest::find((int) $cardCode);
        }

        if (! $guest) {
            return back()->with([
                'error-message' => 'Guest not found',
            ]);
        }

        $event = Event::find($guest->event_id);

        if (! $event) {
            return back()->with([
                'error-message' => 'Event not found',
                'guest' => $guest,
            ]);
        }

        return $this->processGuestScan($guest, $event);
    }

    public function verifyinvitee(Request $request, $id)
    {
        $guest = EventGuest::find($id);

        if (! $guest) {
            return back()->with([
                'error-message' => 'Guest not found',
            ]);
        }

        $event = Event::find($guest->event_id);

        if (! $event) {
            return back()->with([
                'error-message' => 'Event not found',
                'guest' => $guest,
            ]);
        }

        return $this->processGuestScan($guest, $event);
    }

    private function processGuestScan(EventGuest $guest, Event $event)
    {
        $allowedGuests = $this->allowedGuestsFromCardType($guest->card_type ?? null);
        $currentScans = (int) ($guest->scanning_times ?? 0);

        if ($currentScans >= $allowedGuests) {
            return back()->with([
                'error-message' => 'Card already used',
                'guest' => $guest,
                'event' => $event,
                'scanning_progress' => [
                    'current' => $currentScans,
                    'total' => $allowedGuests,
                ],
            ]);
        }

        $guest->scanning_times = $currentScans + 1;
        $guest->scanned_time = now();
        $guest->save();

        if ((int) $guest->scanning_times === 1) {
            $this->sendWelcomeSmsSafely($event, $guest);
        }

        return back()->with([
            'success-message' => 'Scanned successfully',
            'guest' => $guest,
            'event' => $event,
            'scanning_progress' => [
                'current' => (int) $guest->scanning_times,
                'total' => $allowedGuests,
            ],
        ]);
    }

    private function allowedGuestsFromCardType(?string $cardType): int
    {
        $cardType = strtolower(trim((string) $cardType));

        if ($cardType === 'single') {
            return 1;
        }

        if ($cardType === 'double') {
            return 2;
        }

        if (preg_match('/^watu\s+(\d+)$/', $cardType, $matches)) {
            return max(1, (int) $matches[1]);
        }

        if (preg_match('/(\d+)/', $cardType, $matches)) {
            return max(1, (int) $matches[1]);
        }

        return 1;
    }

    private function sendWelcomeSmsSafely(Event $event, EventGuest $guest): void
    {
        try {
            $welcomeSms = $event->welcomingsms->SMS_welcoming ?? null;

            if (empty($welcomeSms)) {
                return;
            }

            if (empty($guest->guest_phone)) {
                return;
            }

            $message = str_replace('#NAME#', $guest->guest_name ?? '', $welcomeSms);
            $phone = $this->normalizePhone($guest->guest_phone);

            $apiUrl = config('services.elive_sms.url', env('ELIVE_SMS_URL', 'https://message.elive.co.tz/api/v1/vendor/message/send'));
            $apiKey = config('services.elive_sms.api_key', env('ELIVE_SMS_API_KEY'));
            $apiSecret = config('services.elive_sms.api_secret', env('ELIVE_SMS_API_SECRET'));
            $senderId = config('services.elive_sms.sender_id', env('ELIVE_SMS_SENDER_ID', 'elive card'));
            $deliveryReportUrl = config('services.elive_sms.delivery_report_url', env('ELIVE_SMS_DELIVERY_REPORT_URL'));

            if (empty($apiKey) || empty($apiSecret)) {
                Log::warning('Welcome SMS not sent because SMS API credentials are missing.', [
                    'guest_id' => $guest->id,
                    'event_id' => $event->id,
                ]);

                return;
            }

            $payload = [
                'senderId' => $senderId,
                'messageType' => 'text',
                'message' => $message,
                'contacts' => $phone,
            ];

            if (! empty($deliveryReportUrl)) {
                $payload['deliveryReportUrl'] = $deliveryReportUrl;
            }

            $response = Http::timeout(20)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'api_key' => $apiKey,
                    'api_secret' => $apiSecret,
                ])
                ->post($apiUrl, $payload);

            Log::info('Welcome SMS response after scan.', [
                'guest_id' => $guest->id,
                'event_id' => $event->id,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Welcome SMS failed after scan.', [
                'guest_id' => $guest->id ?? null,
                'event_id' => $event->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function normalizePhone(?string $phone): string
    {
        $phone = preg_replace('/\D+/', '', (string) $phone);

        if (str_starts_with($phone, '255')) {
            return $phone;
        }

        if (str_starts_with($phone, '0')) {
            return '255' . substr($phone, 1);
        }

        return '255' . $phone;
    }
}