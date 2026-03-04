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
        Schema::table('milestones', function (Illuminate\Database\Schema\Blueprint $table) {
            // Drop the foreign key first
            $table->dropForeign(['user_id']);
            // Now drop the column
            $table->dropColumn('user_id');
        });
    }
    
    public function down()
    {
        Schema::table('milestones', function (Illuminate\Database\Schema\Blueprint $table) {
            // Add it back in case you rollback
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
    
    
};
