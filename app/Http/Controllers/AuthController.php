<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{

    private const CLUSTERS = [
        'Service Computing',
        'Data to Knowledge',
        'Enabling Technologies & Infrastructures',
    ];

    private const EMAIL_DOMAINS = [
        '@student.usm.my' => 'Student',
        '@lecturer.usm.my' => 'Lecturer',
    ];

    private const STUDY_MODES = [
        'full_time' => 'Full Time',
    ];

    public function showRegisterForm()
    {
        return view('auth.register', [
            'clusters' => self::CLUSTERS,
            'emailDomains' => self::EMAIL_DOMAINS,
            'studyModes' => self::STUDY_MODES,
        ]);
    }

    public function register(Request $request)
    {
        $emailDomain = (string) $request->input('email_domain');
        $emailUsername = strtolower(trim((string) $request->input('email_username')));

        $request->merge([
            'email_username' => $emailUsername,
            'email' => $emailUsername !== '' ? $emailUsername . $emailDomain : null,
        ]);

        $isStudent = $emailDomain === '@student.usm.my';

        $rules = [
            'name' => 'required|string|max:255',
            'email_username' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._%+-]+$/'],
            'email_domain' => ['required', 'string', Rule::in(array_keys(self::EMAIL_DOMAINS))],
            'email' => [
                'required',
                'email',
                'unique:users,email',
            ],
            'cluster' => ['nullable', 'string', Rule::in(self::CLUSTERS)],
            'study_mode' => ['nullable', 'string', Rule::in(array_keys(self::STUDY_MODES))],
            'start_date' => ['nullable', 'date'],
        ];

        if ($isStudent) {
            $rules['cluster'] = ['required', 'string', Rule::in(self::CLUSTERS)];
            $rules['study_mode'] = ['nullable', 'string', Rule::in(array_keys(self::STUDY_MODES))];
            $rules['start_date'] = ['required', 'date'];
        }

        $request->validate($rules);
    
        $randomPassword = Str::random(10); // Generate random password
    
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($randomPassword),
            'force_password_change' => true,
            'cluster' => $isStudent ? $request->cluster : null,
            'study_mode' => $isStudent ? 'full_time' : null,
            'start_date' => $isStudent ? $request->start_date : null,
        ]);
    
        // Assign role based on email domain
        if ($isStudent) {
            $user->assignRole('student');
        } elseif ($emailDomain === '@lecturer.usm.my') {
            $user->assignRole('lecturer');
        }
    
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
            return back()
                ->withErrors(['email' => 'You have entered the wrong email or password.'])
                ->withInput($request->only('email', 'remember'));
        }

        // Authenticate user
        if (Auth::attempt(
            ['email' => $request->email, 'password' => $request->password],
            $request->boolean('remember')
        )) {
            return redirect()->route($role . '.dashboard'); // Redirect based on role
        }

        return back()
            ->withErrors(['email' => 'You have entered the wrong email or password.'])
            ->withInput($request->only('email', 'remember'));
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
}
