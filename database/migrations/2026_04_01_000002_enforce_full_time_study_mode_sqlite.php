<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        if (! Schema::hasTable('users')) {
            return;
        }

        DB::table('users')
            ->whereNotNull('study_mode')
            ->where('study_mode', '!=', 'full_time')
            ->update(['study_mode' => 'full_time']);

        DB::statement(<<<'SQL'
CREATE TRIGGER IF NOT EXISTS users_study_mode_full_time_only_insert
BEFORE INSERT ON users
FOR EACH ROW
WHEN NEW.study_mode IS NOT NULL AND NEW.study_mode <> 'full_time'
BEGIN
    SELECT RAISE(ABORT, 'study_mode must be full_time or NULL');
END;
SQL);

        DB::statement(<<<'SQL'
CREATE TRIGGER IF NOT EXISTS users_study_mode_full_time_only_update
BEFORE UPDATE OF study_mode ON users
FOR EACH ROW
WHEN NEW.study_mode IS NOT NULL AND NEW.study_mode <> 'full_time'
BEGIN
    SELECT RAISE(ABORT, 'study_mode must be full_time or NULL');
END;
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement('DROP TRIGGER IF EXISTS users_study_mode_full_time_only_insert');
        DB::statement('DROP TRIGGER IF EXISTS users_study_mode_full_time_only_update');
    }
};