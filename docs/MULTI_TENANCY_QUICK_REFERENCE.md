# Multi-Tenancy Quick Reference

## File Checklist

### Database
- [ ] `database/migrations/*_create_businesses_table.php` - Business CRUD
- [ ] `database/migrations/*_create_business_user_table.php` - Pivot table with unique constraint
- [ ] `database/migrations/*_create_permission_tables.php` - Spatie (includes business_id)
- [ ] `database/factories/BusinessFactory.php` - Factory with owner_id
- [ ] `database/seeders/RoleSeeder.php` - Global roles (business_id = NULL)
- [ ] `database/seeders/PlatformUserSeeder.php` - Platform users with global roles
- [ ] `database/seeders/TenantUserSeeder.php` - Tenant user with business

### Configuration
- [ ] `config/permission.php` - `teams: true`, `team_foreign_key: business_id`, `team_resolver`
- [ ] `bootstrap/app.php` - Add `SetTenantContext`, `EnsureBusinessContextMatch` middleware

### Core Services & Models
- [ ] `app/Services/TenantResolver.php` - Session + app container management
- [ ] `app/Models/Business.php` - Relationships: owner, users, roles
- [ ] `app/Models/BusinessUser.php` - Pivot model (optional, for auto-increment)
- [ ] `app/Models/Concerns/BelongsToBusiness.php` - Auto-scoping trait (optional)
- [ ] `app/Models/User.php` - Add HasRoles, businesses relationships, helper methods

### Actions (Business Logic)
- [ ] `app/Actions/CreateBusinessAction.php` - Create + assign owner
- [ ] `app/Actions/UpdateBusinessAction.php` - Update name
- [ ] `app/Actions/DeleteBusinessAction.php` - Delete + cleanup
- [ ] `app/Actions/InviteBusinessMemberAction.php` - Add member + assign role
- [ ] `app/Actions/RemoveBusinessMemberAction.php` - Remove member + roles
- [ ] `app/Actions/AssignBusinessRoleAction.php` - Change role
- [ ] `app/Actions/SwitchBusinessAction.php` - Update session context

### Data Transfer Objects
- [ ] `app/Data/BusinessData.php` - Business validation & casting

### HTTP
- [ ] `app/Http/Middleware/SetTenantContext.php` - Load/assign business
- [ ] `app/Http/Middleware/EnsureBusinessContextMatch.php` - Validate route business
- [ ] `app/Http/Middleware/HandleInertiaRequests.php` - Share context with frontend
- [ ] `app/Http/Controllers/BusinessController.php` - CRUD operations
- [ ] `app/Http/Controllers/BusinessSwitchController.php` - Context switching
- [ ] `app/Http/Controllers/Admin/DashboardController.php` - Platform dashboard
- [ ] `routes/web.php` - Routes with middleware

### Authorization
- [ ] `app/Policies/BusinessPolicy.php` - view, update, delete
- [ ] `app/Policies/UserPolicy.php` - viewPlatformDashboard, view, create, update, delete

### Frontend Components
- [ ] `resources/js/components/business-switcher.tsx` - Select dropdown
- [ ] `resources/js/components/platform-sidebar.tsx` - Admin sidebar
- [ ] `resources/js/components/app-sidebar.tsx` - Update with BusinessSwitcher
- [ ] `resources/js/layouts/platform-layout.tsx` - Platform admin layout
- [ ] `resources/js/layouts/app-sidebar-layout.tsx` - Support custom sidebar
- [ ] `resources/js/pages/business/create.tsx` - Create form
- [ ] `resources/js/pages/business/edit.tsx` - Edit form
- [ ] `resources/js/pages/admin/dashboard.tsx` - Platform stats
- [ ] `resources/js/pages/dashboard.tsx` - Show context info

### Types
- [ ] Update `resources/js/types/index.ts` - Add `Business` interface

## Context Flow Diagram

```
Request
  ↓
SetTenantContext Middleware
  ├─ Loaded from session? ✓ → Continue
  ├─ Not loaded → Auto-assign default business
  └─ Platform user? → Skip requirement
  ↓
User is authenticated + business context set
  ↓
EnsureBusinessContextMatch (if route has {business})
  ├─ Route business matches session? → Continue
  ├─ Not matched → Redirect to dashboard
  └─ No business in session → Try to set from route
  ↓
HandleInertiaRequests Middleware
  └─ Share: currentBusiness, businesses, isPlatformUser, globalRoles
  ↓
Response to Frontend
  ↓
Frontend Uses Context
  ├─ Show BusinessSwitcher if business user
  ├─ Show PlatformSidebar if platform user
  └─ Render components with shared data
```

## Role Resolution Order

1. **Check Global Role** (business_id = NULL)
   - Platform users: role applies globally
   - Example: 'super-admin' can access anything

2. **Check Business-Scoped Role** (business_id = X)
   - User has role in current business context
   - Example: 'owner' of Business #5 can edit Business #5

3. **Fallback**
   - No access unless policy allows

## Permission Check Examples

```php
// Check platform role
$user->hasGlobalRole('super-admin')

// Check business role
$user->hasBusinessRole('owner', $business)

// Membership check
$user->isMemberOf($business)
$user->owns($business)

// Determine user type
$user->isPlatformUser() // bool

// In policies
if ($user->isMemberOf($business)) { ... }
if ($user->hasGlobalRole('super-admin')) { ... }
```

## Session Keys

- `current_business_id` - Integer business ID for current context
- `url.intended` - Redirect after business creation

## Common Mistakes

❌ Forgetting to set `business_id = NULL` for global roles
❌ Not calling `PermissionRegistrar::setPermissionsTeamId()` before role operations
❌ Using `hasRole()` without business context for tenant roles
❌ Missing `business.context` middleware on business-scoped routes
❌ Creating users without businesses in tests
❌ Not attaching user to business pivot table
❌ Querying without `BelongsToBusiness` trait or manual scoping

## Testing Patterns

```php
// Tenant user
$user = User::factory()->create();
$business = Business::factory()->create(['owner_id' => $user->id]);
$this->actingAs($user)->get('/dashboard');

// Platform user
$user = User::factory()->create();
$user->assignRole('super-admin', null); // business_id = NULL
$this->actingAs($user)->get('/admin/dashboard');

// Cross-business access
$user1 = User::factory()->create();
$business1 = Business::factory()->create(['owner_id' => $user1->id]);
$user2 = User::factory()->create();
$business2 = Business::factory()->create(['owner_id' => $user2->id]);

// user2 should not access user1's business
$this->actingAs($user2)
    ->get("/businesses/{$business1->id}/edit")
    ->assertForbidden(); // Or redirects to dashboard
```

## Performance Considerations

- **Caching**: Business cached in app container after first query
- **Session**: Current business stored in lightweight session
- **Query Scoping**: Use `BelongsToBusiness` trait for auto-scoping
- **Role Checks**: Spatie caches permissions by default (24 hours)
- **N+1 Queries**: Eager load businesses in sidebar: `$user->businesses`

## Migration Path from Single-Tenant

1. Create Business model + pivot + migrations
2. Create platform admin user with super-admin role
3. Create initial business for existing users (owner_id = user.id)
4. Migrate users from having global roles to business roles
5. Add context loading to existing middleware
6. Update routes to use middleware aliases
7. Gradually migrate frontend components
