<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventGuest;
use App\Models\GuestPdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadCardController extends Controller
{
    public function index($id)
    {
        try {
            /*
            |--------------------------------------------------------------------------
            | Resolve guest by invitation code
            |--------------------------------------------------------------------------
            | SMS links may send reversed invitation code.
            | Example:
            | Original: 894459
            | Link:     954498
            */
            $receivedCode = trim((string) $id);
            $normalCode = $receivedCode;
            $reversedCode = strrev($receivedCode);

            $guest = EventGuest::where('invitation_code', $normalCode)
                ->orWhere('invitation_code', $reversedCode)
                ->first();

            if (! $guest) {
                return response('Guest not found for invitation code: ' . e($id), 404);
            }

            $event = Event::find($guest->event_id);

            if (! $event) {
                return response('Event not found for this guest.', 404);
            }

            $guestPdf = GuestPdf::where('event_guests_id', $guest->id)
                ->latest('id')
                ->first();

            if (! $guestPdf) {
                return response('Guest card not found. Please generate the guest card first.', 404);
            }

            $cardPath = $this->resolveCardPath($guestPdf, $event);

            if (! $cardPath) {
                Log::error('Invitee card file not found on public disk.', [
                    'download_code' => $id,
                    'guest_id' => $guest->id,
                    'guest_name' => $guest->guest_name,
                    'event_id' => $event->id,
                    'event_code' => $event->code,
                    'guest_pdf_id' => $guestPdf->id,
                    'pdf_name' => $guestPdf->pdf_name,
                    'checked_paths' => $this->candidateCardPaths($guestPdf, $event),
                ]);

                return response(
                    'Card file not found on server. Please regenerate this guest card.',
                    404
                );
            }

            $guestName = $guest->guest_name ?: 'guest_card';
            $safeGuestName = Str::slug($guestName, '_') ?: 'guest_card';
            $extension = pathinfo($cardPath, PATHINFO_EXTENSION) ?: 'jpg';

            return Storage::disk('public')->download(
                $cardPath,
                $safeGuestName . '_card.' . $extension
            );
        } catch (\Throwable $e) {
            Log::error('Invitee card download failed.', [
                'download_code' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response('Download failed. Please contact support.', 500);
        }
    }

    private function resolveCardPath(GuestPdf $guestPdf, Event $event): ?string
    {
        foreach ($this->candidateCardPaths($guestPdf, $event) as $path) {
            if (Storage::disk('public')->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function candidateCardPaths(GuestPdf $guestPdf, Event $event): array
    {
        $pdfName = trim((string) ($guestPdf->pdf_name ?? ''));
        $eventCode = trim((string) ($event->code ?? ''));

        if ($pdfName === '') {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize bad/old stored paths
        |--------------------------------------------------------------------------
        | Public disk path should be:
        | cards/PDFCards/{event_code}/{file_name}
        |
        | Not:
        | storage/cards/PDFCards/{event_code}/cards/PDFCards/{event_code}/{file_name}
        */
        $cleanPdfName = str_replace('\\', '/', $pdfName);
        $cleanPdfName = ltrim($cleanPdfName, '/');

        $cleanPdfName = preg_replace('#^public/#i', '', $cleanPdfName);
        $cleanPdfName = preg_replace('#^storage/#i', '', $cleanPdfName);
        $cleanPdfName = preg_replace('#^app/public/#i', '', $cleanPdfName);

        $fileNameOnly = basename($cleanPdfName);

        $eventCodes = array_values(array_unique(array_filter([
            $eventCode,
            strtolower($eventCode),
            strtoupper($eventCode),
        ])));

        $paths = [];

        /*
        |--------------------------------------------------------------------------
        | 1. If pdf_name is already a public disk relative path, test it directly.
        |--------------------------------------------------------------------------
        */
        if (str_contains($cleanPdfName, '/')) {
            $paths[] = $cleanPdfName;
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Correct standard paths using only the final filename.
        |--------------------------------------------------------------------------
        */
        foreach ($eventCodes as $code) {
            $paths[] = 'cards/PDFCards/' . $code . '/' . $fileNameOnly;
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Repair duplicated path.
        |--------------------------------------------------------------------------
        | Example bad value:
        | cards/PDFCards/CD7379/cards/PDFCards/cd7379/card.jpg
        |
        | Repaired:
        | cards/PDFCards/cd7379/card.jpg
        */
        if (preg_match('#cards/PDFCards/[^/]+/(cards/PDFCards/.+)$#i', $cleanPdfName, $matches)) {
            $paths[] = $matches[1];
        }

        return array_values(array_unique($paths));
    }
}