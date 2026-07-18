# Rules: Session Management

> OWASP: A07 (Identification and Authentication Failures)
> CWE: CWE-614 (Sensitive Cookie in HTTPS Session Without 'Secure' Attribute)
> CWE: CWE-384 (Session Fixation)
> Applicability: All web applications

## Core Principles

1. **Session cookies must be secure** — HttpOnly, Secure, SameSite
2. **Session must be regenerated after authentication state change**
3. **Sessions must expire** — both idle timeout and absolute timeout
4. **Old sessions must be invalidated on logout**
5. **Session fixation must be prevented**

## What to Check

### 1. Cookie Security Flags

```php
// config/session.php — check these settings

// HttpOnly: prevents JavaScript access (XSS mitigation)
'http_only' => true,  // MUST be true

// Secure: only send over HTTPS
'secure' => true,  // MUST be true in production

// SameSite: prevents CSRF
'same_site' => 'lax',  // Recommended: 'lax' or 'strict'

// Lifetime: session expiry
'lifetime' => 120,  // 2 hours idle timeout

// Domain: restrict to application domain
'domain' => null,  // null = current domain only
```

### 2. Session Regeneration

```php
// BAD — no regeneration after login
Auth::login($user);
return redirect('/dashboard');

// GOOD — regeneration
Auth::login($user);
$request->session()->regenerate();
return redirect()->intended('/dashboard');

// BAD — no regeneration after privilege change
$user->assignRole('admin');

// GOOD — regeneration on privilege change
$user->assignRole('admin');
$request->session()->regenerate(true); // Invalidate old session
```

### 3. Session Invalidation on Logout

```php
// BAD — partial logout
Auth::logout();
return redirect('/login');

// GOOD — complete logout
Auth::logout();
$request->session()->invalidate();     // Regenerate session ID
$request->session()->regenerateToken(); // Regenerate CSRF token
return redirect('/login');
```

### 4. Session Timeout

```php
// Check:
// - Idle timeout configured (15-60 minutes for sensitive apps)
// - Absolute timeout configured (8-24 hours)
// - Timeout redirects to login with appropriate message
// - AJAX requests handle timeout gracefully
```

### 5. Session Storage

```php
// Check:
// - Session driver appropriate for deployment
//   - database: good for multi-server
//   - redis: best for performance
//   - file: acceptable for single-server
// - Session table has proper indexes
// - Session garbage collection configured
```

### 6. Concurrent Session Control

```php
// Check:
// - Maximum concurrent sessions per user (if applicable)
// - New login invalidates old session (if single-session policy)
// - Admin can force logout of all sessions
```

## Detection

```bash
# Check session config
grep -n "http_only\|secure\|same_site\|lifetime" config/session.php

# Check for session regeneration after login
grep -rn "regenerate" app/ --include="*.php"

# Check for session invalidation on logout
grep -rn "invalidate\|regenerateToken" app/ --include="*.php"
```

## Severity Classification

| Finding | Severity |
|---------|----------|
| HttpOnly flag missing | High |
| Secure flag missing (production) | High |
| SameSite not set | Medium |
| No session regeneration after login | High |
| No session invalidation on logout | Medium |
| No idle timeout | Medium |
| Sessions stored in files (multi-server) | Low |
| No concurrent session control | Low |
