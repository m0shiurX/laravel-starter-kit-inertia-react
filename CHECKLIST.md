# Quick Reference: Code Quality Checklist

Use this checklist when reviewing or implementing changes.

## 🔒 Security Checklist

- [ ] Session encryption enabled (`SESSION_ENCRYPT=true`)
- [ ] CSRF tokens included in non-GET requests
- [ ] Security headers configured (X-Frame-Options, CSP, etc.)
- [ ] Rate limiting on sensitive endpoints (login, 2FA, password reset)
- [ ] Error messages don't expose system information
- [ ] Input validation on both client and server
- [ ] SQL injection prevention (use parameterized queries)
- [ ] XSS prevention (sanitize user input)
- [ ] Secure cookies (HttpOnly, Secure, SameSite)
- [ ] HTTPS enforced in production

## 🧹 Clean Code Checklist

- [ ] Functions have single responsibility
- [ ] No magic numbers (use named constants)
- [ ] Descriptive variable and function names
- [ ] No duplicate code (DRY principle)
- [ ] Proper error handling with specific types
- [ ] JSDoc comments for complex functions
- [ ] Consistent code formatting (Pint for PHP, Prettier for JS/TS)
- [ ] Type safety (no `any` types in TypeScript)
- [ ] Proper TypeScript interfaces and types
- [ ] Avoid nested ternaries (max 2 levels)

## ⚡ Performance Checklist

- [ ] React.memo for expensive components
- [ ] useMemo for expensive computations
- [ ] useCallback for callback functions in dependencies
- [ ] Avoid inline function definitions in JSX
- [ ] Lazy loading for large components
- [ ] Image optimization
- [ ] Database query optimization (N+1 prevention)
- [ ] Proper indexing on frequently queried columns
- [ ] Caching strategy for expensive operations
- [ ] Request deduplication

## ♿ Accessibility Checklist

- [ ] Semantic HTML elements
- [ ] Proper ARIA labels
- [ ] Keyboard navigation support
- [ ] Focus management in modals
- [ ] Color contrast meets WCAG standards
- [ ] Screen reader announcements (aria-live)
- [ ] Alt text for images
- [ ] Form labels associated with inputs
- [ ] Error messages are descriptive
- [ ] Skip navigation links

## 🧪 Testing Checklist

- [ ] Unit tests for business logic
- [ ] Integration tests for critical flows
- [ ] Edge case testing
- [ ] Error handling tests
- [ ] Security tests (CSRF, XSS, SQL injection)
- [ ] Performance tests for critical paths
- [ ] Accessibility tests
- [ ] Cross-browser testing
- [ ] Mobile responsive testing
- [ ] Test coverage > 80%

## 📱 Frontend React Checklist

- [ ] No prop drilling (use context if needed)
- [ ] Avoid unnecessary re-renders
- [ ] Proper key props in lists
- [ ] Clean up useEffect (return cleanup function)
- [ ] Handle loading states
- [ ] Handle error states
- [ ] Handle empty states
- [ ] Avoid useState for derived values
- [ ] Use proper TypeScript types for props
- [ ] Component size < 300 lines (split if larger)

## 🐘 Backend Laravel Checklist

- [ ] Use Form Request for validation
- [ ] Use Eloquent instead of raw SQL
- [ ] N+1 query prevention (eager loading)
- [ ] Use transactions for multi-step operations
- [ ] Queue long-running operations
- [ ] Use Gates/Policies for authorization
- [ ] Use named routes
- [ ] Config files for environment variables (not `env()` direct)
- [ ] Use events for side effects
- [ ] Proper exception handling

## 📝 Documentation Checklist

- [ ] README.md up to date
- [ ] Setup instructions clear
- [ ] API endpoints documented
- [ ] Complex business logic commented
- [ ] Environment variables documented
- [ ] Architecture diagrams (if complex)
- [ ] Changelog maintained
- [ ] Deployment guide
- [ ] Troubleshooting section
- [ ] Contributing guidelines

## 🚀 Deployment Checklist

- [ ] Environment variables set
- [ ] Database migrations run
- [ ] Assets compiled and optimized
- [ ] Cache cleared
- [ ] Queue workers running
- [ ] Logs configured
- [ ] Monitoring configured
- [ ] Backup strategy in place
- [ ] SSL certificate valid
- [ ] DNS configured correctly

## 🔍 Code Review Checklist

- [ ] Code follows project conventions
- [ ] Tests pass
- [ ] No console.log or dd() left in code
- [ ] No commented-out code
- [ ] Dependencies updated if needed
- [ ] Breaking changes documented
- [ ] Performance impact considered
- [ ] Security implications reviewed
- [ ] Accessibility maintained
- [ ] Documentation updated

---

## Common Pitfalls to Avoid

### TypeScript/React
❌ **Don't:**
```typescript
const [data, setData] = useState<any>(null);  // No 'any' types
```
✅ **Do:**
```typescript
const [data, setData] = useState<UserData | null>(null);
```

### React Hooks
❌ **Don't:**
```typescript
useEffect(() => {
    fetchData();
}, []);  // Missing cleanup
```
✅ **Do:**
```typescript
useEffect(() => {
    const controller = new AbortController();
    fetchData(controller.signal);
    return () => controller.abort();
}, []);
```

### Laravel Validation
❌ **Don't:**
```php
$validated = $request->validate([...]);  // Inline validation
```
✅ **Do:**
```php
class UpdateUserRequest extends FormRequest {
    public function rules(): array { return [...]; }
}
```

### Database Queries
❌ **Don't:**
```php
foreach ($users as $user) {
    $user->posts;  // N+1 query
}
```
✅ **Do:**
```php
$users = User::with('posts')->get();
```

---

## Quick Wins

These are easy improvements with high impact:

1. **Enable session encryption** - 1 line change in .env
2. **Add React.memo** - Wrap components that re-render unnecessarily
3. **Add loading states** - Better UX with minimal code
4. **Fix linting issues** - Run `npm run lint` and `vendor/bin/pint`
5. **Add JSDoc comments** - Improves code understanding
6. **Extract magic numbers** - Use named constants
7. **Add ARIA labels** - Better accessibility
8. **Enable TypeScript strict mode** - Catch type errors early
9. **Add error boundaries** - Graceful error handling in React
10. **Use HTTP status codes correctly** - RESTful API design

---

**Last Updated:** 2025-10-14
