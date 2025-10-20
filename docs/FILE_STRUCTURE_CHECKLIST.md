# File Structure & Implementation Checklist

## 🗂️ All Files to Implement (44 total)

### Database Layer (6 files)
```
database/
├─ migrations/
│  ├─ 2025_10_15_091738_create_businesses_table.php
│  ├─ 2025_10_15_092101_create_business_user_table.php
│  └─ 2025_10_15_100327_create_permission_tables.php (Spatie)
├─ factories/
│  └─ BusinessFactory.php
└─ seeders/
   ├─ RoleSeeder.php (Global roles only)
   ├─ PlatformUserSeeder.php (Platform users)
   └─ TenantUserSeeder.php (Demo tenant user)
```

### Configuration (2 files)
```
├─ config/permission.php (Spatie config)
└─ bootstrap/app.php (Middleware registration)
```

### Models (4 files)
```
app/Models/
├─ Business.php (New)
├─ BusinessUser.php (New - Pivot)
├─ User.php (Updated - Add HasRoles trait)
└─ Concerns/BelongsToBusiness.php (Optional - Auto-scoping trait)
```

### Core Service (1 file)
```
app/Services/
└─ TenantResolver.php (Session ↔ Spatie bridge)
```

### Business Logic - Actions (8 files)
```
app/Actions/
├─ CreateBusinessAction.php
├─ UpdateBusinessAction.php
├─ DeleteBusinessAction.php
├─ InviteBusinessMemberAction.php
├─ RemoveBusinessMemberAction.php
├─ AssignBusinessRoleAction.php
└─ SwitchBusinessAction.php
```

### Data Transfer Objects (1 file)
```
app/Data/
└─ BusinessData.php
```

### HTTP - Middleware (3 files)
```
app/Http/Middleware/
├─ SetTenantContext.php (New)
├─ EnsureBusinessContextMatch.php (New)
└─ HandleInertiaRequests.php (Updated)
```

### HTTP - Controllers (3 files)
```
app/Http/Controllers/
├─ BusinessController.php (New)
├─ BusinessSwitchController.php (New)
└─ Admin/DashboardController.php (New - Platform admin)
```

### Authorization - Policies (2 files)
```
app/Policies/
├─ BusinessPolicy.php (New)
└─ UserPolicy.php (New)
```

### Routes (1 file)
```
routes/
└─ web.php (Updated - Add business routes)
```

### Frontend - React Components (10 files)
```
resources/js/
├─ components/
│  ├─ business-switcher.tsx (New)
│  ├─ platform-sidebar.tsx (New)
│  └─ app-sidebar.tsx (Updated)
├─ layouts/
│  ├─ platform-layout.tsx (New)
│  ├─ app-sidebar-layout.tsx (Updated)
│  └─ auth-layout.tsx (May update layout import)
└─ pages/
   ├─ business/
   │  ├─ create.tsx (New)
   │  └─ edit.tsx (New)
   ├─ admin/
   │  └─ dashboard.tsx (New)
   └─ dashboard.tsx (Updated)
```

### Frontend - Types (1 file)
```
resources/js/
└─ types/index.ts (Add Business interface)
```

## 🚀 Implementation Order

### Phase 1: Foundation (Dependencies & Config)
- [ ] Install spatie/laravel-permission
- [ ] Create config/permission.php
- [ ] Update bootstrap/app.php

### Phase 2: Database & Models
- [ ] Create Business model
- [ ] Create migrations (businesses, business_user, permission tables)
- [ ] Create BusinessFactory
- [ ] Update User model
- [ ] Create BusinessUser pivot model

### Phase 3: Core Services
- [ ] Create TenantResolver service
- [ ] Create BusinessData DTO

### Phase 4: Middleware & Authorization
- [ ] Create SetTenantContext middleware
- [ ] Create EnsureBusinessContextMatch middleware
- [ ] Update HandleInertiaRequests middleware
- [ ] Create BusinessPolicy
- [ ] Create UserPolicy

### Phase 5: Business Logic (Actions)
- [ ] CreateBusinessAction
- [ ] UpdateBusinessAction
- [ ] DeleteBusinessAction
- [ ] InviteBusinessMemberAction
- [ ] RemoveBusinessMemberAction
- [ ] AssignBusinessRoleAction
- [ ] SwitchBusinessAction

### Phase 6: Controllers & Routes
- [ ] Create BusinessController
- [ ] Create BusinessSwitchController
- [ ] Create Admin/DashboardController
- [ ] Update web.php routes

### Phase 7: Frontend
- [ ] Add Business type to types/index.ts
- [ ] Create business-switcher.tsx
- [ ] Create platform-sidebar.tsx
- [ ] Update app-sidebar.tsx
- [ ] Create platform-layout.tsx
- [ ] Update app-sidebar-layout.tsx
- [ ] Create business/create.tsx page
- [ ] Create business/edit.tsx page
- [ ] Create admin/dashboard.tsx page
- [ ] Update dashboard.tsx page

### Phase 8: Seeders & Testing
- [ ] Create RoleSeeder
- [ ] Create PlatformUserSeeder
- [ ] Create TenantUserSeeder
- [ ] Run migrations & seeders
- [ ] Test flows manually
- [ ] Update tests to create businesses

## 📋 Quick Links

### Documentation Generated
- `MULTI_TENANCY_ARCHITECTURE.md` - Overview & design
- `MULTI_TENANCY_IMPLEMENTATION_GUIDE.md` - Step-by-step guide
- `MULTI_TENANCY_QUICK_REFERENCE.md` - Checklists & patterns
- `MULTI_TENANCY_ANALYSIS.md` - Technical analysis

### Critical Files
- `app/Services/TenantResolver.php` - Entire context system
- `app/Http/Middleware/SetTenantContext.php` - Request entry point
- `app/Models/Business.php` - Tenant model
- `resources/js/components/business-switcher.tsx` - UX entry point

### Key Helpers to Add to User Model
```php
// Check membership
public function isMemberOf($business)

// Check ownership
public function owns($business)

// Determine user type
public function isPlatformUser()

// Get platform roles
public function globalRoles()

// Check roles
public function hasGlobalRole($role)
public function hasBusinessRole($role, $business)
```

## ⚙️ Configuration Values

```
Permission Config (config/permission.php):
- teams: true
- team_foreign_key: 'business_id'
- team_resolver: App\Services\TenantResolver::class

Session Key:
- current_business_id: (integer)

App Container Key:
- tenant: (Business model instance)

Spatie Role Convention:
- Global roles: business_id = NULL
- Business roles: business_id = {business.id}
```

## 🧪 Testing Checklist

- [ ] Platform user can access admin dashboard
- [ ] Tenant user redirected to business.create if no business
- [ ] Tenant user auto-assigned default business on login
- [ ] Business switcher works (changes session + reloads)
- [ ] Can't access another user's business
- [ ] Can create, update, delete business
- [ ] Can invite/remove members
- [ ] Role assignment scoped per business
- [ ] Platform admin can see all businesses
- [ ] Tests pass with business context

## 🔧 Common Customizations

After basic implementation, you may want to add:

1. **Multiple Businesses Per User** - Already supported via many-to-many
2. **Role Templates** - Pre-defined role sets for new businesses
3. **Invite by Email** - InviteBusinessMemberAction extended
4. **Usage Metrics** - Track per-business actions
5. **Custom Domains** - One domain per business
6. **API Tokens** - Scoped API access per business
7. **Webhooks** - Business event notifications
8. **Audit Logging** - Track all multi-tenant operations
9. **Data Export** - Per-business data download
10. **Backup/Restore** - Per-business data management
