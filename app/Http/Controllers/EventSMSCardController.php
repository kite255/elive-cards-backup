<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventSMSCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class EventSMSCardController extends Controller
{
    public function index()
    {
        //
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $eventId = $this->resolveEventId($request->event_id);

        if (! $eventId) {
            Alert::error('Error', 'Event is invalid.');
            return redirect()->back();
        }

        $event = Event::find($eventId);

        if (! $event) {
            Alert::error('Error', 'Event is invalid.');
            return redirect()->back();
        }

        try {
            $request->validate([
                'SMS_card' => 'required|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Alert::error('Validation Error', 'Please enter the SMS card message');
            return redirect()->back()->withInput();
        }

        EventSMSCard::updateOrCreate(
            [
                'event_id' => $eventId,
            ],
            [
                'user_id' => Auth::id(),
                'SMS_card' => $request->SMS_card,
            ]
        );

        Alert::success('Inserted successfully', 'Message card successfully inserted');

        return redirect()->back();
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        $eventId = $this->resolveEventId($id);

        if (! $eventId) {
            Alert::error('Error', 'Event is invalid.');
            return redirect()->back();
        }

        $event = Event::find($eventId);

        if (! $event) {
            Alert::error('Error', 'Event is invalid.');
            return redirect()->back();
        }

        try {
            $request->validate([
                'SMS_card' => 'required|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Alert::error('Validation Error', 'Please enter the SMS card message');
            return redirect()->back()->withInput();
        }

        EventSMSCard::updateOrCreate(
            [
                'event_id' => $eventId,
            ],
            [
                'user_id' => Auth::id(),
                'SMS_card' => $request->SMS_card,
            ]
        );

        Alert::success('Updated successfully', 'SMS card updated successfully');

        return redirect()->back();
    }

    public function destroy(string $id)
    {
        //
    }

    private function resolveEventId($value): ?int
    {
        if (! $value) {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        try {
            return (int) decrypt($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}