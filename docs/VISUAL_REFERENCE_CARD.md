# Multi-Tenancy Visual Reference Card

## 🎯 One-Page Overview

### Context Flow Diagram
```
┌─────────────┐
│   Request   │
└──────┬──────┘
       ▼
┌─────────────────────────────────┐
│ SetTenantContext Middleware     │
├─────────────────────────────────┤
│ ✓ Load business from session    │
│ ✓ Auto-assign if missing        │
│ ✓ Skip for platform users       │
└──────┬──────────────────────────┘
       ▼
┌─────────────────────────────────┐
│ EnsureBusinessContextMatch      │
├─────────────────────────────────┤
│ ✓ Validate {business} in route  │
│ ✓ Match with session context    │
│ ✓ Prevent cross-tenant access   │
└──────┬──────────────────────────┘
       ▼
┌─────────────────────────────────┐
│ Controller + Action             │
├─────────────────────────────────┤
│ ✓ Execute business logic        │
│ ✓ Check policies                │
│ ✓ Manage relationships           │
└──────┬──────────────────────────┘
       ▼
┌─────────────────────────────────┐
│ HandleInertiaRequests           │
├─────────────────────────────────┤
│ ✓ Share currentBusiness         │
│ ✓ Share businesses list         │
│ ✓ Share auth info               │
└──────┬──────────────────────────┘
       ▼
┌─────────────┐
│  Response   │
└─────────────┘
```

### Data Model
```
┌──────────────────────────────────────────┐
│             businesses                   │
├──────────────────────────────────────────┤
│ id (PK)                                  │
│ owner_id (FK → users)                    │
│ name                                     │
│ created_at, updated_at                   │
└──────────────────────────────────────────┘
              │
              ├─ owns ──→ ┌──────────────┐
              │           │    users     │
              │           └──────────────┘
              │
              └─ has_many ──→ business_users (pivot)
                              ├──────────────────────┐
                              │ id (PK)              │
                              │ business_id (FK)     │
                              │ user_id (FK)         │
                              │ created_at, updated  │
                              └──────────────────────┘

┌──────────────────────────────────────────┐
│               roles                      │
├──────────────────────────────────────────┤
│ id (PK)                                  │
│ name                                     │
│ guard_name = 'web'                       │
│ business_id (NULL = global, X = scoped)  │
└──────────────────────────────────────────┘
```

### Middleware Order (bootstrap/app.php)
```php
$middleware->web(append: [
    HandleAppearance::class,
    SetTenantContext::class,        // 1st: Load business
    HandleInertiaRequests::class,   // 2nd: Share data
    AddLinkHeadersForPreloadedAssets::class,
]);

$middleware->alias([
    'business.context' => EnsureBusinessContextMatch::class,
]);
```

### User Types Decision Tree
```
┌─ User has role with business_id = NULL?
│  ├─ YES → Platform User
│  │  ├─ Can access all businesses
│  │  ├─ See platform admin pages
│  │  └─ Skip business requirements
│  │
│  └─ NO → Business User
│     ├─ Must have ≥1 business
│     ├─ Roles scoped per business
│     └─ Can switch between businesses
```

### Permission Check Hierarchy
```
1. hasGlobalRole('super-admin')?
   └─ YES → Allow (bypass everything)

2. Is User a Member of Business?
   └─ NO → Deny (check membership)

3. hasBusinessRole('action', $business)?
   └─ YES → Allow (permission granted)

4. Fallback → Deny (no access)
```

### Route Protection Patterns
```
// Public routes (no auth needed)
Route::get('/', ...);

// Auth required, no context needed
Route::middleware('auth')->group(function () {
    Route::get('dashboard', ...);
    Route::delete('user', ...);
});

// Auth + Platform only
Route::middleware(['auth', 'can:viewPlatformDashboard'])
    ->get('admin/dashboard', ...);

// Auth + Business context required
Route::middleware(['auth', 'business.context'])->group(function () {
    Route::get('businesses/{business}/edit', ...);
    Route::patch('businesses/{business}', ...);
    Route::delete('businesses/{business}', ...);
});
```

### Common Operations Checklist

#### Create Business
- [ ] Check user authenticated
- [ ] Create Business with owner_id
- [ ] Attach user to business_user pivot
- [ ] Create 'owner' role with business_id
- [ ] Assign role to user with setPermissionsTeamId()
- [ ] Set as current context via TenantResolver
- [ ] Redirect to dashboard

#### Invite Member
- [ ] Check user is owner or has admin role
- [ ] Attach user to business_user pivot
- [ ] Create or get role for this business
- [ ] Assign role with setPermissionsTeamId()
- [ ] Notify user (optional)

#### Switch Business
- [ ] Check user is member of target business
- [ ] Update session: session(['current_business_id' => $business->id])
- [ ] Update app container: app()->instance('tenant', $business)
- [ ] Intelligent redirect based on context
- [ ] Frontend re-renders with new data

### Frontend Props (Inertia)
```javascript
// Available in all components
const {
    currentBusiness,        // Business | null
    businesses,            // Business[]
    auth: {
        user,              // User
        isPlatformUser,    // boolean
        globalRoles,       // string[]
    }
} = usePage().props;

// Usage
const isBusiness = !auth.isPlatformUser;
const businessName = currentBusiness?.name || 'No Business';
```

### Session & Container Keys
```php
// Session (persistent across requests)
session('current_business_id')      // int | null
session('url.intended')              // string | null

// App Container (request-only cache)
app('tenant')                        // Business | null
app(PermissionRegistrar::class)
    ->getPermissionsTeamId()         // string (business_id)
```

### SQL Query Patterns
```php
// Get all businesses for user
$user->businesses()->get();

// Get businesses owned by user
$user->ownedBusinesses()->get();

// Get users in business
$business->users()->get();

// Check membership
$business->hasMember($user);        // bool
$user->isMemberOf($business);       // bool

// Get business-scoped roles
$business->roles()->get();

// Query without business scope (admin)
User::withoutBusinessScope()
    ->forAllBusinesses()
    ->get();

// Query for specific business
User::forBusiness($business)->get();
```

### Common Errors & Fixes
```
❌ "Route [business.create] not found"
✓ Check route name is 'business.create' (singular)
✓ Verify route is registered in routes/web.php
✓ Clear route cache: php artisan route:clear

❌ "User is not a member of this business"
✓ Ensure business_user pivot row exists
✓ Check hasMember() logic in Business model
✓ Verify user was attached via relationships

❌ "Permission denied accessing business"
✓ Check policy methods (view, update, delete)
✓ Verify user has required role
✓ Check permission setup in seeder

❌ "Business context keeps resetting"
✓ Ensure SetTenantContext middleware is added
✓ Check session storage is working
✓ Verify app()->instance() in TenantResolver

❌ "Platform user sees business switcher"
✓ Check isPlatformUser() returns correct value
✓ Verify role has business_id = NULL
✓ Ensure platform layout is used in AppSidebar
```

### Performance Tips
```
✓ Use eager loading for businesses in sidebar
  $user->businesses()->get()  // Do this in list views

✗ Don't query business info on every request
  ✓ Load once in middleware, cache in app container

✓ Use exists() for membership checks
  $user->businesses()->where('id', $business->id)->exists()

✗ Don't loop through user->businesses in queries
  ✓ Use 'business.context' middleware instead

✓ Leverage permission cache (24h default)
  Check config('permission.cache.expiration_time')
```

### Testing Snippet
```php
// Tenant user test
$user = User::factory()->create();
$business = Business::factory()->create(['owner_id' => $user->id]);

$this->actingAs($user)
    ->get('/dashboard')
    ->assertOk();

// Platform user test  
$admin = User::factory()->create();
$admin->assignRole('super-admin', null);  // business_id = NULL

$this->actingAs($admin)
    ->get('/admin/dashboard')
    ->assertOk();

// Cross-business protection
$user1 = User::factory()->create();
$business1 = Business::factory()->create(['owner_id' => $user1->id]);

$user2 = User::factory()->create();
$business2 = Business::factory()->create(['owner_id' => $user2->id]);

$this->actingAs($user2)
    ->get("/businesses/{$business1->id}/edit")
    ->assertForbidden();
```

---

**Print this card and keep it by your desk while implementing!** 📋
