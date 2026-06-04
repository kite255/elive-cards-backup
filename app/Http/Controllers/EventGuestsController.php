<?php

namespace App\Http\Controllers;

use App\Imports\eventGuestsImport;
use App\Jobs\CreateCard;
use App\Jobs\CreateQrcode;
use App\Models\Event;
use App\Models\GuestQrcode;
use App\Models\EventGuest;
use App\Models\GuestPdf;
use App\Models\Qrcode as ModelsQrcode;
use App\Models\SendWhatsappCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\File;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use App\Jobs\CreateQrcodeBatch;
use App\Jobs\CreateCardBatch;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Jobs\ProcessGuestBatch;
use RealRashid\SweetAlert\Facades\Alert;

class EventGuestsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */


    public function store(Request $request)
    {
        try {
            $request->validate([
                'guestExcelFile' => 'required'
            ]);

            $guestExcelData = $request->file('guestExcelFile');
            $user_id = Auth::id();
            $event_id = $request->event_id;

            Excel::import(new eventGuestsImport($user_id, $event_id), $guestExcelData);

            Alert::success('Excel file uploaded successfully', 'cards creation on progress');
            return redirect()->back();
        } catch (\Exception $e) {
            Alert::error('Error', 'make sure the excel file is in the correct format');
            return redirect()->back();
        }

    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
     public function edit(string $id)
    {
        $id = decrypt($id);
        $guest = EventGuest::find($id);
        return view('venecardDashboard.eventCard.layoutSections.editguest', compact('guest'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $guest = EventGuest::find($id);
        $guest->guest_phone = $request->guest_phone;
        $guest->save();
        Alert::success($guest->guest_name, ' has been updated successfully');
        return redirect()->route('events.show', encrypt($guest->event_id));
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $id = decrypt($id);
        $guest = EventGuest::find($id);
        $guest->delete();
        Alert::success($guest->guest_name, ' has been deleted successfully');
        return redirect()->route('events.show', encrypt($guest->event_id));
    }


    public function addSingleGuest(Request $request, $eventId)
    {
        try {
            $request->validate([
                'guestName' => 'required',
                'guestPhone' => 'required|numeric|digits:10',
                'cardType' => 'required',
                'groupSize' => 'required_if:cardType,GROUP',
                 'note' => 'nullable',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Alert::error('Validation Error', $e->getMessage());
            return redirect()->back();
        }

        $userId = Auth::id();
        $event_id = decrypt($eventId);
        $event = Event::find($event_id);
        if(!$event){
            Alert::error('Error', 'Event not found');
            return redirect()->back();
        }
        $guest = new EventGuest();
        $guest->user_id = $userId;
        $guest->event_id = $event_id;
        $guest->guest_name = $request->guestName;
        $guest->guest_phone = ltrim($request->guestPhone, '0');
        $guest->card_type = $request->cardType === 'GROUP' ?  'WATU ' .$request->groupSize : $request->cardType;
        $guest->note = $request->note;

        // Generate a unique invitation code
        do {
            $generatedCode = rand(100000, 999999);
            $exists = EventGuest::where('event_id', $event_id)
                               ->where('invitation_code', $generatedCode)
                               ->exists();
        } while ($exists);
        $guest->invitation_code = $generatedCode;
        $guest->save();
        Alert::success( $request->guestName , ' has been added successfully');
        return redirect()->back();
    }
}
