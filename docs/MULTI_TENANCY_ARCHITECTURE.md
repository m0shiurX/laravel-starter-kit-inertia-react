# Multi-Tenancy Architecture

## Overview

This application implements a **session-based, role-scoped multi-tenancy** system where multiple businesses (tenants) share a single authentication system. Users can belong to multiple businesses and switch contexts dynamically. The system uses a single `web` guard for all users, with Spatie Laravel Permission package providing role-based access control scoped to individual businesses.

## Core Concepts

### User Types

**Platform Users**
- Users with roles that have `business_id = NULL` (global roles)
- Access the application without business context requirements
- Typically administrators or support staff who manage platform-wide operations
- Identified via `User::isPlatformUser()` method
- Exempt from business context requirements

**Business Users**
- Users with roles scoped to specific businesses via `business_id` column
- Must have at least one associated business (owner or member)
- Business context automatically managed through session
- Can switch between businesses they have access to
- Default business assigned on first login

### Business Context Management

**Session-Based Context**
- Current business stored in session as `current_business_id`
- Context persists across requests until user switches business
- Context resolved via `TenantResolver` service
- Shared with frontend via Inertia props

**Automatic Business Assignment**
- New users without businesses redirected to business creation
- Existing users have default business set automatically on login
- System prioritizes owned businesses over member businesses

## Architecture Components

### Middleware Stack

**SetTenantContext** (`app/Http/Middleware/SetTenantContext.php`)
- Runs on all authenticated requests
- Loads business context from session
- Skips platform users (no business required)
- Auto-assigns default business or redirects to creation
- Sets business context for permission scoping

**EnsureBusinessContextMatch** (`app/Http/Middleware/EnsureBusinessContextMatch.php`)
- Validates route `{business}` parameters match current context
- Prevents unauthorized cross-business access
- Redirects mismatches to current business
- Applied to business-scoped routes

**HandleInertiaRequests** (`app/Http/Middleware/HandleInertiaRequests.php`)
- Shares business context with React frontend
- Provides authenticated user data
- Exposes current business information to all Inertia pages

### Services

**TenantResolver** (`app/Services/TenantResolver.php`)
- Central service for business context management
- Implements `Spatie\Permission\Teams\TeamResolver` interface
- Manages session-based business storage
- Provides `getCurrentBusiness()` and `setCurrentBusiness()` methods
- Returns `business_id` for permission scoping

### Models

**User** (`app/Models/User.php`)
- Uses Spatie `HasRoles` trait with teams feature
- `businesses()` relationship - owned businesses
- `memberBusinesses()` relationship - member access
- `isPlatformUser()` - checks for global roles
- `globalRoles()` - returns platform role names

**Business** (Implementation details in models directory)
- Represents individual tenants
- Owned by users via `owner_id` foreign key
- Members via many-to-many relationship
- Scope for all permission assignments

### Controllers

**BusinessSwitchController** (`app/Http/Controllers/`)
- Handles business context switching
- Validates user access to target business
- Updates session via TenantResolver
- Determines appropriate redirect destination
- Prevents switching to inaccessible businesses

### Routes

**Business Context Routes**
- Protected by `business.context` middleware (alias for EnsureBusinessContextMatch)
- Include `{business}` parameter in route definitions
- Examples: business settings, business-specific resources
- All validated against current session context

**Global Routes**
- Authentication routes (login, register, password reset)
- User profile routes
- Platform administration (platform users only)
- No business context requirements

## Configuration

**Permission Team Configuration** (`config/permission.php`)
- `teams` feature enabled
- `team_foreign_key` set to `business_id`
- `PermissionsTeamResolver` points to TenantResolver service

**Middleware Registration** (`bootstrap/app.php`)
- SetTenantContext applied via route middleware
- EnsureBusinessContextMatch aliased as `business.context`
- Proper middleware ordering ensures context before authorization

## Directory Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── BusinessSwitchController.php
│   └── Middleware/
│       ├── SetTenantContext.php
│       ├── EnsureBusinessContextMatch.php
│       └── HandleInertiaRequests.php
├── Models/
│   ├── User.php
│   └── Business.php
├── Services/
│   └── TenantResolver.php
└── Providers/
    └── AppServiceProvider.php

config/
└── permission.php

routes/
└── web.php
```

## Key Behaviors

### User Login Flow
1. User authenticates via Fortify
2. SetTenantContext middleware loads business from session
3. If no business in session, auto-assign default business
4. Platform users skip business assignment
5. Users without businesses redirected to create one

### Business Switching Flow
1. User requests business switch via BusinessSwitchController
2. System validates user has access to target business
3. TenantResolver updates session with new business_id
4. User redirected to appropriate page in new business context
5. Subsequent requests use new business context

### Permission Checking
1. Permission queries automatically scoped by current business_id
2. TenantResolver provides business_id to Spatie Permission
3. Platform roles (business_id = NULL) work globally
4. Business roles (business_id = X) work only in that business

### Frontend Context
1. HandleInertiaRequests shares business data with React
2. Frontend receives current business object in props
3. Inertia requests maintain business context
4. Business switcher component shows available businesses

## Security Considerations

**Cross-Business Access Prevention**
- EnsureBusinessContextMatch middleware validates all business routes
- Route parameters must match current session context
- Attempts to access wrong business redirect to current business
- Prevents URL manipulation attacks

**Role Isolation**
- Business-scoped roles cannot affect other businesses
- Platform roles isolated from business operations
- Permission checks always include business_id scope
- Database constraints prevent cross-business data leaks

**Session Management**
- Business context stored securely in Laravel session
- Session invalidation on logout clears business context
- Session regeneration on login prevents fixation
- Context validation on every request

## Testing Strategy

**Test Data Preparation**
- Create businesses for all non-platform test users
- Use `Business::factory()->create(['owner_id' => $user->id])`
- Ensures middleware doesn't redirect during tests
- Platform users skip business creation in tests

**Business Context Tests**
- Validate switching between businesses
- Test access control across business boundaries
- Verify middleware prevents unauthorized access
- Confirm platform users bypass business requirements

**Permission Tests**
- Test role scoping within businesses
- Verify platform roles work globally
- Confirm users can't access other businesses' resources
- Test permission inheritance and cascading

## Migration Guidance

**Adding Business-Scoped Features**
1. Add business_id foreign key to new tables
2. Scope queries by current business context
3. Apply `business.context` middleware to routes
4. Include `{business}` parameter in route definitions
5. Update frontend to use business-aware navigation

**Creating Platform Features**
1. Skip business context requirements in middleware
2. Create global roles (business_id = NULL)
3. Check `isPlatformUser()` before allowing access
4. Use separate route groups for platform admin
5. Document platform-only features clearly
