<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

final class RoleSeeder extends Seeder
{
    /**
     * Seed global and default business roles.
     */
    public function run(): void
    {
        // Global roles (business_id = null)
        $globalRoles = [
            'super-admin',
            'admin',
            'manager',
        ];

        foreach ($globalRoles as $roleName) {
            Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'business_id' => null,
            ]);
        }

        // Note: Business-scoped roles (owner, admin, manager) are created
        // automatically when businesses are created or members are added
    }
}
