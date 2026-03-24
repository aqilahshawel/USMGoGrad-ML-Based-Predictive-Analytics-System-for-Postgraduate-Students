<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_checkpoints', function (Blueprint $table) {
            $table->date('recommended_proposal_review_date')->nullable()->after('viva_date');
            $table->date('recommended_research_review_date')->nullable()->after('recommended_proposal_review_date');
            $table->date('recommended_nos_date')->nullable()->after('recommended_research_review_date');
            $table->date('recommended_final_draft_submission_date')->nullable()->after('recommended_nos_date');
            $table->date('recommended_viva_date')->nullable()->after('recommended_final_draft_submission_date');
            $table->timestamp('recommendation_generated_at')->nullable()->after('last_predicted_at');
        });
    }

    public function down(): void
    {
        Schema::table('student_checkpoints', function (Blueprint $table) {
            $table->dropColumn([
                'recommended_proposal_review_date',
                'recommended_research_review_date',
                'recommended_nos_date',
                'recommended_final_draft_submission_date',
                'recommended_viva_date',
                'recommendation_generated_at',
            ]);
        });
    }
};
