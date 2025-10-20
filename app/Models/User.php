<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $email
 * @property-read CarbonInterface|null $email_verified_at
 * @property-read string $password
 * @property-read string|null $remember_token
 * @property-read string|null $two_factor_secret
 * @property-read string|null $two_factor_recovery_codes
 * @property-read CarbonInterface|null $two_factor_confirmed_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Business> $businesses
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Business> $ownedBusinesses
 */
final class User extends Authenticatable implements MustVerifyEmail
{
    /**
     * @use HasFactory<UserFactory>
     */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'name' => 'string',
            'email' => 'string',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'remember_token' => 'string',
            'two_factor_secret' => 'string',
            'two_factor_recovery_codes' => 'string',
            'two_factor_confirmed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * All businesses this user is a member of.
     *
     * @return BelongsToMany<Business, $this>
     */
    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'business_user')
            ->using(BusinessUser::class)
            ->withTimestamps();
    }

    /**
     * Businesses owned by this user.
     *
     * @return HasMany<Business, $this>
     */
    public function ownedBusinesses(): HasMany
    {
        return $this->hasMany(Business::class, 'owner_id');
    }

    /**
     * Check if user has any global role (platform-level).
     */
    public function hasGlobalRole(string $role): bool
    {
        return $this->hasRole($role, null);
    }

    /**
     * Check if user has a role in a specific business.
     */
    public function hasBusinessRole(string $role, Business|int $business): bool
    {
        $businessId = $business instanceof Business ? $business->id : $business;

        // Temporarily set the permissions team ID
        $originalTeamId = app(\Spatie\Permission\PermissionRegistrar::class)->getPermissionsTeamId();
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($businessId);

        $hasRole = $this->hasRole($role);

        // Restore original team ID
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($originalTeamId);

        return $hasRole;
    }

    /**
     * Check if user is member of a business.
     */
    public function isMemberOf(Business|int $business): bool
    {
        $businessId = $business instanceof Business ? $business->id : $business;

        return $this->businesses()->where('businesses.id', $businessId)->exists();
    }

    /**
     * Check if user owns a business.
     */
    public function owns(Business|int $business): bool
    {
        $businessId = $business instanceof Business ? $business->id : $business;

        return $this->ownedBusinesses()->where('id', $businessId)->exists();
    }

    /**
     * Check if user is a platform user (has any global role).
     */
    public function isPlatformUser(): bool
    {
        return $this->roles()
            ->whereNull('roles.business_id')
            ->exists();
    }

    /**
     * Get all global (platform) roles for this user.
     */
    public function globalRoles(): array
    {
        return $this->roles()
            ->whereNull('roles.business_id')
            ->pluck('name')
            ->toArray();
    }
}
