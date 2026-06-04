<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventGuest;
use Illuminate\Http\Request;

class InviteesController extends Controller
{
    public function inviteesStatistics($id)
    {
        $event = Event::where('code', $id)->first();
        if (!$event) {
            return view('scan-card.event-not-found');
        }
       
       $statistics = [
           'totalCards' => EventGuest::where('event_id', $event->id)->count(),
           'totalSingleCards' => EventGuest::where('event_id', $event->id)->where('card_type', 'SINGLE')->count(),
           'totalDoubleCards' => EventGuest::where('event_id', $event->id)->where('card_type', 'DOUBLE')->count(),
           'totalFamilyCards' => EventGuest::where('event_id', $event->id)
                                   ->whereNotIn('card_type', ['SINGLE', 'DOUBLE'])
                                   ->count(),
           'totalScannedCards' => EventGuest::where('event_id', $event->id)->where('scanning_times', '!=', 0)->count()
       ];

       $statistics['totalNotScannedCards'] = $statistics['totalCards'] - $statistics['totalScannedCards'];
       $statistics['totalInviteeScanned'] = EventGuest::where('event_id', $event->id)->sum('scanning_times');
       $statistics['totalInviteeNotScanned'] = $statistics['totalCards'] - $statistics['totalInviteeScanned'];

       return view('scan-card.invitees-statistics', compact('event', 'statistics'));
    }

    public function inviteesList($id)
    {
       
        $event = Event::where('code', $id)->first();
        if (!$event) {
            return view('scan-card.event-not-found');
        }

        $invitees = EventGuest::where('event_id', $event->id)->get();

        // Default card type to 1 if no invitees found
        $card_type = 1;

        // If invitees exist, get card type from first invitee
        if ($invitees->isNotEmpty()) {
            $firstInvitee = $invitees->first();
            $cardType = strtoupper($firstInvitee->card_type);

            if ($cardType === 'SINGLE') {
                $card_type = 1;
            } elseif ($cardType === 'DOUBLE') {
                $card_type = 2;
            } else {
                preg_match('/(\d+)/', $cardType, $matches);
                $card_type = isset($matches[1]) ? (int)$matches[1] : 3;
            }
        }




        return view('scan-card.invitees-list', compact('event', 'invitees', 'card_type'));
    }



}
