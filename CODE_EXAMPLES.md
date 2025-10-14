# Code Improvement Examples

This document provides specific, actionable code improvements you can copy-paste into your project.

---

## 1. Enhanced TypeScript Hook with Better Error Handling

**File:** `resources/js/hooks/use-two-factor-auth.ts`

### Improved Version

```typescript
import { qrCode, recoveryCodes, secretKey } from '@/routes/two-factor';
import { useCallback, useMemo, useState } from 'react';

interface TwoFactorSetupData {
    svg: string;
    url: string;
}

interface TwoFactorSecretKey {
    secretKey: string;
}

export const OTP_MAX_LENGTH = 6;

/**
 * Enhanced fetch with CSRF token support and better error handling
 */
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

    // Add CSRF token for state-changing requests
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
        const errorBody = await response.text().catch(() => 'Unknown error');
        throw new Error(
            `HTTP ${response.status}: ${response.statusText}. ${errorBody}`
        );
    }

    return response.json();
};

/**
 * Hook for managing two-factor authentication setup and recovery codes
 *
 * @returns Two-factor authentication utilities and state
 *
 * @example
 * ```tsx
 * const { qrCodeSvg, fetchSetupData, errors } = useTwoFactorAuth();
 *
 * useEffect(() => {
 *   fetchSetupData();
 * }, [fetchSetupData]);
 * ```
 */
export const useTwoFactorAuth = () => {
    const [qrCodeSvg, setQrCodeSvg] = useState<string | null>(null);
    const [manualSetupKey, setManualSetupKey] = useState<string | null>(null);
    const [recoveryCodesList, setRecoveryCodesList] = useState<string[]>([]);
    const [errors, setErrors] = useState<string[]>([]);
    const [isLoading, setIsLoading] = useState<boolean>(false);

    const hasSetupData = useMemo<boolean>(
        () => qrCodeSvg !== null && manualSetupKey !== null,
        [qrCodeSvg, manualSetupKey]
    );

    /**
     * Add an error to the error state
     */
    const addError = useCallback((message: string): void => {
        setErrors((prev) => [...prev, message]);
    }, []);

    /**
     * Clear all errors
     */
    const clearErrors = useCallback((): void => {
        setErrors([]);
    }, []);

    /**
     * Fetch QR code for 2FA setup
     */
    const fetchQrCode = useCallback(async (): Promise<void> => {
        try {
            const { svg } = await fetchJson<TwoFactorSetupData>(qrCode.url());
            setQrCodeSvg(svg);
        } catch (error) {
            // Log detailed error for developers
            if (import.meta.env.DEV) {
                console.error('QR Code fetch error:', error);
            }

            // User-friendly error message
            addError('Unable to load QR code. Please try again.');
            setQrCodeSvg(null);
        }
    }, [addError]);

    /**
     * Fetch manual setup key for 2FA
     */
    const fetchSetupKey = useCallback(async (): Promise<void> => {
        try {
            const { secretKey: key } = await fetchJson<TwoFactorSecretKey>(
                secretKey.url()
            );
            setManualSetupKey(key);
        } catch (error) {
            if (import.meta.env.DEV) {
                console.error('Setup key fetch error:', error);
            }

            addError('Unable to load setup key. Please try again.');
            setManualSetupKey(null);
        }
    }, [addError]);

    /**
     * Clear all setup data
     */
    const clearSetupData = useCallback((): void => {
        setManualSetupKey(null);
        setQrCodeSvg(null);
        clearErrors();
    }, [clearErrors]);

    /**
     * Fetch recovery codes with loading state
     */
    const fetchRecoveryCodes = useCallback(async (): Promise<void> => {
        if (isLoading) {
            return; // Prevent duplicate requests
        }

        try {
            setIsLoading(true);
            clearErrors();
            const codes = await fetchJson<string[]>(recoveryCodes.url());
            setRecoveryCodesList(codes);
        } catch (error) {
            if (import.meta.env.DEV) {
                console.error('Recovery codes fetch error:', error);
            }

            addError('Unable to load recovery codes. Please try again.');
            setRecoveryCodesList([]);
        } finally {
            setIsLoading(false);
        }
    }, [isLoading, clearErrors, addError]);

    /**
     * Fetch both QR code and setup key in parallel
     */
    const fetchSetupData = useCallback(async (): Promise<void> => {
        try {
            setIsLoading(true);
            clearErrors();
            await Promise.all([fetchQrCode(), fetchSetupKey()]);
        } catch (error) {
            // Errors already handled by individual functions
            setQrCodeSvg(null);
            setManualSetupKey(null);
        } finally {
            setIsLoading(false);
        }
    }, [clearErrors, fetchQrCode, fetchSetupKey]);

    return {
        qrCodeSvg,
        manualSetupKey,
        recoveryCodesList,
        hasSetupData,
        errors,
        isLoading,
        clearErrors,
        clearSetupData,
        fetchQrCode,
        fetchSetupKey,
        fetchSetupData,
        fetchRecoveryCodes,
    };
};
```

**Key Improvements:**
1. ✅ Added CSRF token support
2. ✅ Better error handling with logging
3. ✅ Added loading state
4. ✅ Request deduplication
5. ✅ JSDoc documentation
6. ✅ Improved TypeScript types

---

## 2. Optimized Alert Error Component

**File:** `resources/js/components/alert-error.tsx`

### Improved Version

```typescript
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { AlertCircleIcon } from 'lucide-react';
import { memo, useMemo } from 'react';

interface AlertErrorProps {
    errors: string[];
    title?: string;
}

/**
 * Display error messages in an alert component
 * Memoized to prevent unnecessary re-renders
 */
const AlertError = memo(function AlertError({
    errors,
    title = 'Something went wrong.',
}: AlertErrorProps) {
    // Memoize unique errors to prevent recalculation
    const uniqueErrors = useMemo(
        () => Array.from(new Set(errors)),
        [errors]
    );

    // Don't render if no errors
    if (uniqueErrors.length === 0) {
        return null;
    }

    return (
        <Alert variant="destructive" role="alert">
            <AlertCircleIcon className="size-4" aria-hidden="true" />
            <AlertTitle>{title}</AlertTitle>
            <AlertDescription>
                <ul className="list-inside list-disc space-y-1 text-sm">
                    {uniqueErrors.map((error) => (
                        <li key={error}>{error}</li>
                    ))}
                </ul>
            </AlertDescription>
        </Alert>
    );
});

export default AlertError;
```

**Key Improvements:**
1. ✅ React.memo for performance
2. ✅ useMemo for expensive operations
3. ✅ Proper key (error text instead of index)
4. ✅ Early return if no errors
5. ✅ Better TypeScript interface
6. ✅ Accessibility improvements

---

## 3. Enhanced Recovery Codes Component

**File:** `resources/js/components/two-factor-recovery-codes.tsx`

### Key Improvements

```typescript
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { regenerateRecoveryCodes } from '@/routes/two-factor';
import { Form } from '@inertiajs/react';
import { Eye, EyeOff, LockKeyhole, RefreshCw } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import AlertError from './alert-error';

interface TwoFactorRecoveryCodesProps {
    recoveryCodesList: string[];
    fetchRecoveryCodes: () => Promise<void>;
    errors: string[];
    isLoading?: boolean;
}

// Constants
const SCROLL_DELAY_MS = 100;
const SKELETON_COUNT = 8;

export default function TwoFactorRecoveryCodes({
    recoveryCodesList,
    fetchRecoveryCodes,
    errors,
    isLoading = false,
}: TwoFactorRecoveryCodesProps) {
    const [codesAreVisible, setCodesAreVisible] = useState<boolean>(false);
    const [hasInitiallyFetched, setHasInitiallyFetched] = useState<boolean>(false);
    const codesSectionRef = useRef<HTMLDivElement | null>(null);
    
    const canRegenerateCodes = recoveryCodesList.length > 0 && codesAreVisible;

    const scrollToCodesSection = useCallback(() => {
        setTimeout(() => {
            codesSectionRef.current?.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
            });
        }, SCROLL_DELAY_MS);
    }, []);

    const toggleCodesVisibility = useCallback(async () => {
        const willBeVisible = !codesAreVisible;

        // Fetch codes only if they're not already loaded
        if (willBeVisible && !recoveryCodesList.length && !isLoading) {
            await fetchRecoveryCodes();
        }

        setCodesAreVisible(willBeVisible);

        // Scroll to codes section after showing
        if (willBeVisible) {
            scrollToCodesSection();
        }
    }, [codesAreVisible, recoveryCodesList.length, isLoading, fetchRecoveryCodes, scrollToCodesSection]);

    // Initial fetch on mount (only once)
    useEffect(() => {
        if (!hasInitiallyFetched && !recoveryCodesList.length && !isLoading) {
            fetchRecoveryCodes()
                .finally(() => setHasInitiallyFetched(true));
        }
    }, [hasInitiallyFetched, recoveryCodesList.length, isLoading, fetchRecoveryCodes]);

    const RecoveryCodeIconComponent = codesAreVisible ? EyeOff : Eye;

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-3">
                    <LockKeyhole className="size-4" aria-hidden="true" />
                    2FA Recovery Codes
                </CardTitle>
                <CardDescription>
                    Recovery codes let you regain access if you lose your 2FA
                    device. Store them securely in a password manager.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="flex flex-col gap-3 select-none sm:flex-row sm:items-center sm:justify-between">
                    <Button
                        onClick={toggleCodesVisibility}
                        className="w-fit"
                        aria-expanded={codesAreVisible}
                        aria-controls="recovery-codes-section"
                        disabled={isLoading}
                    >
                        <RecoveryCodeIconComponent
                            className="size-4"
                            aria-hidden="true"
                        />
                        {isLoading ? 'Loading...' : codesAreVisible ? 'Hide' : 'View'} Recovery Codes
                    </Button>

                    {canRegenerateCodes && (
                        <Form
                            {...regenerateRecoveryCodes.form()}
                            options={{ preserveScroll: true }}
                            onSuccess={fetchRecoveryCodes}
                        >
                            {({ processing }) => (
                                <Button
                                    variant="secondary"
                                    type="submit"
                                    disabled={processing || isLoading}
                                    aria-describedby="regenerate-warning"
                                >
                                    <RefreshCw className={processing ? 'animate-spin' : ''} />
                                    Regenerate Codes
                                </Button>
                            )}
                        </Form>
                    )}
                </div>
                
                <div
                    id="recovery-codes-section"
                    className={`relative overflow-hidden transition-all duration-300 ${
                        codesAreVisible ? 'h-auto opacity-100' : 'h-0 opacity-0'
                    }`}
                    aria-hidden={!codesAreVisible}
                >
                    <div className="mt-3 space-y-3">
                        {errors?.length > 0 ? (
                            <AlertError errors={errors} />
                        ) : (
                            <>
                                <div
                                    ref={codesSectionRef}
                                    className="grid gap-1 rounded-lg bg-muted p-4 font-mono text-sm"
                                    role="list"
                                    aria-label="Recovery codes"
                                >
                                    {recoveryCodesList.length > 0 ? (
                                        recoveryCodesList.map((code) => (
                                            <div
                                                key={code}
                                                role="listitem"
                                                className="select-text"
                                            >
                                                {code}
                                            </div>
                                        ))
                                    ) : (
                                        <div
                                            className="space-y-2"
                                            aria-label="Loading recovery codes"
                                            aria-busy="true"
                                        >
                                            {Array.from(
                                                { length: SKELETON_COUNT },
                                                (_, index) => (
                                                    <div
                                                        key={`skeleton-${index}`}
                                                        className="h-4 animate-pulse rounded bg-muted-foreground/20"
                                                        aria-hidden="true"
                                                    />
                                                )
                                            )}
                                        </div>
                                    )}
                                </div>

                                <div
                                    className="text-xs text-muted-foreground select-none"
                                    role="note"
                                >
                                    <p id="regenerate-warning">
                                        Each recovery code can be used once to
                                        access your account and will be removed
                                        after use. If you run out,{' '}
                                        <strong>regenerate new codes</strong>{' '}
                                        using the button above.
                                    </p>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
```

**Key Improvements:**
1. ✅ Fixed duplicate fetch issue
2. ✅ Added loading state prop
3. ✅ Extracted magic numbers to constants
4. ✅ Better loading indicators
5. ✅ Proper unique keys (code text instead of index)
6. ✅ Accessibility improvements
7. ✅ Request deduplication

---

## 4. Security Headers Middleware

**File:** `app/Http/Middleware/SetSecurityHeaders.php` (new file)

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Add security headers to all responses
 * 
 * Protects against common web vulnerabilities:
 * - Clickjacking
 * - MIME-sniffing attacks  
 * - XSS attacks
 * - Information leakage
 */
final readonly class SetSecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent clickjacking attacks
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevent MIME-sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Enable XSS protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Control referrer information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict browser features
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=()'
        );

        // Content Security Policy (adjust based on your needs)
        if (! $request->is('api/*')) {
            $response->headers->set(
                'Content-Security-Policy',
                implode('; ', [
                    "default-src 'self'",
                    "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
                    "style-src 'self' 'unsafe-inline'",
                    "img-src 'self' data: https:",
                    "font-src 'self' data:",
                    "connect-src 'self'",
                    "frame-ancestors 'none'",
                ])
            );
        }

        // HSTS for production (enforce HTTPS)
        if (config('app.env') === 'production') {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        return $response;
    }
}
```

**Register in `bootstrap/app.php`:**
```php
use App\Http\Middleware\SetSecurityHeaders;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(SetSecurityHeaders::class);
    })
    // ...
```

---

## 5. Enhanced Form Request with Better Validation

**File:** `app/Http/Requests/ConfirmTwoFactorAuthenticationRequest.php` (new file)

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ConfirmTwoFactorAuthenticationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'size:6',
                'regex:/^[0-9]{6}$/',
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Please enter your authentication code.',
            'code.size' => 'The authentication code must be exactly 6 digits.',
            'code.regex' => 'The authentication code must contain only numbers.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => 'authentication code',
        ];
    }
}
```

**Usage in Controller:**
```php
use App\Http\Requests\ConfirmTwoFactorAuthenticationRequest;

public function confirm(ConfirmTwoFactorAuthenticationRequest $request): RedirectResponse
{
    // Request is already validated
    $code = $request->validated()['code'];
    
    // Process 2FA confirmation
    // ...
}
```

---

## Summary of Changes

| File | Priority | Effort | Impact |
|------|----------|--------|--------|
| `use-two-factor-auth.ts` | HIGH | 30 min | Security + UX |
| `alert-error.tsx` | MEDIUM | 10 min | Performance |
| `two-factor-recovery-codes.tsx` | MEDIUM | 20 min | UX + Quality |
| `SetSecurityHeaders.php` | HIGH | 15 min | Security |
| `ConfirmTwoFactorAuthenticationRequest.php` | MEDIUM | 10 min | Validation |

**Total Estimated Time:** ~1.5 hours  
**Total Impact:** Significant improvements to security, performance, and code quality

---

**Last Updated:** 2025-10-14
