<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventSMSWelcoming;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class EventSMSWelcomingController extends Controller
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

        if ($event->event_type == 'invitation' && $event->event_package == 'bronze') {
            Alert::error('Error', 'Bronze event is not allowed to set welcome SMS.');
            return redirect()->back();
        }

        try {
            $request->validate([
                'SMS_welcoming' => 'required|string|max:255',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Alert::error('Validation Error', 'Please enter the welcome SMS message');
            return redirect()->back()->withInput();
        }

        EventSMSWelcoming::updateOrCreate(
            [
                'event_id' => $eventId,
            ],
            [
                'user_id' => Auth::id(),
                'SMS_welcoming' => $request->SMS_welcoming,
            ]
        );

        Alert::success('Successfully Saved', 'Welcome SMS saved successfully');

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

        if ($event->event_type == 'invitation' && $event->event_package == 'bronze') {
            Alert::error('Error', 'Bronze event is not allowed to set welcome SMS.');
            return redirect()->back();
        }

        try {
            $request->validate([
                'SMS_welcoming' => 'required|string|max:255',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Alert::error('Validation Error', 'Please enter the welcome SMS message');
            return redirect()->back()->withInput();
        }

        EventSMSWelcoming::updateOrCreate(
            [
                'event_id' => $eventId,
            ],
            [
                'user_id' => Auth::id(),
                'SMS_welcoming' => $request->SMS_welcoming,
            ]
        );

        Alert::success('Successfully Updated', 'Welcome SMS updated successfully');

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