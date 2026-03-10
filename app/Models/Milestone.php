<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Milestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'lecturer_id',
        'title',
        'description',
        'due_date',
        'status',
        'is_system_generated',
        'system_key',
        'stage_key',
        'target_semester_number',
    ];
}