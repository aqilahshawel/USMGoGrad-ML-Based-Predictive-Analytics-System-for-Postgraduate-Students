<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('proposal_review_date')->nullable();
            $table->date('research_review_date')->nullable();
            $table->date('nos_date')->nullable();
            $table->date('final_draft_submission_date')->nullable();
            $table->date('viva_date')->nullable();
            $table->decimal('got_probability', 5, 4)->nullable();
            $table->boolean('predicted_got')->nullable();
            $table->string('risk_level')->nullable();
            $table->string('model_version')->nullable();
            $table->timestamp('last_predicted_at')->nullable();
            $table->json('prediction_input')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_checkpoints');
    }
};