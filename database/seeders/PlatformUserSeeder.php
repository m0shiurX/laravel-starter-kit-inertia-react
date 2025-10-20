<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class PlatformUserSeeder extends Seeder
{
    /**
     * Seed platform users with global roles.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        $platformUsers = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@app.test',
                'role' => 'super-admin',
            ],
            [
                'name' => 'Admin',
                'email' => 'admin@app.test',
                'role' => 'admin',
            ],
            [
                'name' => 'Manager',
                'email' => 'manager@app.test',
                'role' => 'manager',
            ],
        ];

        foreach ($platformUsers as $userData) {
            $user = User::query()->firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'email_verified_at' => now(),
                    'password' => $password,
                ]
            );

            // Assign global role without business_id context
            // We need to bypass the team resolver temporarily
            if (! $user->roles()->wherePivot('business_id', null)->where('name', $userData['role'])->exists()) {
                // Manually attach the role with business_id = null
                $role = \Spatie\Permission\Models\Role::query()->where('name', $userData['role'])
                    ->where('business_id', null)
                    ->first();

                if ($role) {
                    $user->roles()->attach($role->id, ['business_id' => null]);
                }
            }
        }
    }
}
