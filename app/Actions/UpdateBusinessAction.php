<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\BusinessData;
use App\Models\Business;

final readonly class UpdateBusinessAction
{
    /**
     * Update business details.
     */
    public function handle(Business $business, BusinessData $data): Business
    {
        $business->update([
            'name' => $data->name,
        ]);

        return $business->fresh();
    }
}
