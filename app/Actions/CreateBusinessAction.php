<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\BusinessData;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final readonly class CreateBusinessAction
{
    /**
     * Create a new business and assign the owner role to the creator.
     */
    public function handle(User $user, BusinessData $data): Business
    {
        return DB::transaction(function () use ($user, $data): Business {
            // Create the business
            $business = Business::query()->create([
                'name' => $data->name,
                'owner_id' => $user->id,
            ]);

            // Attach user to business
            $business->users()->attach($user);

            // Ensure 'owner' role exists for this business
            $ownerRole = Role::query()->firstOrCreate([
                'name' => 'owner',
                'guard_name' => 'web',
                'business_id' => $business->id,
            ]);

            // Set team context and assign owner role to user
            app(PermissionRegistrar::class)->setPermissionsTeamId($business->id);
            $user->assignRole($ownerRole);

            return $business;
        });
    }
}
