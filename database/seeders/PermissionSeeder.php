<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'news.view', 'news.create', 'news.edit', 'news.delete',
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
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
            'news.view', 'news.create', 'news.edit', 'news.delete',
            'categories.view',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
