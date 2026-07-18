# Rules: API Security

> OWASP: A05 (Security Misconfiguration), A07 (Identification and Authentication Failures)
> CWE: CWE-352 (CSRF), CWE-306 (Missing Authentication)
> Applicability: All applications with HTTP endpoints

## Overview

Even Livewire-based applications have HTTP endpoints (routes, controllers) that need
security hardening. This covers rate limiting, CSRF protection, CORS, and API-specific
concerns.

## What to Check

### 1. Rate Limiting

```php
// Check: Sensitive endpoints have rate limiting
// routes/web.php or routes/api.php

// GOOD — throttling on authentication routes
Route::post('/login', [LoginController::class, 'login'])
    ->middleware('throttle:5,1'); // 5 attempts per minute

Route::post('/forgot-password', [ResetPasswordController::class, 'sendResetLinkEmail'])
    ->middleware('throttle:3,1'); // 3 per minute

Route::post('/register', [RegisterController::class, 'register'])
    ->middleware('throttle:3,1'); // 3 per minute

// GOOD — API rate limiting
Route::middleware('throttle:api')->group(function () {
    Route::apiResource('posts', PostController::class);
});
```

```php
// Check: Rate limiting configured in AppServiceProvider
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

### 2. CSRF Protection

```blade
{* Check: All forms include CSRF token *}
<form method="POST" action="/submit">
    @csrf
    ...
</form>

{* Livewire handles CSRF automatically *}
```

```php
// Check: State-changing routes have CSRF protection
// Laravel excludes these by default:
// - routes defined in routes/api.php (use Sanctum/Passport)
// - URLs in Except array in VerifyCsrfToken middleware

// BAD — CSRF excluded for web routes
protected $except = [
    '/webhook/*',  // Only if webhook is from trusted source
    '/api/*',      // Only if using token-based auth
];
```

### 3. CORS Configuration

```php
// config/cors.php — check settings

// BAD — wide open
'allowed_origins' => ['*'],
'supports_credentials' => true, // Can't use * with credentials

// GOOD — restricted
'allowed_origins' => [
    env('FRONTEND_URL', 'https://yourdomain.com'),
],
'supports_credentials' => true,
```

### 4. Security Headers

```php
// Check: Security headers middleware exists
// Check: Headers are present in responses

// Expected headers:
// Content-Security-Policy: default-src 'self'; script-src 'self'
// X-Content-Type-Options: nosniff
// X-Frame-Options: DENY (or SAMEORIGIN if framing is needed)
// Strict-Transport-Security: max-age=31536000; includeSubDomains
// Referrer-Policy: strict-origin-when-cross-origin
// Permissions-Policy: camera=(), microphone=(), geolocation=()
// X-XSS-Protection: 0 (modern CSP is preferred over this legacy header)
```

### 5. HTTP Method Restrictions

```php
// Check: Routes use appropriate HTTP methods
// POST for creation, PUT/PATCH for updates, DELETE for deletion

// BAD — GET for mutations
Route::get('/users/{user}/delete', [UserController::class, 'delete']);

// GOOD — DELETE method
Route::delete('/users/{user}', [UserController::class, 'destroy']);
```

### 6. Error Response Consistency

```php
// Check: Error responses don't leak information
// BAD — verbose error
return response()->json([
    'error' => 'User not found',
    'query' => $query,  // Leaked
    'sql' => $sql,      // Leaked
    'trace' => $trace,  // Leaked
], 404);

// GOOD — minimal error
return response()->json([
    'message' => __('Not found.'),
], 404);
```

### 7. File Upload Security

```php
// Check: File uploads are restricted
// - Type validation (MIME type + extension)
// - Size limits
// - Content inspection (not just extension)
// - Storage outside web root
// - Random filenames (not user-controlled)

// GOOD — secure file upload
$request->validate([
    'file' => 'required|file|max:2048|mimes:pdf,jpg,png',
]);

$file = $request->file('file');
$path = $file->store('uploads', 'private'); // Outside web root
$newName = Str::random(40) . '.' . $file->getClientOriginalExtension();
$file->move(storage_path('app/private/uploads'), $newName);
```

### 8. Open Redirect Prevention

```php
// BAD — user-controlled redirect
return redirect($request->input('return_to'));

// GOOD — validated redirect
$allowed = ['/dashboard', '/profile', '/settings'];
$returnTo = $request->input('return_to', '/dashboard');
if (!in_array($returnTo, $allowed)) {
    $returnTo = '/dashboard';
}
return redirect($returnTo);
```

## Detection

```bash
# Find routes without throttle middleware
grep -rn "Route::" routes/ | grep -v "throttle"

# Find forms without CSRF
grep -rn "<form" resources/views/ | grep -v "@csrf\|wire:submit"

# Find GET routes that mutate
grep -rn "Route::get" routes/ | grep -i "delete\|remove\|update\|create"

# Find open redirects
grep -rn "redirect(\$request" app/ --include="*.php"
```

## Severity Classification

| Finding | Severity |
|---------|----------|
| No rate limiting on login | High |
| CSRF protection disabled for web routes | High |
| CORS allows all origins with credentials | High |
| Missing security headers | Medium |
| GET for mutations | Medium |
| Verbose error responses | Medium |
| File upload without type check | High |
| Open redirect | Medium |
| No file size limit | Medium |
