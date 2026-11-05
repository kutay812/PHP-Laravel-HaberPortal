<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\RoleName;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UpdatePermissions extends Command
{
    protected $signature = 'permissions:update';
    protected $description = 'Update permissions for all roles without affecting existing users';

    public function handle(): int
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info('Updating permissions...');

        $permissions = [
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'news.view',
            'news.create',
            'news.edit',
            'news.delete',
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdmin = Role::firstOrCreate(['name' => RoleName::SuperAdmin->value]);
        $admin = Role::firstOrCreate(['name' => RoleName::Admin->value]);
        $editor = Role::firstOrCreate(['name' => RoleName::Editor->value]);
        Role::firstOrCreate(['name' => RoleName::User->value]);

        $superAdmin->syncPermissions(Permission::all());

        $admin->syncPermissions($permissions);

        $editor->syncPermissions([
            'news.view',
            'news.create',
            'news.edit',
            'news.delete',
            'categories.view',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info('Permissions updated successfully!');

        return self::SUCCESS;
    }
}
