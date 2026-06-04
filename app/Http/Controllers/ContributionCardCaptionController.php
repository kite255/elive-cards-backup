<?php

namespace App\Http\Controllers;

use App\Models\ContributionCardCaption;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class ContributionCardCaptionController extends Controller
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $event_id = decrypt($request->event_id);
        $event = Event::where('id', $event_id)->first();
        if(!$event){
            Alert::error('Error', 'event is invalid');
            return redirect()->back();
        }
        try {
            $request->validate([
                'contribution_card_caption' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Alert::error('Validation Error', 'Please enter the caption for your card');
            return redirect()->back();
        }
        $contributionCardCaption = new ContributionCardCaption();
        $contributionCardCaption->user_id = Auth::user()->id;
        $contributionCardCaption->event_id = $event_id;
        $contributionCardCaption->caption = $request->contribution_card_caption;
        $contributionCardCaption->save();
        Alert::success('Successfully Saved', 'caption saved successfully');
        return redirect()->back();
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $event_id = decrypt($id);
        $event = Event::where('id', $event_id)->first();
        if(!$event){
            Alert::error('Error', 'event is invalid');
            return redirect()->back();
        }
      
        $contributionCardCaption = ContributionCardCaption::where('event_id', $event_id)->first();
        if(!$contributionCardCaption){
            Alert::error('Error', 'caption is invalid');
            return redirect()->back();
        }
        try {
            $request->validate([
                'contribution_card_caption' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Alert::error('Validation Error', 'Please enter the caption for your card');
            return redirect()->back();
        }
        $contributionCardCaption->caption = $request->contribution_card_caption;
        $contributionCardCaption->save();
        Alert::success('Successfully Updated', 'caption updated successfully');
        return redirect()->back();
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
