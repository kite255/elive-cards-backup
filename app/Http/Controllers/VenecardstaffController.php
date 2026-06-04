<?php

namespace App\Http\Controllers;

use App\Models\Venecardstaff;
use Illuminate\Http\Request;

class VenecardstaffController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $venecardStaffs = Venecardstaff::paginate(4);
        return view('venecardDashboard.venecardstaff.index', compact('venecardStaffs'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('venecardDashboard.venecardstaff.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
      'firstName' => 'required | alpha | min:4',
      'lastName'  => 'required | alpha | min:4',
      'phone'     => 'required | digits:10',
      'email'     => 'required | email'
        ]);
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        $addStaff = new Venecardstaff();
        $addStaff->first_name = strtoupper($request->firstName);
        $addStaff->last_name = strtoupper($request->lastName);
        $addStaff->phone = $request->phone;
        $addStaff->email = $request->email;
        $addStaff->region = $request->region;
        $addStaff->role = $request->role;
        $addStaff->password = $hashedPassword;
        $addStaff->save();
        return redirect()->route('venecardstaff.index');

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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
