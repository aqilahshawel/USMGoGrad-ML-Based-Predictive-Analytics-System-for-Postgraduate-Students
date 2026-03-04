<?php

namespace App\Http\Controllers;

use App\Models\Feedback; // If you created the model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\FeedbackReceived; // We'll create this Mailable

class FeedbackController extends Controller
{
    public function create()
    {
        return view('feedback.create');
    }

    public function submit(Request $request)
    {
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
}
