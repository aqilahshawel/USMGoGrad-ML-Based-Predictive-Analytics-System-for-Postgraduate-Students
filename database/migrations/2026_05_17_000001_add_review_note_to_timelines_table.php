<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timelines', function (Blueprint $table) {
            if (! Schema::hasColumn('timelines', 'review_note')) {
                $table->text('review_note')->nullable()->after('reviewed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('timelines', function (Blueprint $table) {
            if (Schema::hasColumn('timelines', 'review_note')) {
                $table->dropColumn('review_note');
            }
        });
    }
};