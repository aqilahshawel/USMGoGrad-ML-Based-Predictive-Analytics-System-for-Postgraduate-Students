<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Timeline;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        $user = auth()->user();
    
        $students = collect(); // Empty collection for lecturers
        $timelines = collect(); // Default empty collection for all roles
        $milestones = collect(); // Make sure it's not all()
    
        if ($user) {
            if ($user->hasRole('student')) {
                // Students see only their own timelines and milestones
                $timelines = $user->timelines;
                $milestones = Milestone::where('student_id', $user->id)->get();
    
            } elseif ($user->hasRole('lecturer')) {
                // Get students supervised by this lecturer
                $students = User::where('supervisor_id', $user->id)->get();
    
                // Fetch timelines and milestones of students under this lecturer
                $timelines = Timeline::whereIn('user_id', $students->pluck('id'))->get();
                $milestones = Milestone::whereIn('student_id', $students->pluck('id'))->get();
    
            } elseif ($user->hasRole('admin')) {
                // Admins see all timelines, but not milestones anymore
                $timelines = Timeline::all();
                // If you want admin to view all milestones too, uncomment below:
                // $milestones = Milestone::all();
            }
        }
    
        return view('home', compact('milestones', 'timelines', 'students'));
    }
    
}
