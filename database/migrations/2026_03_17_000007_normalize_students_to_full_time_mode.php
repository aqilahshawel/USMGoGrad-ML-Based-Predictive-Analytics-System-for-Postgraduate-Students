<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('study_mode', '!=', 'full_time')
            ->whereNotNull('study_mode')
            ->update(['study_mode' => 'full_time']);

        DB::table('users')
            ->whereNull('study_mode')
            ->where('email', 'like', '%@student.usm.my')
            ->update(['study_mode' => 'full_time']);
    }

    public function down(): void
    {
        // Irreversible normalization: keep full_time values.
    }
};
