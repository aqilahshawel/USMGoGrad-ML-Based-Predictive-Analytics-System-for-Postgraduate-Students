<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_checkpoints', function (Blueprint $table) {
            $table->boolean('ekc500_admin_approved')->default(false)->after('user_id');
        });

        DB::table('student_checkpoints')
            ->whereNotNull('ekc500_admin_approval_date')
            ->update(['ekc500_admin_approved' => true]);
    }

    public function down(): void
    {
        Schema::table('student_checkpoints', function (Blueprint $table) {
            $table->dropColumn('ekc500_admin_approved');
        });
    }
};