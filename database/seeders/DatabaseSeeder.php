<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // ✅ Ensure roles exist
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $lecturerRole = Role::firstOrCreate(['name' => 'lecturer', 'guard_name' => 'web']);

        // ✅ Create Admin User
        $admin = User::firstOrCreate([
            'email' => 'admin@admin.usm.my',
        ], [
            'name' => 'Admin',
            'password' => Hash::make('password'),
        ]);

        // ✅ Assign Admin Role
        if (!$admin->hasRole('admin')) {
            $admin->assignRole($adminRole);
        }
    }
}
