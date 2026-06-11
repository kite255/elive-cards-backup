<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventGuest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * Supports:
     * /verify-card/{eventId}?scanned_value=INVITATION_CODE
     * /verify-card/{invitationCode}
     * /verify-card/{eventId}?scanned_value=https://domain.com/i/INVITATION_CODE
     */
    public function verifycard(Request $request, $id)
    {
        $scannedValue = $this->extractInvitationCode(
            $request->input('scanned_value', '')
        );

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
                    'scanned_value' => $scannedValue,
                ]);
            }

            return $this->processGuestScan($guest->id);
        }

        $cardCode = $this->extractInvitationCode($id);

        if ($cardCode === '') {
            return back()->with([
                'error-message' => 'Please scan card to proceed',
            ]);
        }

        $guest = EventGuest::where('invitation_code', $cardCode)->first();

        if (! $guest && is_numeric($cardCode)) {
            $guest = EventGuest::find((int) $cardCode);
        }

        if (! $guest) {
            return back()->with([
                'error-message' => 'Guest not found',
                'scanned_value' => $cardCode,
            ]);
        }

        return $this->processGuestScan($guest->id);
    }

    public function verifyinvitee(Request $request, $id)
    {
        $guest = EventGuest::find($id);

        if (! $guest) {
            return back()->with([
                'error-message' => 'Guest not found',
            ]);
        }

        return $this->processGuestScan($guest->id);
    }

    private function processGuestScan(int $guestId)
    {
        try {
            $result = DB::transaction(function () use ($guestId) {
                $guest = EventGuest::whereKey($guestId)
                    ->lockForUpdate()
                    ->first();

                if (! $guest) {
                    return [
                        'status' => 'error',
                        'message' => 'Guest not found',
                    ];
                }

                $event = Event::find($guest->event_id);

                if (! $event) {
                    return [
                        'status' => 'error',
                        'message' => 'Event not found',
                        'guest' => $guest,
                    ];
                }

                $allowedGuests = $this->allowedGuestsFromCardType($guest->card_type ?? null);
                $currentScans = (int) ($guest->scanning_times ?? 0);

                if ($currentScans >= $allowedGuests) {
                    return [
                        'status' => 'already_used',
                        'message' => 'Card already used',
                        'guest' => $guest,
                        'event' => $event,
                        'current' => $currentScans,
                        'total' => $allowedGuests,
                    ];
                }

                $guest->scanning_times = $currentScans + 1;
                $guest->scanned_time = now();
                $guest->save();

                return [
                    'status' => 'success',
                    'message' => 'Scanned successfully',
                    'guest' => $guest->fresh(),
                    'event' => $event,
                    'current' => (int) $guest->scanning_times,
                    'total' => $allowedGuests,
                    'send_welcome_sms' => ((int) $guest->scanning_times === 1),
                ];
            });

            if (($result['status'] ?? null) === 'success') {
                if (! empty($result['send_welcome_sms'])) {
                    $this->sendWelcomeSmsSafely($result['event'], $result['guest']);
                }

                return back()->with([
                    'success-message' => $result['message'],
                    'guest' => $result['guest'],
                    'event' => $result['event'],
                    'scanning_progress' => [
                        'current' => $result['current'],
                        'total' => $result['total'],
                    ],
                ]);
            }

            if (($result['status'] ?? null) === 'already_used') {
                return back()->with([
                    'error-message' => $result['message'],
                    'guest' => $result['guest'],
                    'event' => $result['event'],
                    'scanning_progress' => [
                        'current' => $result['current'],
                        'total' => $result['total'],
                    ],
                ]);
            }

            return back()->with([
                'error-message' => $result['message'] ?? 'Scan failed',
                'guest' => $result['guest'] ?? null,
                'event' => $result['event'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Scan processing failed.', [
                'guest_id' => $guestId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()->with([
                'error-message' => 'Scan failed. Please try again.',
            ]);
        }
    }

    /**
     * Accept raw code or full private invitation link.
     */
    private function extractInvitationCode($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $value = str_replace('\\', '/', $value);

        /*
         * Use ~ delimiter, not #.
         * This prevents "Unknown modifier ']'" when matching URLs with # characters.
         */
        if (preg_match('~/i/([^/?#]+)~i', $value, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('~/verify-card/([^/?#]+)~i', $value, $matches)) {
            return trim($matches[1]);
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $path = trim((string) parse_url($value, PHP_URL_PATH), '/');

            if ($path !== '') {
                $segments = explode('/', $path);
                $last = end($segments);

                return trim((string) $last);
            }
        }

        return trim($value);
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

        if ($cardType === 'family') {
            return 5;
        }

        if ($cardType === 'group') {
            return 1;
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
            $event->loadMissing('welcomingsms');

            $welcomeSms = $event->welcomingsms->SMS_welcoming ?? null;

            if (empty($welcomeSms)) {
                return;
            }

            if (empty($guest->guest_phone)) {
                return;
            }

            $message = str_replace('#NAME#', $guest->guest_name ?? '', $welcomeSms);
            $phone = $this->normalizePhone($guest->guest_phone);

            if ($phone === '') {
                return;
            }

            $apiUrl = config(
                'services.elive_sms.url',
                env('ELIVE_SMS_URL', 'https://message.elive.co.tz/api/v1/vendor/message/send')
            );

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
                'contacts' => [
                    [
                        'recipient_id' => (string) $guest->id,
                        'dest_addr' => $phone,
                    ],
                ],
            ];

            if (! empty($deliveryReportUrl)) {
                $payload['deliveryReportUrl'] = $deliveryReportUrl;
            }

            $response = Http::timeout(20)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
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

        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '255')) {
            return $phone;
        }

        if (str_starts_with($phone, '0')) {
            return '255' . substr($phone, 1);
        }

        if (str_starts_with($phone, '7') || str_starts_with($phone, '6')) {
            return '255' . $phone;
        }

        return $phone;
    }
}