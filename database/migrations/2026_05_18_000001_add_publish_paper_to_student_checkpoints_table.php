<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_checkpoints', function (Blueprint $table) {
            if (! Schema::hasColumn('student_checkpoints', 'publish_paper')) {
                $table->boolean('publish_paper')->default(false)->after('proposal_review_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_checkpoints', function (Blueprint $table) {
            if (Schema::hasColumn('student_checkpoints', 'publish_paper')) {
                $table->dropColumn('publish_paper');
            }
        });
    }
};
