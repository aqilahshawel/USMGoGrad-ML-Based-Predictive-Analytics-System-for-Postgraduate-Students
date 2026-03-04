<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class MilestoneController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole('student')) {
            $milestones = Milestone::where('student_id', $user->id)->get();
        } elseif ($user->hasRole('lecturer')) {
            $milestones = Milestone::where('lecturer_id', $user->id)->get();
        } else {
            abort(403, 'Unauthorized');
        }

        return view('milestones.index', compact('milestones'));
    }

    public function create()
    {
        return view('milestones.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);
    
        $student = Auth::user();
        $lecturerId = $student->supervisor_id; // Ensure this is not null
    
        // Check if the student has a supervisor assigned
        if (!$lecturerId) {
            return back()->with('error', 'You must be assigned to a lecturer first.');
        }
    
        Milestone::create([
            'student_id' => $student->id,
            'lecturer_id' => $lecturerId,
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
        ]);
    
        return redirect()->route('milestones.index')->with('success', 'Milestone proposed successfully.');
    }
    

    public function edit(Milestone $milestone)
    {
        $user = Auth::user();

        if (
            ($user->hasRole('student') && $milestone->student_id !== $user->id) ||
            ($user->hasRole('lecturer') && $milestone->lecturer_id !== $user->id)
        ) {
            abort(403);
        }

        return view('milestones.edit', compact('milestone'));
    }

    public function update(Request $request, Milestone $milestone)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'status' => 'in:pending,approved,finalized'
        ]);

        $milestone->update($request->only(['title', 'description', 'due_date', 'status']));

        return redirect()->route('milestones.index')->with('success', 'Milestone updated.');
    }
}
