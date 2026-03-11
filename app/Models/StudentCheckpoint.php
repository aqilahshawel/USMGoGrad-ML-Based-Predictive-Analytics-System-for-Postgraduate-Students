<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentCheckpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'proposal_review_date',
        'research_review_date',
        'nos_date',
        'final_draft_submission_date',
        'viva_date',
        'got_probability',
        'predicted_got',
        'risk_level',
        'model_version',
        'last_predicted_at',
        'prediction_input',
    ];

    protected $casts = [
        'proposal_review_date' => 'date',
        'research_review_date' => 'date',
        'nos_date' => 'date',
        'final_draft_submission_date' => 'date',
        'viva_date' => 'date',
        'predicted_got' => 'bool',
        'last_predicted_at' => 'datetime',
        'prediction_input' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}