<?php

namespace App\Http\Controllers;

use App\Imports\eventGuestsImport;
use App\Models\Event;
use App\Models\EventGuest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use RealRashid\SweetAlert\Facades\Alert;

class EventGuestsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store guests from Excel file.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate(
                [
                    'guestExcelFile' => 'required|file|mimes:xlsx,xls,csv',
                    'event_id' => 'required',
                ],
                [
                    'guestExcelFile.required' => 'Please choose an Excel file.',
                    'guestExcelFile.file' => 'The uploaded file is invalid.',
                    'guestExcelFile.mimes' => 'The file must be xlsx, xls, or csv.',
                    'event_id.required' => 'Event is required.',
                ]
            );

            $guestExcelData = $request->file('guestExcelFile');
            $user_id = Auth::id();
            $event_id = $validated['event_id'];

            Excel::import(new eventGuestsImport($user_id, $event_id), $guestExcelData);

            Alert::success('Excel file uploaded successfully', 'Cards creation is in progress.');

            return redirect()->back();
        } catch (\Illuminate\Validation\ValidationException $e) {
            Alert::error('Validation Error', $e->validator->errors()->first());

            return redirect()
                ->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            Alert::error('Error', 'Make sure the Excel file is in the correct format.');

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
     * Show guest edit form.
     */
    public function edit(string $id)
    {
        $id = decrypt($id);

        $guest = EventGuest::findOrFail($id);

        return view('venecardDashboard.eventCard.layoutSections.editguest', compact('guest'));
    }

    /**
     * Update guest phone.
     */
    public function update(Request $request, string $id)
    {
        $id = decrypt($id);

        $validated = $request->validate(
            [
                'guest_phone' => 'required|numeric|digits:10',
            ],
            [
                'guest_phone.required' => 'Guest phone is required.',
                'guest_phone.numeric' => 'Guest phone must contain numbers only.',
                'guest_phone.digits' => 'Guest phone must be exactly 10 digits.',
            ]
        );

        $guest = EventGuest::findOrFail($id);

        $guest->guest_phone = ltrim($validated['guest_phone'], '0');
        $guest->save();

        Alert::success($guest->guest_name, 'has been updated successfully.');

        return redirect()->route('events.show', encrypt($guest->event_id));
    }

    /**
     * Delete guest.
     */
    public function destroy(string $id)
    {
        $id = decrypt($id);

        $guest = EventGuest::findOrFail($id);
        $eventId = $guest->event_id;
        $guestName = $guest->guest_name;

        $guest->delete();

        Alert::success($guestName, 'has been deleted successfully.');

        return redirect()->route('events.show', encrypt($eventId));
    }

    /**
     * Add one guest manually.
     */
    public function addSingleGuest(Request $request, $eventId)
    {
        try {
            $validated = $request->validate(
                [
                    'guestName' => 'required|string|max:255',
                    'guestPhone' => 'required|numeric|digits:10',
                    'cardType' => 'required|string|max:50',
                    'groupSize' => 'required_if:cardType,GROUP|nullable|numeric|min:1|max:100',
                    'note' => 'nullable|string|max:1000',
                ],
                [
                    'guestName.required' => 'Guest name is required.',
                    'guestPhone.required' => 'Guest phone is required.',
                    'guestPhone.numeric' => 'Guest phone must contain numbers only.',
                    'guestPhone.digits' => 'Guest phone must be exactly 10 digits.',
                    'cardType.required' => 'Card type is required.',
                    'groupSize.required_if' => 'Group size is required for group card.',
                    'groupSize.numeric' => 'Group size must be a number.',
                    'note.max' => 'Note must not exceed 1000 characters.',
                ]
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            Alert::error('Validation Error', $e->validator->errors()->first());

            return redirect()
                ->back()
                ->withErrors($e->validator)
                ->withInput();
        }

        $userId = Auth::id();
        $event_id = decrypt($eventId);

        $event = Event::find($event_id);

        if (! $event) {
            Alert::error('Error', 'Event not found.');

            return redirect()->back();
        }

        $guest = new EventGuest();
        $guest->user_id = $userId;
        $guest->event_id = $event_id;
        $guest->guest_name = $validated['guestName'];
        $guest->guest_phone = ltrim($validated['guestPhone'], '0');

        $guest->card_type = $validated['cardType'] === 'GROUP'
            ? 'WATU ' . ($validated['groupSize'] ?? 1)
            : $validated['cardType'];

        // Optional note
        $guest->note = $validated['note'] ?? null;

        // Generate unique invitation code per event
        do {
            $generatedCode = rand(100000, 999999);

            $exists = EventGuest::where('event_id', $event_id)
                ->where('invitation_code', $generatedCode)
                ->exists();
        } while ($exists);

        $guest->invitation_code = $generatedCode;

        $guest->save();

        Alert::success($validated['guestName'], 'has been added successfully.');

        return redirect()->back();
    }
}