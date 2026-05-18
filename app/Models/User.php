<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'cluster',
        'research_title',
        'research_objective',
        'start_date',
        'study_mode',
        'force_password_change',
        'password_changed_at',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'hashed',
        'start_date' => 'date',
        'force_password_change' => 'bool',
        'password_changed_at' => 'datetime',
    ];

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function students()
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    public function timelines()
    {
        return $this->hasMany(Timeline::class, 'user_id');
    }

    public function calendarIntegrations(): HasMany
    {
        return $this->hasMany(CalendarIntegration::class);
    }

    public function googleCalendarIntegration(): HasOne
    {
        return $this->hasOne(CalendarIntegration::class)->where('provider', 'google');
    }

    public function checkpoint(): HasOne
    {
        return $this->hasOne(StudentCheckpoint::class);
    }

    public function meetingRequestsAsStudent(): HasMany
    {
        return $this->hasMany(MeetingRequest::class, 'student_id');
    }

    public function meetingRequestsAsSupervisor(): HasMany
    {
        return $this->hasMany(MeetingRequest::class, 'supervisor_id');
    }

}
