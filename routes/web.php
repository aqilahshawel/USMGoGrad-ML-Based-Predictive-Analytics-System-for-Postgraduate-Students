<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TimelineController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ChatBotController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\CalendarOAuthController;
use App\Http\Controllers\IpsDownloadController;
use App\Notifications\CheckpointOverdueReminderNotification;


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

Route::middleware(['auth', 'role:student|lecturer'])->group(function () {
    Route::get('checkpoints', function (Request $request) {
        $params = ['tab' => 'checkpoint'];

        if ($request->filled('student_id')) {
            $params['student_id'] = $request->query('student_id');
        }

        return redirect()->route('timeline.index', $params);
    })->name('checkpoints.show');
});

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::put('checkpoints', [TimelineController::class, 'updateCheckpoint'])->name('checkpoints.update');
    Route::put('timeline/checkpoints', [TimelineController::class, 'updateCheckpoint'])->name('timeline.checkpoints.update');
});

// SV Assign Page (Admin Only)
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/supervisors', [SupervisorController::class, 'index'])->name('supervisors.index');
    Route::post('/assign-supervisor', [SupervisorController::class, 'assign'])->name('supervisors.assign');
    Route::post('/unassign-supervisor', [SupervisorController::class, 'unassign'])->name('supervisors.unassign');
    Route::post('/students/delete', [SupervisorController::class, 'deleteStudent'])->name('students.delete');
    Route::post('/students/cluster', [SupervisorController::class, 'updateCluster'])->name('students.cluster.update');
    Route::post('/students/research-title', [SupervisorController::class, 'updateResearchTitle'])->name('students.research-title.update');
    Route::post('/students/research-objective', [SupervisorController::class, 'updateResearchObjective'])->name('students.research-objective.update');
    Route::post('/students/registration-date', [SupervisorController::class, 'updateRegistrationDate'])->name('students.registration-date.update');
    Route::post('/students/study-mode', [SupervisorController::class, 'updateStudyMode'])->name('students.study-mode.update');
});

// Scheduling Routes (Lecturer + Student visibility)
Route::middleware(['auth', 'role:lecturer|student'])->group(function () {
    Route::get('/scheduling', [ScheduleController::class, 'index'])->name('scheduling.index');
    Route::get('/get-availability', [ScheduleController::class, 'getAvailability']);
    Route::get('/get-events', [ScheduleController::class, 'getEvents']); // Fixed this
});

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::post('/meeting-requests', [ScheduleController::class, 'storeMeetingRequest'])->name('meeting-requests.store');
});

Route::middleware(['auth', 'role:lecturer'])->group(function () {
    Route::post('/save-event', [ScheduleController::class, 'saveEvent']);
    Route::put('/meeting-requests/{meetingRequest}/approve', [ScheduleController::class, 'approveMeetingRequest'])->name('meeting-requests.approve');
    Route::put('/meeting-requests/{meetingRequest}/reject', [ScheduleController::class, 'rejectMeetingRequest'])->name('meeting-requests.reject');
});

Route::middleware(['auth', 'role:lecturer'])->prefix('calendar/google')->name('calendar.google.')->group(function () {
    Route::get('/connect', [CalendarOAuthController::class, 'redirectToGoogle'])->name('connect');
    Route::get('/callback', [CalendarOAuthController::class, 'handleGoogleCallback'])->name('callback');
    Route::post('/disconnect', [CalendarOAuthController::class, 'disconnectGoogle'])->name('disconnect');
});

// Timeline Routes (Only for authenticated users)
Route::middleware(['auth'])->group(function () {
    Route::resource('/timeline', TimelineController::class);

    Route::post('/notifications/{id}/dismiss', function (string $id) {
        $user = auth()->user();
        abort_unless($user, 403);

        $notification = $user->notifications()
            ->whereKey($id)
            ->firstOrFail();

        $notification->markAsRead();
        return back();
    })->name('notifications.dismiss');

    Route::post('/notifications/{id}/open-timeline', function (string $id) {
        $user = auth()->user();
        abort_unless($user, 403);

        $notification = $user->notifications()
            ->where('type', CheckpointOverdueReminderNotification::class)
            ->whereKey($id)
            ->firstOrFail();

        $notification->markAsRead();

        $url = $notification->data['timeline_url'] ?? route('timeline.index', ['tab' => 'checkpoint']);
        return redirect()->to($url);
    })->name('notifications.openTimeline');

    Route::post('/notifications/{id}/open', function (string $id) {
        $user = auth()->user();
        abort_unless($user, 403);

        $notification = $user->notifications()
            ->whereKey($id)
            ->firstOrFail();

        $notification->markAsRead();

        $url = $notification->data['action_url'] ?? route('home');
        return redirect()->to($url);
    })->name('notifications.open');
});

// Timeline approval (Lecturer only)
Route::middleware(['auth', 'role:lecturer'])->group(function () {
    Route::put('/timeline/{timeline}/approve', [TimelineController::class, 'approve'])->name('timeline.approve');
    Route::put('/timeline/{timeline}/reject', [TimelineController::class, 'reject'])->name('timeline.reject');
});

// ChatBot Routes
Route::middleware(['auth', 'role:student'])->group(function () {
    Route::post('/chatbot/message', [ChatBotController::class, 'sendMessage'])->name('chatbot.message');

    Route::get('/ips/download', [IpsDownloadController::class, 'download'])
        ->middleware('signed:relative')
        ->name('ips.download');
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

// Feedback Routes (Student only)
Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/feedback', [FeedbackController::class, 'create'])->name('feedback.create');
    Route::post('/feedback/submit', [FeedbackController::class, 'submit'])->name('feedback.submit');
});
