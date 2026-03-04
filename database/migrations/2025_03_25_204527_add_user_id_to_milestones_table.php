<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up() {
        Schema::table('milestones', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Adds user_id as a foreign key
        });
    }

    public function down() {
        Schema::table('milestones', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
