<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timelines', function (Blueprint $table) {
            if (! Schema::hasColumn('timelines', 'status')) {
                // approved | pending | rejected
                $table->string('status')->default('approved');
            }

            if (! Schema::hasColumn('timelines', 'proposed_by_user_id')) {
                $table->foreignId('proposed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('timelines', 'reviewed_by_user_id')) {
                $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('timelines', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable();
            }

            if (! Schema::hasColumn('timelines', 'replaces_timeline_id')) {
                $table->foreignId('replaces_timeline_id')->nullable()->constrained('timelines')->nullOnDelete();
            }

            if (! Schema::hasColumn('timelines', 'is_generator_locked')) {
                $table->boolean('is_generator_locked')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('timelines', function (Blueprint $table) {
            if (Schema::hasColumn('timelines', 'replaces_timeline_id')) {
                $table->dropForeign(['replaces_timeline_id']);
                $table->dropColumn('replaces_timeline_id');
            }

            if (Schema::hasColumn('timelines', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }

            if (Schema::hasColumn('timelines', 'reviewed_by_user_id')) {
                $table->dropForeign(['reviewed_by_user_id']);
                $table->dropColumn('reviewed_by_user_id');
            }

            if (Schema::hasColumn('timelines', 'proposed_by_user_id')) {
                $table->dropForeign(['proposed_by_user_id']);
                $table->dropColumn('proposed_by_user_id');
            }

            if (Schema::hasColumn('timelines', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('timelines', 'is_generator_locked')) {
                $table->dropColumn('is_generator_locked');
            }
        });
    }
};
