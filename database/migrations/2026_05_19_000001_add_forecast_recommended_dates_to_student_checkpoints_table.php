<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_checkpoints', function (Blueprint $table) {
            $table->date('forecast_recommended_proposal_review_date')->nullable()->after('baseline_recommended_proposal_review_date');
            $table->date('forecast_recommended_research_review_date')->nullable()->after('forecast_recommended_proposal_review_date');
            $table->date('forecast_recommended_nos_date')->nullable()->after('forecast_recommended_research_review_date');
            $table->date('forecast_recommended_final_draft_submission_date')->nullable()->after('forecast_recommended_nos_date');
            $table->date('forecast_recommended_viva_date')->nullable()->after('forecast_recommended_final_draft_submission_date');
            $table->timestamp('forecast_recommendation_generated_at')->nullable()->after('baseline_recommendation_generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_checkpoints', function (Blueprint $table) {
            $table->dropColumn([
                'forecast_recommended_proposal_review_date',
                'forecast_recommended_research_review_date',
                'forecast_recommended_nos_date',
                'forecast_recommended_final_draft_submission_date',
                'forecast_recommended_viva_date',
                'forecast_recommendation_generated_at',
            ]);
        });
    }
};
