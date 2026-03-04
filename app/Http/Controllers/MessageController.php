<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function studentChat()
    {
        $student = auth()->user();
        $supervisor = $student->supervisor; // Assuming 'supervisor' relationship in User model

        if (!$supervisor) {
            return view('messages.student_chat', ['messages' => collect(), 'supervisor' => null]);
        }

        $messages = Message::where(function ($query) use ($student, $supervisor) {
            $query->where('sender_id', $student->id)->where('receiver_id', $supervisor->id)
                ->orWhere('sender_id', $supervisor->id)->where('receiver_id', $student->id);
        })->orderBy('created_at', 'asc')->get();

        // Mark messages as read if the logged-in student is the receiver
        Message::where('sender_id', $supervisor->id)
            ->where('receiver_id', $student->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return view('messages.student_chat', compact('messages', 'supervisor'));
    }

    public function lecturerChat()
    {
        $lecturer = Auth::user();
        $students = User::where('supervisor_id', $lecturer->id)->get(); // Assuming 'supervisor_id' on User model

        $students->each(function ($student) use ($lecturer) {
            $student->unread_count = Message::where('sender_id', $student->id)
                ->where('receiver_id', $lecturer->id)
                ->where('is_read', false)
                ->count();
        });

        return view('messages.lecturer_chat', compact('lecturer', 'students'));
    }

    public function lecturerStudentChat($studentId)
    {
        $lecturer = auth()->user();
        $student = User::findOrFail($studentId);

        $messages = Message::where(function ($query) use ($lecturer, $student) {
            $query->where('sender_id', $lecturer->id)->where('receiver_id', $student->id)
                ->orWhere('sender_id', $student->id)->where('receiver_id', $lecturer->id);
        })->orderBy('created_at', 'asc')->get();

        // Mark messages as read if the logged-in lecturer is the receiver
        Message::where('sender_id', $student->id)
            ->where('receiver_id', $lecturer->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return view('messages.lecturer_student_chat', compact('messages', 'student'));
    }

    public function sendMessage(Request $request, $receiverId)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $message = new Message();
        $message->sender_id = auth()->id();
        $message->receiver_id = $receiverId;
        $message->message = $request->message; // Changed 'content' to 'message' to match migration
        $message->save();

        if (auth()->user()->hasRole('student')) { // Assuming you have roles
            return redirect()->route('student.messages.index')
                ->with('success', 'Message sent successfully!');
        } elseif (auth()->user()->hasRole('lecturer')) {
            return redirect()->route('lecturer.messages.student.chat', ['studentId' => $receiverId])
                ->with('success', 'Message sent successfully!');
        }

        return back()->with('success', 'Message sent successfully!'); // Default redirect
    }
}
