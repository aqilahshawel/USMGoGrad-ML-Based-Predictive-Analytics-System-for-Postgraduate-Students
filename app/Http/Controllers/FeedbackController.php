<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class FeedbackController extends Controller
{
    public function create(Request $request)
    {
        if ($redirect = $this->redirectIfNotEligible($request->user())) {
            return $redirect;
        }

        return view('feedback.create');
    }

    public function submit(Request $request)
    {
        if ($redirect = $this->redirectIfNotEligible($request->user())) {
            return $redirect;
        }

        $validated = $request->validate([
            'description' => 'required|string',
        ]);

        $user = $request->user();
        $supervisor = $user?->supervisor;

        Feedback::create([
            'user_id' => $user?->id,
            'supervisor_id' => $supervisor?->id,
            'name' => (string) ($user?->name ?? 'Unknown'),
            'supervisor_name' => (string) ($supervisor?->name ?? 'Unassigned'),
            'description' => $validated['description'],
        ]);

        return redirect()->route('feedback.create')->with('success', 'Your feedback has been submitted.');
    }

    private function redirectIfNotEligible(?User $user): ?RedirectResponse
    {
        if (! $user || ! $user->start_date) {
            return redirect()->route('home')
                ->with('error', 'Feedback is available only after two months from your registration date.');
        }

        $availableOn = $user->start_date->copy()->addMonthsNoOverflow(2)->startOfDay();

        if (now()->lt($availableOn)) {
            return redirect()->route('home')
                ->with('error', 'Feedback will be available on ' . $availableOn->format('d M Y') . '.');
        }

        return null;
    }
}
