<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('milestones', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id')->nullable()->after('id');
            $table->unsignedBigInteger('lecturer_id')->nullable()->after('student_id');
            $table->enum('status', ['pending', 'approved', 'finalized'])->default('pending')->after('due_date');
    
            // Foreign keys (optional, depending on your setup)
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('lecturer_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
    
    public function down()
    {
        Schema::table('milestones', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropForeign(['lecturer_id']);
            $table->dropColumn(['student_id', 'lecturer_id', 'status']);
        });
    }
    
};
