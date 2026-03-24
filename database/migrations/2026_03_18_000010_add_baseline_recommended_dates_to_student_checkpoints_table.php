<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_checkpoints', function (Blueprint $table) {
            $table->date('baseline_recommended_proposal_review_date')
                ->nullable()
                ->after('recommended_viva_date');
            $table->date('baseline_recommended_research_review_date')
                ->nullable()
                ->after('baseline_recommended_proposal_review_date');
            $table->date('baseline_recommended_nos_date')
                ->nullable()
                ->after('baseline_recommended_research_review_date');
            $table->date('baseline_recommended_final_draft_submission_date')
                ->nullable()
                ->after('baseline_recommended_nos_date');
            $table->date('baseline_recommended_viva_date')
                ->nullable()
                ->after('baseline_recommended_final_draft_submission_date');
            $table->string('baseline_model_version')
                ->nullable()
                ->after('model_version');
            $table->timestamp('baseline_recommendation_generated_at')
                ->nullable()
                ->after('recommendation_generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('student_checkpoints', function (Blueprint $table) {
            $table->dropColumn([
                'baseline_recommended_proposal_review_date',
                'baseline_recommended_research_review_date',
                'baseline_recommended_nos_date',
                'baseline_recommended_final_draft_submission_date',
                'baseline_recommended_viva_date',
                'baseline_model_version',
                'baseline_recommendation_generated_at',
            ]);
        });
    }
};
