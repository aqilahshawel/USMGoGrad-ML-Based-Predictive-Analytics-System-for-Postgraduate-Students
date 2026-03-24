<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Timeline;
use App\Models\Milestone;
use App\Models\StudentCheckpoint;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Services\CandidatureTimelineService;
use App\Services\OverdueCheckpointNotificationService;
use App\Services\StudentCheckpointPredictionService;

class HomeController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $timelineService = app(CandidatureTimelineService::class);
        $overdueNotificationService = app(OverdueCheckpointNotificationService::class);
        $predictionService = app(StudentCheckpointPredictionService::class);
    
        $students = collect();
        $timelines = collect();
        $milestones = collect();
        $supervisors = collect();
        $unassignedStudents = collect();
        $studentsMissingCandidature = collect();
        $adminStats = [
            'total_supervisors' => 0,
            'total_students' => 0,
            'assigned_students' => 0,
            'unassigned_students' => 0,
            'incomplete_candidature' => 0,
        ];
        $progressSnapshot = null;
        $dashboardTargetUser = null;
        $dashboardCheckpoint = null;
        $dashboardPredictionState = 'unavailable';
        $dashboardMissingPrerequisites = [];
        $predictionWarning = null;
    
        if ($user) {
            if ($user->hasRole('student')) {
                $dashboardTargetUser = $user;
                $timelines = $user->timelines()->orderBy('event_date')->get();
                $milestones = Milestone::where('student_id', $user->id)->get();

                // Best-effort sync + snapshot
                if ($user->start_date) {
                    try {
                        $timelineService->syncForStudent($user);
                        $timelines = $user->timelines()->orderBy('event_date')->get();
                        $progressSnapshot = $timelineService->progressSnapshot(
                            $user->start_date,
                            $user->study_mode ?: 'full_time',
                            null,
                            $user->checkpoint
                        );
                    } catch (\Throwable $e) {}
                }
            } elseif ($user->hasRole('lecturer')) {
                $students = User::where('supervisor_id', $user->id)->get();
                $timelines = Timeline::whereIn('user_id', $students->pluck('id'))->get();
                $milestones = Milestone::whereIn('student_id', $students->pluck('id'))->get();

                // If lecturer selected a student, get that student's data
                $selectedStudentId = request('student_id');
                if ($selectedStudentId) {
                    $student = $students->firstWhere('id', $selectedStudentId);
                    if ($student) {
                        $dashboardTargetUser = $student;
                        $timelines = $student->timelines()->orderBy('event_date')->get();
                        if ($student->start_date) {
                            try {
                                $timelineService->syncForStudent($student);
                                $timelines = $student->timelines()->orderBy('event_date')->get();
                                $progressSnapshot = $timelineService->progressSnapshot(
                                    $student->start_date,
                                    $student->study_mode ?: 'full_time',
                                    null,
                                    $student->checkpoint
                                );
                            } catch (\Throwable $e) {}
                        }
                    }
                }
            } elseif ($user->hasRole('admin')) {
                $supervisors = User::role('lecturer')
                    ->with(['students' => fn($query) => $query->orderBy('name')])
                    ->orderBy('name')
                    ->get();

                $allStudents = User::role('student')->orderBy('name')->get();

                $unassignedStudents = $allStudents->whereNull('supervisor_id')->values();
                $studentsMissingCandidature = $allStudents
                    ->filter(fn(User $student) => blank($student->start_date))
                    ->values();

                $adminStats = [
                    'total_supervisors' => $supervisors->count(),
                    'total_students' => $allStudents->count(),
                    'assigned_students' => $allStudents->whereNotNull('supervisor_id')->count(),
                    'unassigned_students' => $unassignedStudents->count(),
                    'incomplete_candidature' => $studentsMissingCandidature->count(),
                ];
            }
        }

        if ($dashboardTargetUser) {
            $dashboardMissingPrerequisites = $predictionService->missingPrerequisites($dashboardTargetUser);
            [$dashboardCheckpoint, $predictionWarning, $dashboardPredictionState] = $this->resolveDashboardPrediction(
                $dashboardTargetUser,
                $predictionService,
                $dashboardMissingPrerequisites
            );

            if ($dashboardTargetUser->start_date) {
                try {
                    $dashboardTargetUser = $dashboardTargetUser->fresh('checkpoint');
                    $timelineService->syncForStudent($dashboardTargetUser);

                    if ($user->hasRole('student')) {
                        $timelines = $dashboardTargetUser->timelines()->orderBy('event_date')->get();
                    } elseif ($user->hasRole('lecturer') && request('student_id')) {
                        $timelines = $dashboardTargetUser->timelines()->orderBy('event_date')->get();
                    }

                    $progressSnapshot = $timelineService->progressSnapshot(
                        $dashboardTargetUser->start_date,
                        $dashboardTargetUser->study_mode ?: 'full_time',
                        null,
                        $dashboardTargetUser->checkpoint
                    );

                    $overdueNotificationService->dispatchForStudent($dashboardTargetUser->fresh('supervisor'));
                } catch (\Throwable $e) {
                    // Ignore notification failures in dashboard rendering
                }
            }
        }


        return view('home', compact(
            'milestones',
            'timelines',
            'students',
            'supervisors',
            'unassignedStudents',
            'studentsMissingCandidature',
            'adminStats',
            'progressSnapshot',
            'dashboardTargetUser',
            'dashboardCheckpoint',
            'dashboardPredictionState',
            'dashboardMissingPrerequisites',
            'predictionWarning'
        ));
    }

    private function resolveDashboardPrediction(
        User $student,
        StudentCheckpointPredictionService $predictionService,
        array $missingPrerequisites
    ): array {
        $checkpoint = $student->checkpoint ?: new StudentCheckpoint(['user_id' => $student->id]);

        if ($missingPrerequisites !== []) {
            return [$checkpoint, null, 'unavailable_prerequisites'];
        }

        if ($predictionService->shouldRefreshOnProfileView($checkpoint)) {
            try {
                $predictionService->predictForStudent($student->fresh('checkpoint'));
                $checkpoint = $student->fresh('checkpoint')->checkpoint;
            } catch (\Throwable $e) {
                return [$checkpoint, $e->getMessage(), 'refresh_failed'];
            }
        }

        if ($checkpoint && ! is_null($checkpoint->got_probability)) {
            return [$checkpoint, null, 'ready'];
        }

        return [$checkpoint, 'ML prediction could not be refreshed right now. Please try again later.', 'refresh_failed'];
    }
}
