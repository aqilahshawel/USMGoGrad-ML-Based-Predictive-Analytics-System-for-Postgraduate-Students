<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\StudentCheckpoint;
use App\Models\CalendarIntegration;
use App\Models\MeetingRequest;
use App\Models\Schedule;
use App\Models\Timeline;
use App\Models\User;
use App\Services\CandidatureTimelineService;
use App\Services\StudentCheckpointPredictionService;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

class SupervisorController extends Controller
{
    private const CLUSTERS = [
        'Service Computing',
        'Data to Knowledge',
        'Enabling Technologies & Infrastructures',
    ];

    private const STUDY_MODES = [
        'full_time' => 'Full Time',
    ];

    public function index()
    {
        $supervisors = User::role('lecturer')->with('students')->get();
        $students = User::role('student')->whereNull('supervisor_id')->get(); // Fetch students without a supervisor

        $allStudents = User::role('student')->orderBy('name')->get();

        $assignedStudents = User::role('student')
            ->whereNotNull('supervisor_id')
            ->with('supervisor')
            ->orderBy('name')
            ->get();
    
        return view('supervisors.index', [
            'supervisors' => $supervisors,
            'students' => $students,
            'allStudents' => $allStudents,
            'assignedStudents' => $assignedStudents,
            'clusters' => self::CLUSTERS,
            'studyModes' => self::STUDY_MODES,
        ]);
    }
    

    public function assign(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
            'supervisor_id' => 'required|exists:users,id'
        ]);

        $student = User::findOrFail($request->student_id);
        $student->supervisor_id = $request->supervisor_id;
        $student->save();

        return redirect()->back()->with('success', 'Supervisor assigned successfully.');
    }

    public function unassign(Request $request)
    {
        $validated = $request->validate([
            'unassign_student_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $student = User::role('student')->findOrFail($validated['unassign_student_id']);
        $student->supervisor_id = null;
        $student->save();

        return redirect()->back()->with('success', 'Student unassigned from supervisor successfully.');
    }

    public function deleteStudent(Request $request)
    {
        $validated = $request->validate([
            'delete_student_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        if ($request->user() && (int) $request->user()->id === (int) $validated['delete_student_id']) {
            return redirect()->back()->with('error', 'You cannot delete the currently logged-in user.');
        }

        $student = User::find($validated['delete_student_id']);

        if (! $student || ! $student->hasRole('student')) {
            return redirect()->back()->withErrors([
                'delete_student_id' => 'Please select a valid student account.',
            ]);
        }

        try {
            DB::transaction(function () use ($student) {
                Schedule::where('user_id', $student->id)->delete();
                CalendarIntegration::where('user_id', $student->id)->delete();
                StudentCheckpoint::where('user_id', $student->id)->delete();

                Timeline::where('user_id', $student->id)->delete();
                Timeline::where('proposed_by_user_id', $student->id)->update(['proposed_by_user_id' => null]);
                Timeline::where('reviewed_by_user_id', $student->id)->update(['reviewed_by_user_id' => null]);

                MeetingRequest::where('student_id', $student->id)
                    ->orWhere('supervisor_id', $student->id)
                    ->orWhere('reviewed_by', $student->id)
                    ->delete();

                User::where('supervisor_id', $student->id)->update(['supervisor_id' => null]);

                $student->delete();
            });
        } catch (QueryException $exception) {
            return redirect()->back()->withErrors([
                'delete_student_id' => 'Unable to delete this student because related records are still protected by the database.',
            ]);
        }

        return redirect()->back()->with('success', 'Student account deleted successfully.');
    }

    public function updateCluster(Request $request)
    {
        $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'cluster' => ['nullable', 'string', Rule::in(self::CLUSTERS)],
        ]);

        $student = User::role('student')->findOrFail($request->student_id);

        $student->cluster = $request->cluster ?: null;
        $student->save();

        $warning = $this->refreshPredictionAfterProfileUpdate($student);

        $redirect = redirect()->back()->with('success', 'Student cluster updated successfully.');

        if ($warning) {
            $redirect->with('prediction_warning', $warning);
        }

        return $redirect;
    }

    public function updateResearchTitle(Request $request)
    {
        $validated = $request->validate([
            'research_title_student_id' => ['required', 'integer', 'exists:users,id'],
            'research_title' => ['nullable', 'string', 'max:255'],
        ]);

        $student = User::role('student')->findOrFail($validated['research_title_student_id']);

        $student->research_title = trim((string) ($validated['research_title'] ?? '')) ?: null;
        $student->save();

        return redirect()->back()->with('success', 'Student research title updated successfully.');
    }

    public function updateResearchObjective(Request $request)
    {
        $validated = $request->validate([
            'research_objective_student_id' => ['required', 'integer', 'exists:users,id'],
            'research_objective' => ['nullable', 'string', 'max:2000'],
        ]);

        $student = User::role('student')->findOrFail($validated['research_objective_student_id']);

        $student->research_objective = trim((string) ($validated['research_objective'] ?? '')) ?: null;
        $student->save();

        return redirect()->back()->with('success', 'Student research objective updated successfully.');
    }

    public function updateRegistrationDate(Request $request)
    {
        $validated = $request->validate([
            'registration_student_id' => ['required', 'integer', 'exists:users,id'],
            'start_date' => ['required', 'date'],
        ]);

        $student = User::role('student')->findOrFail($validated['registration_student_id']);

        $student->start_date = $validated['start_date'];
        $student->save();

        $warning = $this->refreshPredictionAfterProfileUpdate($student);

        $redirect = redirect()->back()->with('success', 'Student registration date updated successfully.');

        if ($warning) {
            $redirect->with('prediction_warning', $warning);
        }

        return $redirect;
    }

    public function updateStudyMode(Request $request)
    {
        $validated = $request->validate([
            'study_mode_student_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $student = User::role('student')->findOrFail($validated['study_mode_student_id']);

        $student->study_mode = 'full_time';
        $student->save();

        $warning = $this->refreshPredictionAfterProfileUpdate($student);

        $redirect = redirect()->back()->with('success', 'Student candidature mode updated successfully.');

        if ($warning) {
            $redirect->with('prediction_warning', $warning);
        }

        return $redirect;
    }

    private function refreshPredictionAfterProfileUpdate(User $student): ?string
    {
        $predictionService = app(StudentCheckpointPredictionService::class);
        $timelineService = app(CandidatureTimelineService::class);

        $student = $student->fresh('checkpoint');
        $missingPrerequisites = $predictionService->missingPrerequisites($student);
        if ($missingPrerequisites !== []) {
            return null;
        }

        $student->checkpoint ?: StudentCheckpoint::firstOrCreate(['user_id' => $student->id]);

        $warning = null;

        try {
            $predictionService->predictForStudent($student->fresh('checkpoint'));
        } catch (\Throwable $e) {
            $warning = $e->getMessage();
        }

        try {
            if ($student->start_date) {
                $timelineService->syncForStudent($student->fresh('checkpoint'));
            }
        } catch (\Throwable $e) {
            $warning = $warning ? $warning . ' ' . $e->getMessage() : $e->getMessage();
        }

        return $warning;
    }
}
