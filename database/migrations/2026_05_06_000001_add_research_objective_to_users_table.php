<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'research_objective')) {
                if (Schema::hasColumn('users', 'research_title')) {
                    $table->text('research_objective')->nullable()->after('research_title');
                } else {
                    $table->text('research_objective')->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'research_objective')) {
                $table->dropColumn('research_objective');
            }
        });
    }
};
