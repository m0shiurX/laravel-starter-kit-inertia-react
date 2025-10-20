# Multi-Tenancy Implementation Guide

A step-by-step guide to add session-based, role-scoped multi-tenancy to any Laravel-React project.

## 1. Dependencies

Install required packages:
```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

## 2. Database Migrations

**Create businesses table:**
```bash
php artisan make:migration create_businesses_table
```

**Create business_user pivot table:**
```bash
php artisan make:migration create_business_user_table
```

**Update Spatie permission tables** to include `business_id` column in `roles`, `model_has_roles`, and `model_has_permissions` tables.

## 3. Configuration

Edit `config/permission.php`:
```php
'teams' => true,
'column_names' => [
    'team_foreign_key' => 'business_id',
],
'team_resolver' => App\Services\TenantResolver::class,
```

## 4. Core Service

Create `app/Services/TenantResolver.php`:
- Implements `Spatie\Permission\Contracts\PermissionsTeamResolver`
- Manages session-based business context: `session('current_business_id')`
- Provides `getCurrentBusiness()`, `setCurrentBusiness()`, `getPermissionsTeamId()`
- Caches business in app container via `app()->instance('tenant', $business)`

## 5. Models

**Business model** (`app/Models/Business.php`):
- `owner_id` foreign key to users
- `users()` belongs-to-many relationship (includes owner)
- Methods: `hasMember()`, `isOwner()`, `roles()`

**User model updates** (`app/Models/User.php`):
- Add `HasRoles` trait from Spatie
- Add relationships: `businesses()`, `ownedBusinesses()`
- Add methods:
  - `isMemberOf($business)` - check membership
  - `owns($business)` - check ownership
  - `isPlatformUser()` - check for global roles (business_id = NULL)
  - `globalRoles()` - get platform roles
  - `hasGlobalRole($role)` - check global role
  - `hasBusinessRole($role, $business)` - check business role

**Optional trait** (`app/Models/Concerns/BelongsToBusiness.php`):
- Auto-scope queries by current business context
- Auto-set business_id on creation
- Methods: `withoutBusinessScope()`, `forBusiness()`, `forAllBusinesses()`

## 6. Middleware Stack

### SetTenantContext
- Runs on all authenticated requests
- Loads business from session or assigns default
- Skips platform users (checks `isPlatformUser()`)
- Redirects to `business.create` if no business exists

### EnsureBusinessContextMatch
- Validates route `{business}` parameter matches session context
- Auto-switches to route business if accessible
- Applied via `business.context` route middleware

### HandleInertiaRequests updates
- Share `currentBusiness`, `businesses`, `isPlatformUser`, `globalRoles` with frontend

## 7. Actions (Business Logic)

Create in `app/Actions/`:

- **CreateBusinessAction** - Create business + attach user + assign owner role
- **UpdateBusinessAction** - Update business name
- **DeleteBusinessAction** - Delete business + detach users + delete roles
- **InviteBusinessMemberAction** - Attach user + assign role
- **RemoveBusinessMemberAction** - Detach user + remove roles (prevent owner removal)
- **AssignBusinessRoleAction** - Change member role within business
- **SwitchBusinessAction** - Update session context

Key: Use `PermissionRegistrar::setPermissionsTeamId($business->id)` when assigning/removing roles.

## 8. Controllers

**BusinessController**:
- `create()` - Show business creation form
- `store(BusinessData, CreateBusinessAction)` - Create + set as current context
- `edit(Business)` - Show edit form
- `update(BusinessData, Business, UpdateBusinessAction)` - Update business
- `destroy(Business, DeleteBusinessAction)` - Delete business

**BusinessSwitchController**:
- `switch(Business, SwitchBusinessAction, Request)` - Change business context
- Smart redirect: dashboard if switching from business-specific page, else reload current page

**Admin\DashboardController** (platform-only):
- Authorize via `viewPlatformDashboard` policy
- Show platform stats and recent activity

## 9. Policies

**BusinessPolicy**:
- `view()` - User is member OR global super-admin
- `update()` - User is owner OR has admin role OR global super-admin
- `delete()` - User is owner OR global super-admin

**UserPolicy**:
- `viewPlatformDashboard()` - Has global role (super-admin, admin, or manager)

Register policies in `AuthServiceProvider` or use auto-discovery.

## 10. Routes

```php
Route::middleware(['auth', 'verified'])->group(function () {
    // Platform admin
    Route::prefix('admin')->group(function () {
        Route::get('dashboard', AdminDashboardController::class);
    });

    // Business creation (for users without business)
    Route::get('businesses/create', [BusinessController::class, 'create'])->name('business.create');
    Route::post('businesses', [BusinessController::class, 'store'])->name('business.store');

    // Business context-matched routes
    Route::middleware('business.context')->group(function () {
        Route::get('businesses/{business}/edit', [BusinessController::class, 'edit']);
        Route::patch('businesses/{business}', [BusinessController::class, 'update']);
        Route::delete('businesses/{business}', [BusinessController::class, 'destroy']);
    });

    // Business switching
    Route::post('business/switch/{business}', [BusinessSwitchController::class, 'switch']);
});
```

## 11. Frontend Components

**BusinessSwitcher** - Select component:
- Show current business
- List all user businesses
- Option to create new business
- POST to `/business/switch/{id}` on change

**AppSidebar** - Business users:
- Include BusinessSwitcher in header
- Show business-specific nav items (Settings)

**PlatformSidebar** - Platform users:
- Different layout without business switcher
- Show global role badge (super-admin, admin, manager)
- Platform admin links (Users, Roles, Businesses)

**Dashboard** - Show context info:
- User type (Platform/Business)
- Current business name
- Authentication guard info

## 12. Seeders

**RoleSeeder**:
- Create global roles: super-admin, admin, manager (business_id = NULL)
- Business-scoped roles created on-demand (owner, admin, manager per business)

**PlatformUserSeeder**:
- Create platform users with global roles
- Use raw role attachment with business_id = NULL

**TenantUserSeeder**:
- Create demo user + demo business
- Assign owner role to user for the business

## 13. Data Transfer Objects (DTOs)

**BusinessData** (Spatie LaravelData):
```php
public function __construct(
    public ?int $id = null,
    #[Required, StringType, Max(255)]
    public ?string $name = null,
    public ?int $owner_id = null,
) {}
```

## 14. Testing

For all tests creating authenticated users without platforms roles:
```php
$user = User::factory()->create();
Business::factory()->create(['owner_id' => $user->id]);

$this->actingAs($user)->get('/dashboard');
```

Platform user tests skip business creation (use `isPlatformUser()`).

## 15. Key Architectural Decisions

| Aspect | Decision | Why |
|--------|----------|-----|
| Authentication | Single `web` guard | All users (platform + tenant) use same guard |
| Context Storage | Session + app container | Fast access, survives requests, cleared on logout |
| Role Scoping | `business_id` column in roles table | Spatie-native teams feature, automatic scoping |
| User Types | Platform (business_id=NULL) + Business (scoped) | Platform admins need global access |
| Redirect Strategy | Middleware auto-assigns default business | Seamless UX, users never see "no business" state |
| Authorization | Policies + direct method calls | Explicit, testable, Laravel convention |

## Troubleshooting

**Q: Users keep getting redirected to business.create**
- A: Ensure user has business AND is attached via `business_user` pivot. Check `isPlatformUser()` returns correct value.

**Q: Business context doesn't match**
- A: Verify `current_business_id` in session. Check `EnsureBusinessContextMatch` middleware is applied to route.

**Q: Roles not scoping correctly**
- A: Ensure `PermissionRegistrar::setPermissionsTeamId()` is called BEFORE role assignment. Check `team_foreign_key` in config.

**Q: Platform users can't access platform routes**
- A: Verify policy `viewPlatformDashboard` checks `hasGlobalRole()`. Ensure platform user has role with business_id = NULL.

## Next Steps

1. Add business member management UI (invite/remove)
2. Implement permission-based features per role
3. Add audit logging for multi-tenant operations
4. Implement tenant data isolation at query level
5. Add impersonation for platform admins
