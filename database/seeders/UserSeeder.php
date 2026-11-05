<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $configuredPassword = (string) env('DEFAULT_ADMIN_PASSWORD', '');
        $generatedPassword = $configuredPassword !== '' ? $configuredPassword : Str::password(24);
        $hashedPassword = Hash::make($generatedPassword);

        $adminEmail = (string) env('DEFAULT_ADMIN_EMAIL', 'admin@example.com');

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name'     => 'Super Admin',
                'password' => $hashedPassword,
            ]
        );
        $superAdmin->syncRoles([RoleName::SuperAdmin->value]);

        $admin = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name'     => 'Admin User',
                'password' => $hashedPassword,
            ]
        );
        $admin->syncRoles([RoleName::Admin->value]);

        if (!app()->environment('production')) {
            $editor = User::firstOrCreate(
                ['email' => 'editor@example.com'],
                [
                    'name'     => 'Editor User',
                    'password' => $hashedPassword,
                ]
            );
            $editor->syncRoles([RoleName::Editor->value]);
        }

        if ($configuredPassword === '' && $this->command !== null) {
            $this->command->warn("DEFAULT_ADMIN_PASSWORD tanımlı olmadığı için tek seferlik güçlü parola üretildi: {$generatedPassword}");
        }
    }
}
