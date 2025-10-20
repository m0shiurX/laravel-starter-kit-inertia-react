<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Business;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Trait for models that belong to a business (tenant).
 *
 * Automatically scopes queries to the current business context.
 * Can be bypassed for global admin access.
 *
 * @mixin Model
 */
trait BelongsToBusiness
{
    /**
     * Boot the trait and add global scope.
     */
    public static function bootBelongsToBusiness(): void
    {
        // Add global scope to filter by business_id
        static::addGlobalScope('business', function (Builder $builder): void {
            $businessId = session('current_business_id');

            // Only apply scope if there's an active business context
            if ($businessId !== null) {
                $builder->where($builder->getModel()->getQualifiedBusinessIdColumn(), $businessId);
            }
        });

        // Automatically set business_id when creating
        static::creating(function (Model $model): void {
            if (! $model->isDirty('business_id')) {
                $businessId = session('current_business_id');

                throw_if($businessId === null, RuntimeException::class, 'Cannot create '.class_basename($model).' without active business context. '.
                    'Please ensure a business is set in the session.');

                $model->setAttribute('business_id', $businessId);
            }
        });
    }

    /**
     * Query without the business scope (for global admin).
     */
    public static function withoutBusinessScope(): Builder
    {
        return static::withoutGlobalScope('business');
    }

    /**
     * Query for all businesses (for global admin).
     */
    public static function forAllBusinesses(): Builder
    {
        return static::withoutGlobalScope('business');
    }

    /**
     * Query for a specific business.
     */
    public static function forBusiness(Business|int $business): Builder
    {
        $businessId = $business instanceof Business ? $business->id : $business;

        return static::withoutGlobalScope('business')
            ->where('business_id', $businessId);
    }

    /**
     * Get the business this model belongs to.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the fully qualified business_id column name.
     */
    public function getQualifiedBusinessIdColumn(): string
    {
        return $this->qualifyColumn('business_id');
    }
}
