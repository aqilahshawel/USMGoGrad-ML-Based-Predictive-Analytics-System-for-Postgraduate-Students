<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timelines', function (Blueprint $table) {
            if (! Schema::hasColumn('timelines', 'is_system_generated')) {
                $table->boolean('is_system_generated')->default(false);
            }
            if (! Schema::hasColumn('timelines', 'system_key')) {
                $table->string('system_key')->nullable();
            }
            if (! Schema::hasColumn('timelines', 'semester_number')) {
                $table->unsignedSmallInteger('semester_number')->nullable();
            }
            if (! Schema::hasColumn('timelines', 'semester_start')) {
                $table->date('semester_start')->nullable();
            }
            if (! Schema::hasColumn('timelines', 'semester_end')) {
                $table->date('semester_end')->nullable();
            }
            if (! Schema::hasColumn('timelines', 'stage_key')) {
                $table->string('stage_key')->nullable();
            }
            if (! Schema::hasColumn('timelines', 'risk_status')) {
                $table->string('risk_status')->nullable();
            }
        });

        Schema::table('milestones', function (Blueprint $table) {
            if (! Schema::hasColumn('milestones', 'is_system_generated')) {
                $table->boolean('is_system_generated')->default(false);
            }
            if (! Schema::hasColumn('milestones', 'system_key')) {
                $table->string('system_key')->nullable();
            }
            if (! Schema::hasColumn('milestones', 'stage_key')) {
                $table->string('stage_key')->nullable();
            }
            if (! Schema::hasColumn('milestones', 'target_semester_number')) {
                $table->unsignedSmallInteger('target_semester_number')->nullable();
            }
        });

        // Add uniqueness for idempotent sync (safe with NULL system_key)
        Schema::table('timelines', function (Blueprint $table) {
            $table->unique(['user_id', 'system_key'], 'timelines_user_id_system_key_unique');
        });

        Schema::table('milestones', function (Blueprint $table) {
            $table->unique(['student_id', 'system_key'], 'milestones_student_id_system_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('timelines', function (Blueprint $table) {
            // Indexes first
            $table->dropUnique('timelines_user_id_system_key_unique');

            if (Schema::hasColumn('timelines', 'risk_status')) {
                $table->dropColumn('risk_status');
            }
            if (Schema::hasColumn('timelines', 'stage_key')) {
                $table->dropColumn('stage_key');
            }
            if (Schema::hasColumn('timelines', 'semester_end')) {
                $table->dropColumn('semester_end');
            }
            if (Schema::hasColumn('timelines', 'semester_start')) {
                $table->dropColumn('semester_start');
            }
            if (Schema::hasColumn('timelines', 'semester_number')) {
                $table->dropColumn('semester_number');
            }
            if (Schema::hasColumn('timelines', 'system_key')) {
                $table->dropColumn('system_key');
            }
            if (Schema::hasColumn('timelines', 'is_system_generated')) {
                $table->dropColumn('is_system_generated');
            }
        });

        Schema::table('milestones', function (Blueprint $table) {
            $table->dropUnique('milestones_student_id_system_key_unique');

            if (Schema::hasColumn('milestones', 'target_semester_number')) {
                $table->dropColumn('target_semester_number');
            }
            if (Schema::hasColumn('milestones', 'stage_key')) {
                $table->dropColumn('stage_key');
            }
            if (Schema::hasColumn('milestones', 'system_key')) {
                $table->dropColumn('system_key');
            }
            if (Schema::hasColumn('milestones', 'is_system_generated')) {
                $table->dropColumn('is_system_generated');
            }
        });
    }
};
