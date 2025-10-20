<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class TenantUserSeeder extends Seeder
{
    /**
     * Seed tenant users with businesses.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        // Create owner with business
        $owner = User::query()->firstOrCreate(
            ['email' => 'owner@app.test'],
            [
                'name' => 'Business Owner',
                'email_verified_at' => now(),
                'password' => $password,
            ]
        );

        // Create a business for the owner
        $business = Business::query()->firstOrCreate(
            [
                'name' => 'Demo Business',
                'owner_id' => $owner->id,
            ]
        );

        // Create business-scoped owner role
        $ownerRole = Role::query()->firstOrCreate([
            'name' => 'owner',
            'guard_name' => 'web',
            'business_id' => $business->id,
        ]);

        // Assign owner role to the user for this business using setPermissionsTeamId
        app(PermissionRegistrar::class)->setPermissionsTeamId($business->id);

        if (! $owner->hasRole($ownerRole->name)) {
            $owner->assignRole($ownerRole->name);
        }

        // Reset permissions team ID
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        // Add owner to business users relationship if not already added
        if (! $business->users()->where('user_id', $owner->id)->exists()) {
            $business->users()->attach($owner->id);
        }
    }
}
