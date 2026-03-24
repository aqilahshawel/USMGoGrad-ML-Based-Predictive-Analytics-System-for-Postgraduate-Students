<?php

namespace App\Http\Controllers;

use App\Models\Feedback; // If you created the model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\FeedbackReceived; // We'll create this Mailable
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

        $request->validate([
            'name' => 'required|string|max:255',
            'supervisor_name' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        // Optional: Save feedback to the database
        // Feedback::create($request->all());

        // Send email
        $recipientEmail = 'imranzaini389@student.usm.my'; // Replace with your actual email
        Mail::to($recipientEmail)->send(new FeedbackReceived($request->all()));

        return redirect()->route('feedback.create')->with('success', 'Your feedback has been submitted and sent!');
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
