<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $admin->assignRole('admin');

        $manager = User::factory()->create([
            'name' => 'Project Manager',
            'email' => 'manager@test.com',
            'password' => 'password',
        ]);

        $manager->assignRole('project_manager');

        $member = User::factory()->create([
            'name' => 'Member',
            'email' => 'member@test.com',
            'password' => 'password',
        ]);

        $member->assignRole('member');
    }
}
