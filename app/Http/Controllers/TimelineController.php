<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Timeline;
use App\Models\User;

class TimelineController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
    
        $students = collect(); // Default empty collection
        $timelines = collect(); // Default empty collection
        $selectedStudentId = null; // Ensure it's always defined
    
        if ($user) {
            if ($user->hasRole('student')) {
                // Students should only see their own timeline
                $timelines = Timeline::where('user_id', $user->id)->get();
            } elseif ($user->hasRole('lecturer')) {
                // Get students under this lecturer's supervision
                $students = User::where('supervisor_id', $user->id)->get();
    
                // Check if the lecturer selected a student from dropdown (if applicable)
                $selectedStudentId = $request->input('student_id', $students->first()->id ?? null);
    
                // Fetch timelines of the selected student
                if ($selectedStudentId) {
                    $timelines = Timeline::where('user_id', $selectedStudentId)->get();
                }
            } else {
                // Admins see all timelines
                $timelines = Timeline::all();
            }
        }
    
        return view('timeline.index', compact('timelines', 'students', 'selectedStudentId'));
    }
    
    
    

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'event_date' => 'required|date',
        ]);

        Timeline::create([
            'title' => $request->title,
            'event_date' => $request->event_date,
            'completed' => $request->has('completed'),
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('timeline.index')->with('success', 'Timeline event added.');
    }

    public function update(Request $request, Timeline $timeline)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'event_date' => 'required|date',
        ]);

        $timeline->update([
            'title' => $request->title,
            'event_date' => $request->event_date,
            'completed' => $request->has('completed'),
        ]);

        return redirect()->route('timeline.index')->with('success', 'Timeline event updated.');
    }

    public function destroy(Timeline $timeline)
    {
        $timeline->delete();
        return redirect()->route('timeline.index')->with('success', 'Timeline event deleted.');
    }
}
