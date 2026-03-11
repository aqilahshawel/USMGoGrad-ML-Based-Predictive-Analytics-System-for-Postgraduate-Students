<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TimelineController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ChatBotController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\CalendarOAuthController;
use App\Http\Controllers\StudentCheckpointController;


// Redirect guests to login instead of home
Route::get('/', function () {
    return redirect()->route('login');
});

// Use Laravel UI's login
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Use Custom Register Controller
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Password Change Controller
Route::middleware(['auth'])->group(function () {
    Route::get('/change-password', [PasswordController::class, 'showChangeForm'])->name('password.change');
    Route::post('/change-password', [PasswordController::class, 'changePassword'])->name('password.update');
});

// Home Route (Only accessible after login)
Route::get('/home', [HomeController::class, 'index'])->middleware('auth')->name('home');

// Milestones: student-personal checkpoints (students manage; lecturers can view supervisees)
Route::middleware(['auth', 'role:student|lecturer'])->group(function () {
    Route::get('milestones', [MilestoneController::class, 'index'])->name('milestones.index');
    Route::get('checkpoints', [StudentCheckpointController::class, 'show'])->name('checkpoints.show');
});

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('milestones/create', [MilestoneController::class, 'create'])->name('milestones.create');
    Route::post('milestones', [MilestoneController::class, 'store'])->name('milestones.store');
    Route::get('milestones/{milestone}/edit', [MilestoneController::class, 'edit'])->name('milestones.edit');
    Route::put('milestones/{milestone}', [MilestoneController::class, 'update'])->name('milestones.update');
    Route::put('checkpoints', [StudentCheckpointController::class, 'update'])->name('checkpoints.update');
});

// SV Assign Page (Admin Only)
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/supervisors', [SupervisorController::class, 'index'])->name('supervisors.index');
    Route::post('/assign-supervisor', [SupervisorController::class, 'assign'])->name('supervisors.assign');
    Route::post('/students/cluster', [SupervisorController::class, 'updateCluster'])->name('students.cluster.update');
    Route::post('/students/candidature-settings', [SupervisorController::class, 'updateCandidatureSettings'])->name('students.candidature.update');
});

// Scheduling Routes (Lecturer + Student visibility)
Route::middleware(['auth', 'role:lecturer|student'])->group(function () {
    Route::get('/scheduling', [ScheduleController::class, 'index']);
    Route::get('/get-availability', [ScheduleController::class, 'getAvailability']);
    Route::get('/get-events', [ScheduleController::class, 'getEvents']); // Fixed this
});

Route::middleware(['auth', 'role:lecturer'])->group(function () {
    Route::post('/save-event', [ScheduleController::class, 'saveEvent']);
});

Route::middleware(['auth', 'role:lecturer'])->prefix('calendar/google')->name('calendar.google.')->group(function () {
    Route::get('/connect', [CalendarOAuthController::class, 'redirectToGoogle'])->name('connect');
    Route::get('/callback', [CalendarOAuthController::class, 'handleGoogleCallback'])->name('callback');
    Route::post('/disconnect', [CalendarOAuthController::class, 'disconnectGoogle'])->name('disconnect');
});

// Timeline Routes (Only for authenticated users)
Route::middleware(['auth'])->group(function () {
    Route::resource('/timeline', TimelineController::class);
});

// Timeline approval (Lecturer only)
Route::middleware(['auth', 'role:lecturer'])->group(function () {
    Route::put('/timeline/{timeline}/approve', [TimelineController::class, 'approve'])->name('timeline.approve');
    Route::put('/timeline/{timeline}/reject', [TimelineController::class, 'reject'])->name('timeline.reject');
});

// ChatBot Routes
Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/chatbot', [ChatBotController::class, 'index'])->name('chatbot.index');
    Route::post('/chatbot/message', [ChatBotController::class, 'sendMessage'])->name('chatbot.message');
});

// Messenger Routes
Route::middleware('auth')->group(function () {
    // Student Routes
    Route::get('/student/messages', [MessageController::class, 'studentChat'])->name('student.messages.index');
    Route::post('/student/messages/{lecturerId}/send', [MessageController::class, 'sendMessage'])->name('student.messages.send');

    // Lecturer Routes
    Route::get('/lecturer/messages', [MessageController::class, 'lecturerChat'])->name('lecturer.messages.index');
    Route::get('/lecturer/messages/{studentId}/chat', [MessageController::class, 'lecturerStudentChat'])->name('lecturer.messages.student.chat');
    Route::post('/lecturer/messages/{studentId}/send', [MessageController::class, 'sendMessage'])->name('lecturer.messages.send');
});

// Email Routes
Route::get('/feedback', [FeedbackController::class, 'create'])->name('feedback.create');
Route::post('/feedback/submit', [FeedbackController::class, 'submit'])->name('feedback.submit');
