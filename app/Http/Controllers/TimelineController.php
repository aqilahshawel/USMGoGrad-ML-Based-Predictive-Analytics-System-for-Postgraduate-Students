<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentCheckpoint;
use App\Models\Timeline;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Services\CandidatureTimelineService;
use App\Services\OverdueCheckpointNotificationService;
use App\Services\StudentCheckpointPredictionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TimelineController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $timelineService = app(CandidatureTimelineService::class);
        $overdueNotificationService = app(OverdueCheckpointNotificationService::class);
        $predictionService = app(StudentCheckpointPredictionService::class);
    
        $students = collect(); 
        $timelines = collect(); 
        $selectedStudentId = null; 
        $targetUser = null;
    
        if ($user) {
            if ($user->hasRole('student')) {
                $targetUser = $user;
                try {
                    if ($user->start_date) {
                        $timelineService->syncForStudent($user);
                    }
                } catch (\Throwable $e) {
                }

                $timelines = Timeline::query()
                    ->where('user_id', $user->id)
                    ->where(function ($q) {
                        $q->where('status', 'approved')
                            ->orWhere('proposed_by_user_id', Auth::id());
                    })
                    ->orderBy('event_date')
                    ->get();
            } elseif ($user->hasRole('lecturer')) {
                $students = User::where('supervisor_id', $user->id)->get();
    
                // Check if lecturer selected a student from dropdown (if applicable)
                $selectedStudentId = $request->input('student_id', $students->first()->id ?? null);
    
                // Fetch timelines of the selected student (approved + pending requests)
                if ($selectedStudentId) {
                    try {
                        $student = User::find($selectedStudentId);
                        if ($student && (int) $student->supervisor_id === (int) $user->id && $student->start_date) {
                            $targetUser = $student;
                            $timelineService->syncForStudent($student);
                        }
                    } catch (\Throwable $e) {
                    }

                    $timelines = Timeline::query()
                        ->where('user_id', $selectedStudentId)
                        ->orderBy('event_date')
                        ->get();
                }
            } else {
                $timelines = Timeline::query()->orderBy('event_date')->get();
            }
        }
    
        $progressSnapshot = null;
        $checkpoint = null;
        $predictionState = 'unavailable';
        $predictionWarning = session('prediction_warning');

        if ($user->hasRole('student') && $user->start_date) {
            $targetUser = $user;
        } elseif ($user->hasRole('lecturer') && $selectedStudentId && ! $targetUser) {
            $s = User::find($selectedStudentId);
            if ($s) {
                $targetUser = $s;
            }
        }

        if ($targetUser && $targetUser->start_date) {
            $checkpoint = StudentCheckpoint::firstOrCreate(['user_id' => $targetUser->id]);

            try {
                $progressSnapshot = $timelineService->progressSnapshot(
                    $targetUser->start_date,
                    $targetUser->study_mode ?: 'full_time',
                    null,
                    $checkpoint
                );
            } catch (\Throwable $e) {
            }

            try {
                $overdueNotificationService->dispatchForStudent($targetUser->fresh('supervisor'));
            } catch (\Throwable $e) {
                // Ignore notification failures to keep timeline page available
            }
        }

        $missingPrerequisites = $targetUser ? $predictionService->missingPrerequisites($targetUser) : [];

        if ($targetUser && $checkpoint) {
            if ($missingPrerequisites !== []) {
                $predictionState = 'unavailable_prerequisites';
            } else {
                if ($predictionService->shouldRefreshOnProfileView($checkpoint)) {
                    try {
                        $predictionService->predictForStudent($targetUser->fresh('checkpoint'));
                        $targetUser = $targetUser->fresh('checkpoint');
                        $checkpoint = $targetUser->checkpoint ?: $checkpoint;

                        $timelineService->syncForStudent($targetUser);
                        $timelines = Timeline::query()
                            ->where('user_id', $targetUser->id)
                            ->orderBy('event_date')
                            ->get();

                        $progressSnapshot = $timelineService->progressSnapshot(
                            $targetUser->start_date,
                            $targetUser->study_mode ?: 'full_time',
                            null,
                            $checkpoint
                        );
                    } catch (\Throwable $e) {
                        $predictionWarning = $e->getMessage();
                    }
                }

                if (! is_null($checkpoint->got_probability)) {
                    $predictionState = 'ready';
                } elseif ($predictionWarning) {
                    $predictionState = 'refresh_failed';
                } else {
                    $predictionState = 'refresh_failed';
                    $predictionWarning = 'ML prediction could not be refreshed right now. Please try again later.';
                }
            }
        }

        return view('timeline.index', compact(
            'timelines',
            'students',
            'selectedStudentId',
            'progressSnapshot',
            'targetUser',
            'checkpoint',
            'missingPrerequisites',
            'predictionState',
            'predictionWarning'
        ));
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

        // If  changed a generator-backed checkpoint, lock it so sync won't overwrite
        if ($timeline->system_key && $timeline->is_system_generated) {
            $timeline->is_generator_locked = true;
            $timeline->save();
        }

        return redirect()->route('timeline.index')->with('success', 'Timeline event updated.');
    }

    public function updateCheckpoint(
        Request $request,
        StudentCheckpointPredictionService $predictionService,
        CandidatureTimelineService $timelineService,
        OverdueCheckpointNotificationService $overdueNotificationService
    )
    {
        $student = Auth::user();
        if (! $student || ! $student->hasRole('student')) {
            abort(403);
        }

        $validated = $request->validate([
            'ekc500_admin_approved' => ['nullable', 'boolean'],
            'proposal_review_date' => ['nullable', 'date'],
            'research_review_date' => ['nullable', 'date'],
            'nos_date' => ['nullable', 'date'],
            'final_draft_submission_date' => ['nullable', 'date'],
            'viva_date' => ['nullable', 'date'],
        ]);

        $validated['ekc500_admin_approved'] = (bool) $request->boolean('ekc500_admin_approved');

        $checkpoint = StudentCheckpoint::firstOrCreate(['user_id' => $student->id]);
        $this->validateCheckpointSequence(array_merge($checkpoint->toArray(), $validated));

        $checkpoint->fill($validated)->save();

        $warning = null;

        try {
            $predictionService->predictForStudent($student->fresh('checkpoint'));
        } catch (\Throwable $e) {
            Log::warning('Checkpoint update prediction refresh failed.', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
            $warning = $e->getMessage();
        }

        try {
            if ($student->start_date) {
                $timelineService->syncForStudent($student->fresh('checkpoint'));
            }
        } catch (\Throwable $e) {
            Log::warning('Checkpoint update timeline sync failed.', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
            $warning = $warning ? $warning . ' ' . $e->getMessage() : $e->getMessage();
        }

        try {
            $overdueNotificationService->dispatchForStudent($student->fresh('supervisor'));
        } catch (\Throwable $e) {
            Log::warning('Checkpoint update overdue dispatch failed.', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
            $warning = $warning ? $warning . ' ' . $e->getMessage() : $e->getMessage();
        }

        return redirect()
            ->route('timeline.index', ['tab' => 'checkpoint'])
            ->with('success', 'Checkpoint status updated successfully.')
            ->with('prediction_warning', $warning);
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

    private function validateCheckpointSequence(array $data): void
    {
        $sequence = [
            'ekc500_admin_approved' => null,
            'proposal_review_date' => 'ekc500_admin_approved',
            'research_review_date' => 'proposal_review_date',
            'nos_date' => 'research_review_date',
            'final_draft_submission_date' => 'nos_date',
            'viva_date' => 'final_draft_submission_date',
        ];

        foreach ($sequence as $field => $requiredField) {
            $value = $data[$field] ?? null;
            if (! $value || ! $requiredField) {
                continue;
            }

            $requiredValue = $data[$requiredField] ?? null;
            if (! $requiredValue) {
                throw ValidationException::withMessages([
                    $field => 'Complete the previous checkpoint before updating this one.',
                ]);
            }

            if ($requiredField === 'ekc500_admin_approved' && $requiredValue !== true) {
                throw ValidationException::withMessages([
                    $field => 'EKC500 admin approval must be checked before updating this checkpoint.',
                ]);
            }

            if ($requiredField === 'ekc500_admin_approved') {
                continue;
            }

            if ((string) $value < (string) $requiredValue) {
                throw ValidationException::withMessages([
                    $field => 'This checkpoint date cannot be earlier than the previous checkpoint date.',
                ]);
            }
        }
    }
}
