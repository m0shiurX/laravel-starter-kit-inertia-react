# CRITICAL SECURITY RECOMMENDATIONS

**Priority:** 🔴 HIGH - Implement Before Production Deployment

---

## 1. Enable Session Encryption ⚠️

**Severity:** HIGH  
**Effort:** 5 minutes  
**File:** `.env`

### Current State
```env
SESSION_ENCRYPT=false
```

### Required Change
```env
SESSION_ENCRYPT=true
```

### Why This Matters
Without session encryption, sensitive data in sessions (including authentication state) can be:
- Tampered with by attackers
- Read if storage is compromised
- Vulnerable to session fixation attacks

### Impact
- Protects user sessions from manipulation
- Prevents unauthorized access
- Meets security compliance requirements

---

## 2. Add CSRF Protection to Frontend Fetch Calls 🛡️

**Severity:** HIGH  
**Effort:** 30 minutes  
**File:** `resources/js/hooks/use-two-factor-auth.ts`

### Current Issue
```typescript
const fetchJson = async <T>(url: string): Promise<T> => {
    const response = await fetch(url, {
        headers: { Accept: 'application/json' },
    });
    // ❌ No CSRF token for state-changing requests
```

### Recommended Fix
```typescript
const fetchJson = async <T>(
    url: string,
    options: RequestInit = {}
): Promise<T> => {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    const defaultHeaders: HeadersInit = {
        Accept: 'application/json',
    };

    // Add CSRF token for non-GET requests
    if (options.method && options.method !== 'GET' && csrfToken) {
        defaultHeaders['X-CSRF-TOKEN'] = csrfToken;
    }

    const response = await fetch(url, {
        ...options,
        headers: {
            ...defaultHeaders,
            ...options.headers,
        },
    });

    if (!response.ok) {
        throw new Error(`Failed to fetch: ${response.status}`);
    }

    return response.json();
};
```

### Why This Matters
- Prevents Cross-Site Request Forgery attacks
- Ensures state-changing operations are intentional
- Laravel expects CSRF tokens on POST/PUT/DELETE requests

### Additional Notes
Make sure your layout has the CSRF token meta tag:
```blade
<!-- resources/views/app.blade.php -->
<meta name="csrf-token" content="{{ csrf_token() }}">
```

---

## 3. Configure Security Headers 🔒

**Severity:** MEDIUM-HIGH  
**Effort:** 15 minutes  
**File:** Create new middleware or update `bootstrap/app.php`

### Implementation Option 1: Middleware

Create `app/Http/Middleware/SetSecurityHeaders.php`:
```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class SetSecurityHeaders
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        
        // Add Content Security Policy
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';"
        );

        return $response;
    }
}
```

Register in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(SetSecurityHeaders::class);
})
```

### Implementation Option 2: Package

Install a security headers package:
```bash
composer require bepsvpt/secure-headers
```

Then configure in `config/secure-headers.php`.

### Why This Matters
- **X-Frame-Options:** Prevents clickjacking attacks
- **X-Content-Type-Options:** Prevents MIME-sniffing attacks
- **X-XSS-Protection:** Enables browser XSS protection
- **Referrer-Policy:** Controls information leaked in referrer header
- **Permissions-Policy:** Restricts browser features
- **Content-Security-Policy:** Prevents XSS and data injection attacks

---

## 4. Secure Cookie Configuration 🍪

**Severity:** HIGH (Production)  
**Effort:** 5 minutes  
**File:** `.env`

### Production Settings
```env
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

### Why This Matters
- **Secure:** Cookies only sent over HTTPS
- **HttpOnly:** JavaScript cannot access cookies (prevents XSS)
- **SameSite:** Prevents CSRF attacks

### Important Note
Only enable `SESSION_SECURE_COOKIE=true` in production with HTTPS. Keep it false in local development.

---

## 5. Improve Error Handling and Information Disclosure 🚨

**Severity:** MEDIUM  
**Effort:** 20 minutes  
**Files:** Multiple TypeScript files

### Current Issue
```typescript
catch {
    setErrors((prev) => [...prev, 'Failed to fetch QR code']);
}
```

### Recommended Pattern
```typescript
catch (error) {
    // Log for developers (never shown to users)
    if (import.meta.env.DEV) {
        console.error('QR Code fetch error:', error);
    }
    
    // Generic message for users
    setErrors((prev) => [
        ...prev, 
        'Unable to load QR code. Please try again or contact support.'
    ]);
    
    // Optional: Send to error tracking service
    // Sentry.captureException(error);
}
```

### Why This Matters
- Detailed error messages can leak system information
- Stack traces expose internal architecture
- Error messages help attackers understand vulnerabilities

### Environment Configuration
```env
# Production
APP_DEBUG=false
LOG_LEVEL=error

# Development
APP_DEBUG=true
LOG_LEVEL=debug
```

---

## 6. Rate Limiting Enhancement 🚦

**Severity:** MEDIUM  
**Effort:** 15 minutes  
**File:** `routes/web.php` and rate limiter configuration

### Current State
Some routes have rate limiting, but it could be more comprehensive.

### Recommended Enhancement
```php
// Add to RouteServiceProvider or bootstrap/app.php
RateLimiter::for('two-factor', function (Request $request) {
    return Limit::perMinute(3)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

// Add to sensitive routes
Route::post('two-factor/confirm', [/* ... */])
    ->middleware('throttle:two-factor');
```

### Why This Matters
- Prevents brute force attacks
- Protects against DoS
- Limits automated abuse

---

## 7. Password Validation Strengthening 🔑

**Severity:** MEDIUM  
**Effort:** 10 minutes  
**File:** User creation/password update requests

### Current Validation
Check if password rules are strong enough.

### Recommended Rules
```php
use Illuminate\Validation\Rules\Password;

return [
    'password' => [
        'required',
        'confirmed',
        Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised(), // Checks against data breaches
    ],
];
```

### Why This Matters
- Prevents weak passwords
- Checks against known breaches
- Enforces complexity requirements

---

## Implementation Priority

### Phase 1: Immediate (Before Next Deploy) 🔥
1. ✅ Enable session encryption
2. ✅ Add CSRF tokens to fetch calls
3. ✅ Configure security headers

**Estimated Time:** 1 hour  
**Risk if not done:** HIGH

### Phase 2: This Week 📅
1. ✅ Secure cookie configuration (production)
2. ✅ Improve error handling
3. ✅ Review rate limiting

**Estimated Time:** 2 hours  
**Risk if not done:** MEDIUM

### Phase 3: Next Sprint 📋
1. ✅ Strengthen password validation
2. ✅ Add comprehensive security tests
3. ✅ Security audit of all endpoints

**Estimated Time:** 4 hours  
**Risk if not done:** LOW-MEDIUM

---

## Testing Security Changes

After implementing security changes, test:

### Manual Tests
```bash
# Test CSRF protection
curl -X POST http://localhost/api/endpoint
# Should fail without CSRF token

# Test rate limiting
for i in {1..10}; do
    curl http://localhost/login
done
# Should block after configured limit

# Test security headers
curl -I http://localhost
# Should show security headers
```

### Automated Tests
```php
// tests/Feature/SecurityTest.php

it('requires CSRF token for state-changing requests', function () {
    $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
    
    $response->assertStatus(419); // CSRF token mismatch
});

it('includes security headers', function () {
    $response = $this->get('/');
    
    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
});
```

---

## Security Checklist Before Production

- [ ] `SESSION_ENCRYPT=true`
- [ ] `SESSION_SECURE_COOKIE=true` (HTTPS only)
- [ ] `APP_DEBUG=false`
- [ ] CSRF tokens on all state-changing requests
- [ ] Security headers configured
- [ ] Rate limiting on sensitive endpoints
- [ ] Strong password validation
- [ ] Error messages sanitized
- [ ] HTTPS enforced
- [ ] Database credentials secured
- [ ] API keys in environment variables
- [ ] Backup strategy implemented
- [ ] Security monitoring enabled
- [ ] Dependency vulnerabilities checked (`composer audit`)

---

## Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [Mozilla Web Security Guidelines](https://infosec.mozilla.org/guidelines/web_security)
- [Security Headers](https://securityheaders.com/) - Test your headers

---

**Last Updated:** 2025-10-14  
**Next Review:** Before production deployment
