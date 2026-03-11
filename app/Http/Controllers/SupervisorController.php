<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\Rule;

class SupervisorController extends Controller
{
    private const CLUSTERS = [
        'Service Computing',
        'Data to Knowledge',
        'Enabling Technologies & Infrastructures',
    ];

    private const STUDY_MODES = [
        'full_time' => 'Full Time',
        'part_time' => 'Part Time',
    ];

    public function index()
    {
        $supervisors = User::role('lecturer')->with('students')->get();
        $students = User::role('student')->whereNull('supervisor_id')->get(); // Fetch students without a supervisor

        $allStudents = User::role('student')->orderBy('name')->get();
    
        return view('supervisors.index', [
            'supervisors' => $supervisors,
            'students' => $students,
            'allStudents' => $allStudents,
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

    public function updateCluster(Request $request)
    {
        $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'cluster' => ['nullable', 'string', Rule::in(self::CLUSTERS)],
        ]);

        $student = User::role('student')->findOrFail($request->student_id);

        $student->cluster = $request->cluster ?: null;
        $student->save();

        return redirect()->back()->with('success', 'Student cluster updated successfully.');
    }

    public function updateCandidatureSettings(Request $request)
    {
        $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'start_date' => ['required', 'date'],
            'study_mode' => ['required', 'string', Rule::in(array_keys(self::STUDY_MODES))],
        ]);

        $student = User::role('student')->findOrFail($request->student_id);

        $student->start_date = $request->start_date;
        $student->study_mode = $request->study_mode;
        $student->save();

        return redirect()->back()->with('success', 'Student candidature settings updated successfully.');
    }
}
