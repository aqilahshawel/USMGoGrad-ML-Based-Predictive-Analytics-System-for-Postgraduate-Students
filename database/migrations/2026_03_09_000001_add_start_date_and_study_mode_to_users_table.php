<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'start_date')) {
                $table->date('start_date')->nullable();
            }

            if (! Schema::hasColumn('users', 'study_mode')) {
                $table->enum('study_mode', ['full_time', 'part_time'])->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'study_mode')) {
                $table->dropColumn('study_mode');
            }

            if (Schema::hasColumn('users', 'start_date')) {
                $table->dropColumn('start_date');
            }
        });
    }
};
