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
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $lecturerRole = Role::firstOrCreate(['name' => 'lecturer', 'guard_name' => 'web']);

        $admin = User::firstOrCreate([
            'email' => 'admin@admin.usm.my',
        ], [
            'name' => 'Admin',
            'password' => Hash::make('password'),
        ]);

        if (!$admin->hasRole('admin')) {
            $admin->assignRole($adminRole);
        }
    }
}
