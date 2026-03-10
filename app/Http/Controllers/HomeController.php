<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Timeline;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Services\CandidatureTimelineService;

class HomeController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $timelineService = app(CandidatureTimelineService::class);
    
        $students = collect();
        $timelines = collect();
        $milestones = collect();
        $progressSnapshot = null;
    
        if ($user) {
            if ($user->hasRole('student')) {
                $timelines = $user->timelines()->orderBy('event_date')->get();
                $milestones = Milestone::where('student_id', $user->id)->get();

                // Best-effort sync + snapshot
                if ($user->start_date && $user->study_mode) {
                    try {
                        $timelineService->syncForStudent($user);
                        $timelines = $user->timelines()->orderBy('event_date')->get();
                        $progressSnapshot = $timelineService->progressSnapshot($user->start_date, $user->study_mode);
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
                        $timelines = $student->timelines()->orderBy('event_date')->get();
                        if ($student->start_date && $student->study_mode) {
                            try {
                                $timelineService->syncForStudent($student);
                                $timelines = $student->timelines()->orderBy('event_date')->get();
                                $progressSnapshot = $timelineService->progressSnapshot($student->start_date, $student->study_mode);
                            } catch (\Throwable $e) {}
                        }
                    }
                }
            } elseif ($user->hasRole('admin')) {
                $timelines = Timeline::all();
            }
        }
    
        return view('home', compact('milestones', 'timelines', 'students', 'progressSnapshot'));
    }
    
}
