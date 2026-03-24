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
        'ekc500_admin_approved',
        'proposal_review_date',
        'research_review_date',
        'nos_date',
        'final_draft_submission_date',
        'viva_date',
        'recommended_proposal_review_date',
        'recommended_research_review_date',
        'recommended_nos_date',
        'recommended_final_draft_submission_date',
        'recommended_viva_date',
        'baseline_recommended_proposal_review_date',
        'baseline_recommended_research_review_date',
        'baseline_recommended_nos_date',
        'baseline_recommended_final_draft_submission_date',
        'baseline_recommended_viva_date',
        'got_probability',
        'predicted_got',
        'risk_level',
        'model_version',
        'baseline_model_version',
        'last_predicted_at',
        'recommendation_generated_at',
        'baseline_recommendation_generated_at',
        'prediction_input',
    ];

    protected $casts = [
        'ekc500_admin_approved' => 'bool',
        'proposal_review_date' => 'date',
        'research_review_date' => 'date',
        'nos_date' => 'date',
        'final_draft_submission_date' => 'date',
        'viva_date' => 'date',
        'recommended_proposal_review_date' => 'date',
        'recommended_research_review_date' => 'date',
        'recommended_nos_date' => 'date',
        'recommended_final_draft_submission_date' => 'date',
        'recommended_viva_date' => 'date',
        'baseline_recommended_proposal_review_date' => 'date',
        'baseline_recommended_research_review_date' => 'date',
        'baseline_recommended_nos_date' => 'date',
        'baseline_recommended_final_draft_submission_date' => 'date',
        'baseline_recommended_viva_date' => 'date',
        'predicted_got' => 'bool',
        'last_predicted_at' => 'datetime',
        'recommendation_generated_at' => 'datetime',
        'baseline_recommendation_generated_at' => 'datetime',
        'prediction_input' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}