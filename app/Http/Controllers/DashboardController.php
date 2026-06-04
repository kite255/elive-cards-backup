<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_events' => Event::count(),
            'total_categories' => EventCategory::count(),
            'total_users' => User::count(),
            'recent_events' => Event::latest()->take(5)->get(),
        ];
        
        return view('venecardDashboard.dashboard', compact('stats'));
    }
}
