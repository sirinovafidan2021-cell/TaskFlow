<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        foreach ([
            ['name' => 'Admin User', 'email' => 'admin@example.test', 'role' => UserRole::Admin],
            ['name' => 'Project Manager User', 'email' => 'manager@example.test', 'role' => UserRole::ProjectManager],
            ['name' => 'Member User', 'email' => 'member@example.test', 'role' => UserRole::Member],
        ] as $demoUser) {
            $user = User::query()->updateOrCreate(
                ['email' => $demoUser['email']],
                [
                    'name' => $demoUser['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([$demoUser['role']->value]);
        }
    }
}
