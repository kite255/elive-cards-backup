<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventGuest;
use App\Models\GuestPdf;
use Illuminate\Http\Request;

class DownloadCardController extends Controller
{
   public function index($id){
    try {
        $guest_invitation_code = strrev($id);
        $guest = EventGuest::where('invitation_code', $guest_invitation_code)->first();

        if (!$guest) {
            dd('Guest not found.');
        }

        $event = Event::find($guest->event_id);
        if (!$event) {
            dd('Event not found.');
        }

        $event_code = $event->code;

        $guest_pdf = GuestPdf::where('event_guests_id', $guest->id)->first();
        if (!$guest_pdf) {
            dd('Guest card not found.');
        }

        $card_to_download = $guest_pdf->pdf_name;
        $relativePath = "storage/cards/PDFCards/{$event_code}/{$card_to_download}";
        $fullPath = public_path($relativePath,$guest->guest_name.'.jpg');

        if (!file_exists($fullPath)) {
            dd('Card file not found on server.');
        }

     $guestName = $guest->guest_name; // assuming you have a "name" column
     $extension = pathinfo($card_to_download, PATHINFO_EXTENSION);

     // Create a clean, safe filename like: john_doe_card.pdf
      $downloadFilename = str_replace(' ', '_', strtolower($guestName)) . '.' . $extension;

return response()->download($fullPath, $downloadFilename);





      //  return response()->download($fullPath);

    } catch (\Exception $e) {
        dd('Download failed due to an unexpected error.');
    }
}

}
