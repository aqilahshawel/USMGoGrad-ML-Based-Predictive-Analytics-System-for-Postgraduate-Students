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
            $studentIds = User::query()
                ->where('supervisor_id', $user->id)
                ->pluck('id');

            $milestones = Milestone::whereIn('student_id', $studentIds)->get();
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

        Milestone::create([
            'student_id' => $student->id,
            // Personal milestones are student-owned; keep lecturer_id only for convenient lecturer filtering.
            'lecturer_id' => $student->supervisor_id,
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'status' => 'pending',
        ]);
    
        return redirect()->route('milestones.index')->with('success', 'Milestone created successfully.');
    }
    

    public function edit(Milestone $milestone)
    {
        $user = Auth::user();

        if (
            ($user->hasRole('student') && $milestone->student_id !== $user->id) ||
            ($user->hasRole('lecturer'))
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

        $user = Auth::user();
        if (! $user->hasRole('student') || (int) $milestone->student_id !== (int) $user->id) {
            abort(403);
        }

        $milestone->update($request->only(['title', 'description', 'due_date', 'status']));

        return redirect()->route('milestones.index')->with('success', 'Milestone updated.');
    }
}
