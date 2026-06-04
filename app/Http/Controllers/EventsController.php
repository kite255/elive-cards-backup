<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventGuest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use RealRashid\SweetAlert\Facades\Alert;

class EventsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $eventCategories = EventCategory::all(); // Fetch all event categories

        $query = Event::query()->orderBy('created_at', 'desc');

        // Filter by user if authenticated
        if (Auth::check()) {
            $query->where('user_id', Auth::id());
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('event_categories_id', $request->category);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        $events = $query->paginate(10);

        return view('venecardDashboard.events.index', compact('events', 'eventCategories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $events = Event::paginate(5);
        return view('venecardDashboard.events.create', compact('events'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name'   => 'required ',
                'category' => 'required ',
                'place'    => 'required',
                'date'      => 'required',
                'event_type' => 'required|in:invitation,contribution',
                'contactName'    => 'nullable|string',
                'contactPhone'   => 'nullable|string',
                'email'         => 'nullable|email',
                'venue_map_location_link' => 'nullable|url'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Alert::error('Error', $e->validator->errors()->first());
            return redirect()->back()->withInput();
        }

        $event = new Event();
        $event->user_id = Auth::id();
        $event->event_categories_id = $request->category;
        $event->name = ucwords($request->name);
        $event->place = ucwords($request->place);
        $event->date = $request->date;
        $event->event_type = $request->event_type;
        $event->venue_map_location_link = $request->venue_map_location_link ? $request->venue_map_location_link : null;
        $event->contact_name = $request->contactName ? ucwords($request->contactName) : null;
        $event->contact_phone = $request->contactPhone ? ltrim($request->contactPhone, '0') : null;
        $event->email = $request->email ? strtolower($request->email) : null;
        $event->code = substr(str_shuffle('ABCDEF'), 0, 2).str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT); //generating random codes for event code
        $event->save();
        Alert::success($event->name, 'event created successfully');
        return redirect()->route('events.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(string $id)
    {
        try {
            $id = decrypt($id);
        } catch (\Exception $e) {
            return view('venecardDashboard.404');
        }
        $event = Event::findOrFail($id);
        if(!$event){
            return view('venecardDashboard.404', compact('id'));
        }
        $query = EventGuest::where('event_id', $id);

        // Sort by name
        if (request('sort_by')) {
            $query->orderBy('guest_name', request('sort_by'));
        }

        // Filter by card type
        if (request('card_type')) {
            $query->where('card_type', strtoupper(request('card_type')));
        }

        // Filter by channel (whatsapp/sms/both)
        if (request('channel')) {
            if (request('channel') === 'whatsapp') {
                $query->whereHas('sendwhatsappcard');
            } elseif (request('channel') === 'sms') {
                $query->whereHas('sendmessagecard');
            } elseif (request('channel') === 'both') {
                $query->whereHas('sendwhatsappcard')->whereHas('sendmessagecard');
            }
        }

        // Filter by message status
        if (request('status')) {
            if (request('status') === 'sent') {
                $query->whereHas('sendwhatsappcard', function($q) {
                    $q->where('delivery_status', 'sent');
                });
            } elseif (request('status') === 'failed') {
                $query->whereHas('sendwhatsappcard', function($q) {
                    $q->where('delivery_status', 'failed');
                });
            } elseif (request('status') === 'delivered') {
                $query->whereHas('sendwhatsappcard', function($q) {
                    $q->where('delivery_status', 'delivered');
                });
            } elseif (request('status') === 'read') {
                $query->whereHas('sendwhatsappcard', function($q) {
                    $q->where('delivery_status', 'read');
                });
            } elseif (request('status') === 'replied') {
                $query->whereHas('sendwhatsappcard', function($q) {
                    $q->whereNotNull('reply_message');
                });
            }
        }
        // Search by name or phone
        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('guest_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('guest_phone', 'LIKE', "%{$searchTerm}%");
            });
        }

        $guests = $query->paginate(50);
        return view('venecardDashboard.eventCard.show', compact('event', 'guests'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $id = decrypt($id);
        $event = Event::findOrFail($id);
        return view('venecardDashboard.events.edit', compact('event'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $id = decrypt($id);
        try {
            $request->validate([
                'name'   => 'required ',
                'category' => 'required ',
                'place'    => 'required',
                'date'      => 'required',
                'contactName'    => 'nullable|string',
                'contactPhone'   => 'nullable|string',
                'email'         => 'nullable|email',
                'venue_map_location_link' => 'nullable|url'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Alert::error('Error', $e->validator->errors()->first());
            return redirect()->back()->withInput();
        }

        $event = Event::findOrFail($id);
        $event->event_categories_id = $request->category;
        $event->name = ucwords($request->name);
        $event->place = ucwords($request->place);
        $event->date = $request->date;
        $event->venue_map_location_link = $request->venue_map_location_link ? $request->venue_map_location_link : null;
        $event->contact_name = $request->contactName ? ucwords($request->contactName) : null;
        $event->contact_phone = $request->contactPhone ? ltrim($request->contactPhone, '0') : null;
        $event->email = $request->email ? strtolower($request->email) : null;
        $event->save();

        Alert::success($event->name, 'event updated successfully');
        return redirect()->route('events.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $id = decrypt($id);
        $event = Event::findOrFail($id);
        $event->delete();
        Alert::success($event->name, 'event deleted successfully');
        return redirect()->route('events.index');
    }
}
