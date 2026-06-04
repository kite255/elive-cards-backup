<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventSMSReminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class EventSMSReminderController extends Controller
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
            Alert::error('Error', 'Bronze event is not allowed to set reminder SMS.');
            return redirect()->back();
        }

        try {
            $request->validate([
                'SMS_reminder' => 'required|string|max:255',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Alert::error('Validation Error', 'Please enter the reminder SMS message');
            return redirect()->back()->withInput();
        }

        EventSMSReminder::updateOrCreate(
            [
                'event_id' => $eventId,
            ],
            [
                'user_id' => Auth::id(),
                'SMS_reminder' => $request->SMS_reminder,
            ]
        );

        Alert::success('Successfully Saved', 'Reminder SMS saved successfully');

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
            Alert::error('Error', 'Bronze event is not allowed to set reminder SMS.');
            return redirect()->back();
        }

        try {
            $request->validate([
                'SMS_reminder' => 'required|string|max:255',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Alert::error('Validation Error', 'Please enter the reminder SMS message');
            return redirect()->back()->withInput();
        }

        EventSMSReminder::updateOrCreate(
            [
                'event_id' => $eventId,
            ],
            [
                'user_id' => Auth::id(),
                'SMS_reminder' => $request->SMS_reminder,
            ]
        );

        Alert::success('Successfully Updated', 'Reminder SMS updated successfully');

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