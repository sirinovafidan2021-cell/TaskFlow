<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'projects.view',
            'projects.create',
            'projects.update',
            'projects.delete',

            'projects.members.manage',

            'tasks.view',
            'tasks.create',
            'tasks.update',
            'tasks.delete',
            'tasks.assign',

            'tasks.status.update',
            'tasks.comment.create',

            'activity.view',
            'users.roles.manage',
            'tokens.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $admin = Role::findOrCreate('admin');

        $projectManager = Role::findOrCreate('project_manager');

        $member = Role::findOrCreate('member');

        $admin->givePermissionTo(Permission::all());

        $projectManager->givePermissionTo([
            'projects.view',
            'projects.create',
            'projects.update',
            'projects.delete',
            'projects.members.manage',

            'tasks.view',
            'tasks.create',
            'tasks.update',
            'tasks.delete',
            'tasks.assign',
            'tasks.status.update',
            'tasks.comment.create',

            'activity.view',
        ]);

        $member->givePermissionTo([
            'projects.view',

            'tasks.view',
            'tasks.update',
            'tasks.status.update',
            'tasks.comment.create',
        ]);
    }
}
