<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class SupervisorController extends Controller
{
    public function index()
    {
        $supervisors = User::role('lecturer')->with('students')->get();
        $students = User::role('student')->whereNull('supervisor_id')->get(); // Fetch students without a supervisor
    
        return view('supervisors.index', compact('supervisors', 'students'));
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
}
