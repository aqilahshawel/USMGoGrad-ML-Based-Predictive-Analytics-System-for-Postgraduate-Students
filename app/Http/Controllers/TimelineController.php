<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Timeline;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Services\CandidatureTimelineService;

class TimelineController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $timelineService = app(CandidatureTimelineService::class);
    
        $students = collect(); // Default empty collection
        $timelines = collect(); // Default empty collection
        $selectedStudentId = null; // Ensure it's always defined
    
        if ($user) {
            if ($user->hasRole('student')) {
                // Ensure default professional checkpoints exist (best-effort)
                try {
                    if ($user->start_date && $user->study_mode) {
                        $timelineService->syncForStudent($user);
                    }
                } catch (\Throwable $e) {
                    // Ignore sync failures (e.g., migrations not run yet)
                }

                // Students: see their approved items + their pending/rejected requests
                $timelines = Timeline::query()
                    ->where('user_id', $user->id)
                    ->where(function ($q) {
                        $q->where('status', 'approved')
                            ->orWhere('proposed_by_user_id', Auth::id());
                    })
                    ->orderBy('event_date')
                    ->get();
            } elseif ($user->hasRole('lecturer')) {
                // Get students under this lecturer's supervision
                $students = User::where('supervisor_id', $user->id)->get();
    
                // Check if the lecturer selected a student from dropdown (if applicable)
                $selectedStudentId = $request->input('student_id', $students->first()->id ?? null);
    
                // Fetch timelines of the selected student (approved + pending requests)
                if ($selectedStudentId) {
                    // Best-effort auto sync when lecturer views a student
                    try {
                        $student = User::find($selectedStudentId);
                        if ($student && (int) $student->supervisor_id === (int) $user->id && $student->start_date && $student->study_mode) {
                            $timelineService->syncForStudent($student);
                        }
                    } catch (\Throwable $e) {
                        // Ignore sync failures
                    }

                    $timelines = Timeline::query()
                        ->where('user_id', $selectedStudentId)
                        ->orderBy('event_date')
                        ->get();
                }
            } else {
                // Admins see all timelines
                $timelines = Timeline::query()->orderBy('event_date')->get();
            }
        }
    
        // Build progress snapshot for the target student
        $progressSnapshot = null;
        $targetUser = null;

        if ($user->hasRole('student') && $user->start_date && $user->study_mode) {
            $targetUser = $user;
        } elseif ($user->hasRole('lecturer') && $selectedStudentId) {
            $s = User::find($selectedStudentId);
            if ($s && $s->start_date && $s->study_mode) {
                $targetUser = $s;
            }
        }

        if ($targetUser) {
            try {
                $progressSnapshot = $timelineService->progressSnapshot($targetUser->start_date, $targetUser->study_mode);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return view('timeline.index', compact('timelines', 'students', 'selectedStudentId', 'progressSnapshot'));
    }
    
    
    

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'event_date' => 'required|date',
        ]);

        $user = Auth::user();

        // Professional timeline: student-created items require supervisor approval
        $status = $user->hasRole('student') ? 'pending' : 'approved';
        $reviewedBy = $status === 'approved' ? $user->id : null;
        $reviewedAt = $status === 'approved' ? now() : null;

        Timeline::create([
            'title' => $request->title,
            'event_date' => $request->event_date,
            'completed' => $request->has('completed'),
            'user_id' => $user->id,
            'status' => $status,
            'proposed_by_user_id' => $user->id,
            'reviewed_by_user_id' => $reviewedBy,
            'reviewed_at' => $reviewedAt,
            'system_key' => $user->hasRole('student') ? ('proposal:' . Str::uuid()) : null,
            'is_system_generated' => false,
        ]);

        $message = $status === 'pending'
            ? 'Timeline event submitted for supervisor approval.'
            : 'Timeline event added.';

        return redirect()->route('timeline.index')->with('success', $message);
    }

    public function update(Request $request, Timeline $timeline)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'event_date' => 'required|date',
        ]);

        $user = Auth::user();

        // Students cannot directly edit professional timeline items; they submit a change request.
        if ($user->hasRole('student')) {
            if ((int) $timeline->user_id !== (int) $user->id) {
                abort(403);
            }

            Timeline::create([
                'title' => $request->title,
                'event_date' => $request->event_date,
                'completed' => $request->has('completed'),
                'user_id' => $timeline->user_id,
                'status' => 'pending',
                'proposed_by_user_id' => $user->id,
                'replaces_timeline_id' => $timeline->id,
                'system_key' => 'proposal:' . Str::uuid(),
                'is_system_generated' => false,
            ]);

            return redirect()->route('timeline.index')->with('success', 'Change request submitted for supervisor approval.');
        }

        // Lecturer/admin: can update directly (lecturer only for supervisees)
        if ($user->hasRole('lecturer')) {
            $student = User::find($timeline->user_id);
            if (! $student || (int) $student->supervisor_id !== (int) $user->id) {
                abort(403);
            }
        }

        $timeline->update([
            'title' => $request->title,
            'event_date' => $request->event_date,
            'completed' => $request->has('completed'),
        ]);

        // If they changed a generator-backed checkpoint, lock it so sync won't overwrite it
        if ($timeline->system_key && $timeline->is_system_generated) {
            $timeline->is_generator_locked = true;
            $timeline->save();
        }

        return redirect()->route('timeline.index')->with('success', 'Timeline event updated.');
    }

    public function destroy(Timeline $timeline)
    {
        $user = Auth::user();

        // Students should not delete professional timeline items directly.
        if ($user->hasRole('student')) {
            abort(403, 'Timeline deletions require supervisor approval.');
        }

        if ($user->hasRole('lecturer')) {
            $student = User::find($timeline->user_id);
            if (! $student || (int) $student->supervisor_id !== (int) $user->id) {
                abort(403);
            }
        }

        $timeline->delete();
        return redirect()->route('timeline.index')->with('success', 'Timeline event deleted.');
    }

    public function approve(Timeline $timeline)
    {
        $lecturer = Auth::user();
        if (! $lecturer || ! $lecturer->hasRole('lecturer')) {
            abort(403);
        }

        $student = User::find($timeline->user_id);
        if (! $student || (int) $student->supervisor_id !== (int) $lecturer->id) {
            abort(403);
        }

        if ($timeline->status !== 'pending') {
            return redirect()->route('timeline.index')->with('success', 'Nothing to approve.');
        }

        // If this is an edit proposal, apply to the original then delete the proposal row.
        if ($timeline->replaces_timeline_id) {
            $original = Timeline::find($timeline->replaces_timeline_id);
            if ($original && (int) $original->user_id === (int) $timeline->user_id) {
                $original->title = $timeline->title;
                $original->event_date = $timeline->event_date;
                $original->completed = $timeline->completed;
                $original->status = 'approved';
                $original->reviewed_by_user_id = $lecturer->id;
                $original->reviewed_at = now();

                // Prevent generator sync from overwriting supervisor-approved edits
                if ($original->system_key && $original->is_system_generated) {
                    $original->is_generator_locked = true;
                }

                $original->save();
            }

            $timeline->reviewed_by_user_id = $lecturer->id;
            $timeline->reviewed_at = now();
            $timeline->status = 'approved';
            $timeline->save();

            // Keep audit? For now, remove proposal after applying.
            $timeline->delete();

            return redirect()->route('timeline.index')->with('success', 'Change request approved and applied.');
        }

        // New pending item: approve in-place
        $timeline->status = 'approved';
        $timeline->reviewed_by_user_id = $lecturer->id;
        $timeline->reviewed_at = now();
        $timeline->save();

        return redirect()->route('timeline.index')->with('success', 'Timeline event approved.');
    }

    public function reject(Timeline $timeline)
    {
        $lecturer = Auth::user();
        if (! $lecturer || ! $lecturer->hasRole('lecturer')) {
            abort(403);
        }

        $student = User::find($timeline->user_id);
        if (! $student || (int) $student->supervisor_id !== (int) $lecturer->id) {
            abort(403);
        }

        if ($timeline->status !== 'pending') {
            return redirect()->route('timeline.index')->with('success', 'Nothing to reject.');
        }

        $timeline->status = 'rejected';
        $timeline->reviewed_by_user_id = $lecturer->id;
        $timeline->reviewed_at = now();
        $timeline->save();

        return redirect()->route('timeline.index')->with('success', 'Timeline request rejected.');
    }
}
