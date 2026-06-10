<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventGuest;
use App\Models\GuestPdf;
use Illuminate\Support\Facades\File;

class DownloadCardController extends Controller
{
    public function index($id)
    {
        try {
            /*
             * Some links may send reversed invitation code.
             * Example: original 879473 may be sent as 374978.
             * So we check both normal and reversed code.
             */
            $normalCode = $id;
            $reversedCode = strrev($id);

            $guest = EventGuest::where('invitation_code', $normalCode)
                ->orWhere('invitation_code', $reversedCode)
                ->first();

            if (! $guest) {
                return response('Guest not found for invitation code: ' . $id, 404);
            }

            $event = Event::find($guest->event_id);

            if (! $event) {
                return response('Event not found for this guest.', 404);
            }

            $guestPdf = GuestPdf::where('event_guests_id', $guest->id)->first();

            if (! $guestPdf) {
                return response('Guest card not found. Please generate the guest card first.', 404);
            }

            $cardFileName = $guestPdf->pdf_name;

            if (! $cardFileName) {
                return response('Guest card file name is empty.', 404);
            }

            /*
             * Expected file location:
             * public/storage/cards/PDFCards/{event_code}/{pdf_name}
             */
            $eventCode = $event->code;

            $relativePath = 'storage/cards/PDFCards/' . $eventCode . '/' . $cardFileName;
            $fullPath = public_path($relativePath);

            /*
             * Fallback: sometimes files are stored directly in storage/app/public
             */
            if (! File::exists($fullPath)) {
                $storagePath = storage_path('app/public/cards/PDFCards/' . $eventCode . '/' . $cardFileName);

                if (File::exists($storagePath)) {
                    $fullPath = $storagePath;
                }
            }

            if (! File::exists($fullPath)) {
                return response(
                    'Card file not found on server. Checked path: ' . $relativePath,
                    404
                );
            }

            $guestName = $guest->guest_name ?: 'guest_card';
            $extension = pathinfo($cardFileName, PATHINFO_EXTENSION) ?: 'jpg';

            $safeGuestName = preg_replace('/[^A-Za-z0-9_\-]/', '_', strtolower($guestName));
            $downloadFileName = $safeGuestName . '_card.' . $extension;

            return response()->download($fullPath, $downloadFileName);
        } catch (\Throwable $e) {
            return response(
                'Download failed: ' . $e->getMessage(),
                500
            );
        }
    }
}