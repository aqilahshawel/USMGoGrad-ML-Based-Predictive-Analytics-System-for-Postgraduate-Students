<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timeline extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'event_date',
        'completed',
        'user_id',
        'is_system_generated',
        'is_generator_locked',
        'system_key',
        'semester_number',
        'semester_start',
        'semester_end',
        'stage_key',
        'risk_status',
        'status',
        'proposed_by_user_id',
        'reviewed_by_user_id',
        'reviewed_at',
        'replaces_timeline_id',
    ];

    protected $casts = [
        'event_date' => 'date',
        'semester_start' => 'date',
        'semester_end' => 'date',
        'reviewed_at' => 'datetime',
        'completed' => 'bool',
        'is_system_generated' => 'bool',
        'is_generator_locked' => 'bool',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
    
}
