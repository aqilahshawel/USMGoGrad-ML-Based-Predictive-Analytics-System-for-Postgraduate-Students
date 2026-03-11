<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_checkpoints', function (Blueprint $table) {
            $table->dropColumn('ekc500_admin_approval_date');
        });
    }

    public function down(): void
    {
        Schema::table('student_checkpoints', function (Blueprint $table) {
            $table->date('ekc500_admin_approval_date')->nullable()->after('user_id');
        });
    }
};
