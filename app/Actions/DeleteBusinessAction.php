<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Business;
use Illuminate\Support\Facades\DB;

final readonly class DeleteBusinessAction
{
    /**
     * Delete a business and clean up relationships.
     */
    public function handle(Business $business): void
    {
        DB::transaction(function () use ($business): void {
            // Detach all users
            $business->users()->detach();

            // Delete business-scoped roles
            $business->roles()->delete();

            // Delete the business
            $business->delete();
        });
    }
}
