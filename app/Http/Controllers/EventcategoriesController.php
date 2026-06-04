<?php

namespace App\Http\Controllers;

use App\Models\EventCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use RealRashid\SweetAlert\Facades\Alert;



class EventcategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $eventCategories = EventCategory::paginate(10);

        return view('venecardDashboard.eventCategories.index', compact('eventCategories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('venecardDashboard.eventCategories.create');
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
                'title' => 'required | min:4 | max:20'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Alert::error('Error', $e->validator->errors()->first());
            return redirect()->back();
        }
        
        $eventCategory = new EventCategory();
        $eventCategory->title = ucwords($request->title);
        $eventCategory->user_id = Auth::id();
        $eventCategory->save();
        Alert::success($eventCategory->title, 'event category added to your list');
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $eventCategories = EventCategory::findOrFail($id);
        return view('venecardDashboard.eventCategories.edit', compact('eventCategories'));
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
        $id = Crypt::decrypt($id);
        $eventCategory = EventCategory::findOrFail($id);
        $request->validate([
            'title' => 'required | min:4 | max:20'
        ]);
        $eventCategory->title = ucwords($request->title);
        $eventCategory->save();
        Alert::success( $eventCategory->title, 'updated successfully');
        return redirect()->route('eventcategories.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
      
        $category = EventCategory::findOrFail($id);
        $category->delete();
        return redirect()->route('eventcategories.index');
    }

    public function trashed()
    { 
        $eventCategories = EventCategory::onlyTrashed()->paginate(3);
        return view('venecardDashboard.eventCategories.trashed', compact('eventCategories'));
    }

    public function forceDelete($id)
    {
        $category = EventCategory::onlyTrashed()->findOrFail($id);
        $category->forceDelete();
        Alert::success($category->title,'event category deleted successfully');
        return redirect()->route('eventcategories.index');
    }

    public function restore($id)
    {
        $id = Crypt::decrypt($id);
        $category = EventCategory::onlyTrashed()->findOrFail($id);
        $category->restore();
        Alert::success($category->title,'restored successfully');
        return redirect()->route('eventcategories.index');
    }
}
