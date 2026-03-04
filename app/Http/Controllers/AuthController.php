<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use Illuminate\Support\Str;

class AuthController extends Controller
{

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'unique:users,email',
                'regex:/@(student|lecturer|admin)\.usm\.my$/'
            ],
        ]);
    
        $randomPassword = Str::random(10); // Generate random password
    
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($randomPassword),
        ]);
    
        // Assign role based on email domain
        if (str_ends_with($request->email, '@student.usm.my')) {
            $user->assignRole('student');
        } elseif (str_ends_with($request->email, '@lecturer.usm.my')) {
            $user->assignRole('lecturer');
        } elseif (str_ends_with($request->email, '@admin.usm.my')) {
            $user->assignRole('admin');
        }
    
        // OPTIONAL: For debugging, you can log the password (remove in production)
        // logger("Generated password for {$user->email}: $randomPassword");
    
        return redirect()->route('register')->with('generated_password', $randomPassword);

    }
    
    
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Determine user role based on email
        $role = null;
        if (str_ends_with($request->email, '@student.usm.my')) {
            $role = 'student';
        } elseif (str_ends_with($request->email, '@lecturer.usm.my')) {
            $role = 'lecturer';
        } elseif (str_ends_with($request->email, '@admin.usm.my')) {
            $role = 'admin';
        } else {
            return back()->with('error', 'Invalid email domain');
        }

        // Authenticate user
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            return redirect()->route($role . '.dashboard'); // Redirect based on role
        }

        return back()->with('error', 'Invalid credentials');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
}
