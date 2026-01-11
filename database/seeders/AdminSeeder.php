<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Super Admin
        $admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@mm.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        // Assign role if Laratrust is set up for updates
        $role = Role::where('name', 'superadministrator')->first();
        if ($role) {
            $admin->addRole($role);
        }
    }
}
