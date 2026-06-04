<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventGuest;
use Illuminate\Http\Request;

class EventGuestTrackingController extends Controller
{
    public function index($id)
    {
       $event = Event::where('code', $id)->first();
       if (!$event) {
        return view('guest-tracking.event-not-found');
    }
       $invitees = EventGuest::where('event_id', $event->id)->get();
       $single_invitees = $invitees->where('card_type', 'SINGLE')->count();
       $double_invitees = $invitees->where('card_type', 'DOUBLE')->count();
       $individual_cards = $invitees->whereIn('card_type', ['SINGLE', 'DOUBLE'])->count();
       $group_invitees = $invitees->whereNotIn('card_type', ['SINGLE', 'DOUBLE'])->count();
       $total_cards = $invitees->count();
       $total_invitees = $double_invitees*2 + $single_invitees;

        return view('guest-tracking.invitation-updates', compact('event', 'invitees', 'single_invitees', 'double_invitees', 'individual_cards', 'total_cards', 'total_invitees', 'group_invitees'));

    }
}
