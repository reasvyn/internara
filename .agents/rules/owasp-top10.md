# Rules: OWASP Top 10 (2021)

> Source: https://owasp.org/Top10/
> Version: OWASP Top 10 — 2021
> Applicability: All web applications

## Overview

The OWASP Top 10 is a standard awareness document for developers and web application security
representatives. It represents a broad consensus about the most critical security risks to
applications.

## A01: Broken Access Control ( Moved from #5)

**Frequency:** 55.97% of applications tested
**CVSS v3 Range:** 6.5 — 10.0

### What to Check

1. **Violation of Principle of Least Privilege**
   - Users can access resources outside their assigned role
   - Privesc from regular user to admin via direct URL manipulation
   - Check: Can user A access user B's profile by changing the ID in the URL?

2. **Insecure Direct Object Reference (IDOR)**
   - Sequential/guessable IDs exposed in URLs or API
   - No ownership verification before returning data
   - Check: Access `/api/users/{other_user_id}` without auth checks

3. **CORS Misconfiguration**
   - `Access-Control-Allow-Origin: *` on sensitive endpoints
   - Credentials allowed from any origin
   - Check: Response headers on authenticated endpoints

4. **Directory Traversal**
   - User-controlled file paths without sanitization
   - Check: `Storage::get($userInput)`, `file_get_contents($userInput)`

5. **Missing Function Level Access Control**
   - Admin endpoints accessible without admin middleware
   - Check: All routes have appropriate middleware

### Laravel-Specific Checks

```php
// BAD: No authorization check
Route::get('/users/{user}', [UserController::class, 'show']);

// GOOD: Policy-based authorization
Route::get('/users/{user}', [UserController::class, 'show'])
    ->middleware('can:view,user');
```

```php
// BAD: IDOR — no ownership check
$user = User::findOrFail($request->id);

// GOOD: Scoped to authenticated user
$user = User::where('id', $request->id)
    ->where('team_id', auth()->user()->team_id)
    ->firstOrFail();
```

### Severity Classification

| Condition | Severity |
|-----------|----------|
| Any user can access any other user's data | Critical |
| Role escalation possible | Critical |
| IDOR on sensitive data | High |
| IDOR on non-sensitive data | Medium |
| CORS misconfiguration | Medium |
| Missing function-level access control | High |

---

## A02: Cryptographic Failures (Formerly "Sensitive Data Exposure")

**Frequency:** 4.49%
**CVSS Range:** 4.3 — 9.1

### What to Check

1. **Plaintext Storage of Passwords**
   ```php
   // BAD
   $user->password = $request->password;
   $user->save();

   // GOOD
   $user->password = Hash::make($request->password);
   $user->save();
   ```

2. **Weak Hashing Algorithms**
   ```php
   // BAD
   md5($password);
   sha1($password);

   // GOOD
   Hash::make($password); // bcrypt/argon2
   ```

3. **Sensitive Data in Source Code**
   - API keys, passwords, tokens hardcoded in PHP files
   - Database credentials in version control
   - Check: `grep -rn "password\|secret\|key\|token" app/ config/`

4. **Sensitive Data in URLs**
   - Tokens in query parameters (logged by proxies/browsers)
   - Passwords in URLs
   - Check: Route definitions with sensitive params

5. **Insufficient Transport Layer Protection**
   - HTTP allowed on authentication endpoints
   - Mixed content (HTTPS page loading HTTP resources)

6. **Sensitive Data Exposure in Error Messages**
   - Stack traces shown to users
   - Database errors leaked in responses

### Laravel-Specific Checks

```php
// Check .env is not web-accessible
// Check config files don't contain real secrets
// Check no passwords/tokens in migration files
// Check sessions use encrypted cookies
```

---

## A03: Injection

**Frequency:** 19.09%
**CVSS Range:** 5.3 — 10.0

### What to Check

1. **SQL Injection**
   ```php
   // BAD — raw query with interpolation
   DB::select("SELECT * FROM users WHERE id = '$id'");

   // BAD — DB::raw without binding
   DB::select(DB::raw("SELECT * FROM users WHERE id = $id"));

   // GOOD — parameterized
   DB::select("SELECT * FROM users WHERE id = ?", [$id]);

   // GOOD — Eloquent
   User::where('id', $id)->first();

   // GOOD — Query Builder with binding
   DB::table('users')->where('id', $id)->first();
   ```

2. **Cross-Site Scripting (XSS)**
   ```blade
   {/* BAD — raw output */}
   {!! $userInput !!}

   {/* GOOD — escaped output */}
   {{ $userInput }}

   {/* CONDITIONAL — markdown (needs sanitization) */}
   {!! Str::markdown($message) !!}  {/* MUST sanitize HTML */}
   ```

3. **Command Injection**
   ```php
   // BAD
   exec("convert $input_file $output_file");
   shell_exec("ping " . $userInput);
   system("cat " . $request->file);

   // GOOD — avoid shell commands entirely
   // If unavoidable: escapeshellarg() on all user inputs
   ```

4. **NoSQL Injection** (if applicable)
   - MongoDB operators in user input (`$gt`, `$ne`, `$regex`)
   - Check: Any NoSQL database usage

5. **LDAP Injection** (if applicable)
   - User input in LDAP queries without sanitization

6. **Template Injection**
   - User-controlled Blade template names
   - `view($userInput)` without validation

### CWE Mapping

| Injection Type | CWE ID |
|---------------|--------|
| SQL Injection | CWE-89 |
| XSS | CWE-79 |
| OS Command Injection | CWE-78 |
| LDAP Injection | CWE-90 |
| NoSQL Injection | CWE-943 |

---

## A04: Insecure Design

**Frequency:** 3.00%
**CVSS Range:** 6.5 — 9.1

### What to Check

1. **Business Logic Bypasses**
   - Price manipulation in e-commerce flows
   - Privilege escalation through sequential steps
   - Race conditions allowing double-spending/ double-submission
   - Check: Multi-step workflows for bypass opportunities

2. **Missing Rate Limiting**
   ```php
   // Check: Are these endpoints rate-limited?
   // Login, registration, password reset, API endpoints
   Route::post('/login', ...)->middleware('throttle:5,1');
   ```

3. **Resource Consumption Without Limits**
   - No file upload size limits
   - No request body size limits
   - No query result limits (unbounded SELECT)
   - Check: File upload handlers, API endpoints

4. **Missing Anti-Automation Controls**
   - CAPTCHA on forms
   - Bot detection on sensitive flows
   - Check: Registration, login, contact forms

5. **Trust Boundary Violations**
   - User input used without validation in business decisions
   - Client-side validation only (no server-side backup)

### Severity Classification

| Condition | Severity |
|-----------|----------|
| Race condition on financial operation | Critical |
| No rate limiting on authentication | High |
| Business logic bypass allowing data corruption | High |
| Missing resource limits | Medium |

---

## A05: Security Misconfiguration

**Frequency:** 4.51%
**CVSS Range:** 5.3 — 8.0

### What to Check

1. **Debug Mode in Production**
   ```php
   // Check .env
   APP_DEBUG=false  // MUST be false in production
   ```

2. **Error Pages Leaking Information**
   - Laravel default error page shows stack trace when APP_DEBUG=true
   - Check: Trigger a 404, 500, 403 — does it show details?

3. **Security Headers Missing**
   ```
   Content-Security-Policy: default-src 'self'
   X-Content-Type-Options: nosniff
   X-Frame-Options: DENY
   Strict-Transport-Security: max-age=31536000; includeSubDomains
   Referrer-Policy: strict-origin-when-cross-origin
   Permissions-Policy: camera=(), microphone=(), geolocation=()
   ```

4. **Unnecessary Features Enabled**
   - Debug toolbar in production
   - Tinker accessible in production
   - Default routes (`/`, `/dashboard`) accessible
   - Check: `Route::get('/telescope', ...)` or similar in production

5. **Default Credentials**
   - Default admin/admin or admin/password
   - Default database credentials
   - Check: Seeder files, setup scripts

6. **Directory Listing**
   - Web server directory listing enabled
   - Check: Access `/storage/` or `/uploads/` directly

7. **Server Version Disclosure**
   - `Server: Apache/2.4.x` or `X-Powered-By: PHP/8.x`
   - Check: Response headers

### Laravel-Specific Checks

```php
// config/app.php — debug must be false
// .env — APP_DEBUG=false
// Check: Does config:cache hide debug mode?
// Check: Are maintenance mode pages styled and informative?
```

---

## A06: Vulnerable and Outdated Components

**Frequency:** 8.77%
**CVSS Range:** Varies

### What to Check

1. **Known CVEs**
   ```bash
   composer audit 2>&1
   npm audit 2>&1
   ```

2. **Unmaintained Dependencies**
   - Last commit > 2 years ago
   - No active maintainers
   - Open security issues without response

3. **Version Constraints Too Broad**
   - `*` version constraints
   - `>=8.0` without upper bound
   - Check: composer.json, package.json

4. **Unused Dependencies**
   - Installed but never imported/used
   - Increases attack surface
   - Check: `grep -rn "PackageName\\\\" app/` for each dependency

### Severity Classification

| Condition | Severity |
|-----------|----------|
| Critical CVE with known exploit | Critical |
| High CVE without workaround | High |
| Abandoned dependency with no alternative | Medium |
| Low CVE with available fix | Medium |

---

## A07: Identification and Authentication Failures

**Frequency:** 2.55%
**CVSS Range:** 5.3 — 8.1

### What to Check

1. **Brute Force Protection**
   - Login endpoint rate-limited
   - Account lockout after N failed attempts
   - Check: Login controller, auth middleware

2. **Credential Stuffing Protection**
   - CAPTCHA after N failed attempts
   - Anomaly detection (IP-based, behavior-based)
   - Check: Login flow, rate limiting

3. **Weak Password Policy**
   - Minimum length < 8 characters
   - No complexity requirements
   - Common passwords not blocked
   - Check: Registration validation, password rules

4. **Session Fixation**
   - Session ID not regenerated after login
   - Check: Login handler regenerates session

5. **Missing Multi-Factor Authentication**
   - No 2FA option for sensitive operations
   - Check: Account settings, admin panel

### Laravel-Specific Checks

```php
// Check: Session regeneration after login
Auth::login($user);
$request->session()->regenerate();  // MUST be present

// Check: Password validation rules
Password::min(8)->mixedCase()->numbers()->symbols()

// Check: Throttle on login
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->email . $request->ip());
});
```

---

## A08: Software and Data Integrity Failures

**Frequency:** 2.05%
**CVSS Range:** 6.8 — 10.0

### What to Check

1. **Insecure Deserialization**
   ```php
   // BAD
   unserialize($userData);
   unserialize($request->cookie('data'));

   // GOOD — avoid deserializing untrusted data entirely
   // If unavoidable: use signed/encrypted serialized data
   ```

2. **Unsafe Auto-Updates**
   - Updates downloaded over HTTP
   - No integrity verification (checksum/signature)
   - Check: Update mechanisms

3. **CI/CD Pipeline Integrity**
   - Secrets in CI/CD environment
   - Untrusted code merged without review
   - Check: CI configuration files

4. **Unsigned Dependencies**
   - Composer packages without signature verification
   - Check: `composer.lock` integrity

### Laravel-Specific Checks

```php
// Check: No use of unserialize() in app code
// Check: signed URLs/cookies used where needed
// Check: APP_KEY is strong and unique
```

---

## A09: Security Logging and Monitoring Failures

**Frequency:** 6.51%
**CVSS Range:** Varies

### What to Check

1. **Insufficient Logging**
   - Login failures not logged
   - Access control failures not logged
   - Input validation failures not logged
   - Check: Log files for security events

2. **PII in Logs**
   ```php
   // BAD
   Log::info("User login: email={$user->email}");
   Log::info("API request", ['token' => $request->bearerToken()]);
   Log::info("Password reset", ['password' => $request->password]);

   // GOOD
   Log::info("User login", ['user_id' => $user->id]);
   Log::info("API request", ['user_id' => auth()->id()]);
   ```

3. **Log Injection**
   - User input directly in log messages without sanitization
   - Check: `Log::info($request->input('name'))` — can contain newlines

4. **Missing Alerting**
   - No alerts for repeated failed logins
   - No alerts for privilege escalation attempts
   - Check: Monitoring/alerting configuration

5. **Audit Trail Gaps**
   - Administrative actions not logged
   - Data mutations not tracked
   - Check: Activity log, audit trail

---

## A10: Server-Side Request Forgery (SSRF)

**Frequency:** 2.72%
**CVSS Range:** 6.5 — 9.0

### What to Check

1. **User-Controlled URLs**
   ```php
   // BAD
   $response = Http::get($request->url);

   // BETTER — validate URL
   $response = Http::get($request->validated('url'));

   // BEST — allowlist
   $allowedDomains = ['api.trusted.com', 'cdn.trusted.com'];
   $url = parse_url($request->validated('url'));
   if (!in_array($url['host'] ?? '', $allowedDomains)) {
       abort(400, 'Domain not allowed');
   }
   ```

2. **Internal Network Access**
   - Can user input reach `127.0.0.1`, `169.254.169.254` (cloud metadata), or internal hosts?
   - Check: Any HTTP client usage with user-controlled URLs

3. **DNS Rebinding**
   - DNS resolution happens before connection
   - Check: URL validation uses hostname, not resolved IP

### Laravel-Specific Checks

```php
// Check: Http facade usage with user input
// Check: file_get_contents(), file_put_contents() with user URLs
// Check: curl_* functions with user-controlled URLs
// Check: get_headers(), getimagesize() with user URLs
```

---

## CVSS v3.1 Severity Classification

| Rating | Score Range | Description |
|--------|-------------|-------------|
| None | 0.0 | No impact |
| Low | 0.1 — 3.9 | Limited impact |
| Medium | 4.0 — 6.9 | Moderate impact |
| High | 7.0 — 8.9 | Serious impact |
| Critical | 9.0 — 10.0 | Complete compromise |

### Quick Severity Calculator

For web application findings, use this simplified CVSS approximation:

```
Base Score = Impact × Exploitability

Impact:
  - No data access: 2.6 (Low)
  - Limited data access: 5.0 (Medium)
  - Full data access: 7.5 (High)
  - Full system control: 10.0 (Critical)

Exploitability:
  - Requires authentication: 5.0 (Medium)
  - Requires special conditions: 3.9 (Low)
  - No authentication, trivial: 8.0 (High)
  - No authentication, automated: 10.0 (Critical)
```
