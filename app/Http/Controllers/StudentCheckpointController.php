<?php

namespace App\Http\Controllers;

use App\Models\StudentCheckpoint;
use App\Models\User;
use App\Services\StudentCheckpointPredictionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentCheckpointController extends Controller
{
    public function show(Request $request, StudentCheckpointPredictionService $predictionService): View
    {
        $user = Auth::user();
        $students = collect();
        $selectedStudentId = null;
        $targetStudent = null;

        if ($user->hasRole('student')) {
            $targetStudent = $user;
        } elseif ($user->hasRole('lecturer')) {
            $students = User::where('supervisor_id', $user->id)->orderBy('name')->get();
            $selectedStudentId = (int) $request->integer('student_id', (int) ($students->first()->id ?? 0));
            $targetStudent = $students->firstWhere('id', $selectedStudentId);
        } else {
            abort(403);
        }

        $checkpoint = $targetStudent
            ? StudentCheckpoint::firstOrCreate(['user_id' => $targetStudent->id])
            : null;

        $missingPrerequisites = $targetStudent ? $predictionService->missingPrerequisites($targetStudent) : [];

        return view('checkpoints.show', compact(
            'checkpoint',
            'missingPrerequisites',
            'selectedStudentId',
            'students',
            'targetStudent'
        ));
    }

    public function update(Request $request, StudentCheckpointPredictionService $predictionService): RedirectResponse
    {
        $student = Auth::user();
        if (! $student->hasRole('student')) {
            abort(403);
        }

        $validated = $request->validate([
            'proposal_review_date' => ['nullable', 'date'],
            'research_review_date' => ['nullable', 'date'],
            'nos_date' => ['nullable', 'date'],
            'final_draft_submission_date' => ['nullable', 'date'],
            'viva_date' => ['nullable', 'date'],
        ]);

        $checkpoint = StudentCheckpoint::firstOrCreate(['user_id' => $student->id]);
        $checkpoint->fill($validated)->save();

        $warning = null;
        try {
            $predictionService->predictForStudent($student->fresh('checkpoint'));
        } catch (\Throwable $e) {
            $warning = $e->getMessage();
        }

        return redirect()
            ->route('checkpoints.show')
            ->with('success', 'Checkpoint dates updated successfully.')
            ->with('prediction_warning', $warning);
    }
}