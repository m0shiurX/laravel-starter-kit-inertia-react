<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Business;
use App\Models\User;
use App\Services\TenantResolver;
use InvalidArgumentException;

final readonly class SwitchBusinessAction
{
    public function __construct(private TenantResolver $resolver) {}

    /**
     * Switch the user's active business context.
     */
    public function handle(User $user, Business $business): void
    {
        // Verify user has access to this business
        throw_unless($user->isMemberOf($business), InvalidArgumentException::class, 'User does not have access to this business.');

        // Set the new business context
        $this->resolver->setCurrentBusiness($business);
    }
}
