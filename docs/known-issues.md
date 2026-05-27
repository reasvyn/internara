# Known Issues and Gotchas

## Infrastructure — Critical

### K6. Cache Config Default `database` Instead of `file` 🔴

**File:** `config/cache.php:20`

**Problem:** `'default' => env('CACHE_STORE', 'database')` — when `CACHE_STORE` is not set in `.env`, the fallback is `database`. The documented default for Tier 1 is `file` (zero-config, no external service). The `.env.example` overrides this to `file`, but if someone removes the variable or starts from scratch without `.env.example`, the `database` driver activates and requires a migration.

**Impact:** A fresh installation without `.env.example` silently uses the `database` cache driver, adding unnecessary database load and requiring the `cache` table migration.

**Fix:** Change the fallback to `file`:
```php
'default' => env('CACHE_STORE', 'file'),
```

---

### K7. Queue Config Default `database` Instead of `sync` 🔴

**File:** `config/queue.php:18`

**Problem:** `'default' => env('QUEUE_CONNECTION', 'database')` — when `QUEUE_CONNECTION` is not set, the fallback is `database` which requires a queue worker. The documented default for Tier 1 is `sync` (no worker needed). The `.env.example` overrides to `sync`, but a missing variable silently activates the `database` driver, creating the `jobs` table and dispatching jobs that never process (no worker running).

**Impact:** Notifications, media conversions, and other queued jobs pile up in the `jobs` table indefinitely. No worker is running to process them.

**Fix:** Change the fallback to `sync`:
```php
'default' => env('QUEUE_CONNECTION', 'sync'),
```

---

### K8. MAIL_MAILER Default `smtp` Instead of `log` in `.env.example` 🔴

**File:** `.env.example:72`

**Problem:** The `.env.example` has `MAIL_MAILER=smtp` as the active default, with `# MAIL_MAILER=log` commented out. A school that copies `.env.example` directly will have `smtp` enabled without any SMTP credentials configured, causing connection errors on every notification attempt.

**Impact:** Email notifications fail with connection refused/timeout errors on fresh installations. The error is logged but the admin may not notice it until someone reports missing emails.

**Fix:** Swap the defaults — make `log` active and `smtp` the commented alternative:
```env
MAIL_MAILER=log
# MAIL_MAILER=smtp
# MAIL_HOST=smtp.example.com
# MAIL_PORT=587
```

---

### K1. Trusted Proxies Not Configured 🔴

**File:** `bootstrap/app.php`

**Problem:** No `trustProxies()` call exists anywhere. No `TrustProxies` middleware, no `config/trustedproxy.php`, no `TRUSTED_PROXIES` env variable.

Every `Request::ip()` call returns the proxy's IP, not the client's real IP. This directly impacts:
- `AuthThrottleMiddleware` — IP-based rate limiting blocks the proxy IP instead of the attacker
- `ProtectSetupRouteMiddleware` — setup token rate limiting is equally broken
- `LogContext` — all IPs logged are wrong
- All audit logs and `login_history` table records use proxy IPs

**Impact:** In any deployment behind a reverse proxy (Nginx, Cloudflare, AWS ALB), rate limiting is ineffective and security audit trails are inaccurate. Attackers behind shared proxies bypass rate limits.

**Fix:** Add `trustProxies(at: [...])` in `bootstrap/app.php` with the known proxy IPs/CIDR ranges. For Cloudflare, use the `TrustProxies` middleware or a package that handles CF-Connecting-IP headers.

---

### K2. Exception Hierarchy Not Consumed 🔴

**Files:** `app/Domain/Core/Exceptions/*.php`, `bootstrap/app.php`

**Problem:** The application defines a complete exception hierarchy (`AppException` → `ActionException`, `InfrastructureException`, `PresentationException`) with `isUserFacing()` and `shouldReport()` methods, but **no renderer consumes these methods**.

The `withExceptions()` callback in `bootstrap/app.php` only specifies `dontFlash`. There is no `render()` callback that reads `$exception->isUserFacing()` to return appropriate HTTP responses, and no `report()` callback that reads `$exception->shouldReport()`.

**Impact:** In production (`APP_DEBUG=false`), all `AppException` subclasses render as generic HTTP 500 errors:
- `NotFoundException` (should be 404) → 500
- `UnauthorizedException` (should be 403) → 500
- `ValidationFailedException` (should be 422) → 500
- `RateLimitException` (should be 429) → 500

In development (`APP_DEBUG=true`), stack traces may leak sensitive configuration.

**Fix:** Implement a custom exception renderer:

```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (AppException $e, Request $request) {
        $status = match (true) {
            $e instanceof NotFoundException => 404,
            $e instanceof UnauthorizedException => 403,
            $e instanceof ValidationFailedException => 422,
            $e instanceof RateLimitException => 429,
            default => 500,
        };
        return response()->view("errors.{$status}", [
            'message' => $e->isUserFacing() ? $e->getMessage() : 'An error occurred.',
        ], $status);
    });
})
```

---

### K3. `after_commit => false` on All Queue Connections 🔴

**File:** `config/queue.php` (lines 46, 55, 66, 75)

**Problem:** All queue connections have `'after_commit' => false`. Jobs are dispatched **before** the database transaction commits. This means:

1. If a transaction rolls back, the job is already queued but references data that doesn't exist
2. The job worker attempts to load models that were never persisted → `ModelNotFoundException`
3. Phantom jobs accumulate in the queue

**Impact:** Notifications may be sent for failed/cancelled operations. Jobs fail with "no such record" errors. Attachments referenced in jobs may not exist.

**Fix:** Set `'after_commit' => true` on all queue connections. This ensures jobs only dispatch after the transaction successfully commits.

```php
'database' => [
    'driver' => 'database',
    'after_commit' => true,
    // ...
],
```

---

### K4. `User::getActiveRegistration()` N+1 Query 🔴

**File:** `app/Domain/User/Models/User.php:122-127`

```php
public function getActiveRegistration(): ?Registration
{
    return $this->registrations()
        ->get()  // Loads ALL registrations into memory
        ->first(fn (Registration $reg) => $reg->hasStatus('active'));
        // hasStatus() queries statuses table per item
}
```

**Problem:** The method loads every registration for the user into a Collection, then calls `hasStatus('active')` on each one. The `hasStatus()` method (from Spatie ModelStatus) queries the `statuses` polymorphic table. For a user with 100 registrations, this generates 101 queries (1 for all registrations + 100 individual status checks).

**Impact:** This method is called on every student dashboard load. With many registrations, page load time degrades linearly.

**Fix:** Replace with a single query using `whereHas`:

```php
public function getActiveRegistration(): ?Registration
{
    return $this->registrations()
        ->whereHas('statuses', fn ($q) => $q->where('name', 'active'))
        ->first();
}
```

---

### K5. SQLite for Production (No Concurrent Writes) 🔴

**File:** `config/database.php`

**Problem:** The default database driver is SQLite. SQLite uses file-level locking — only one write transaction can proceed at a time. Combined with the `database` queue driver, queue workers and web requests contend for the same write lock.

**Impact:** Under concurrent load:
- "database is locked" errors on simultaneous attendance clock-in, logbook submissions
- Queue processing blocks web requests (or vice versa)
- No `FOR UPDATE` skip-locked support (pessimistic locking is unreliable)
- No partial indexes or concurrent index creation

**Mitigation:** Use MySQL 8+ or PostgreSQL 15+ in production. The schema is already compatible — only the default connection needs changing.

---

## Infrastructure — High

### H1. Session Encryption Disabled 🟠

**File:** `config/session.php`

**Problem:** `'encrypt' => env('SESSION_ENCRYPT', false)`. Session data is stored in the database in plaintext (SQLite `sessions` table when using `database` driver). The CSRF token, user ID, flash data, and session payload are all readable if the database file is compromised.

**Status:** ✅ Fixed — `config/session.php` now defaults to `true`, and `.env.example` has `SESSION_ENCRYPT=true`.

---

### H2. Session Secure Cookie Flag Not Set 🟠

**File:** `config/session.php`

**Problem:** `'secure' => env('SESSION_SECURE_COOKIE')` defaults to `null` (not set) when the env var is missing. This means session cookies are transmitted over unencrypted HTTP connections.

**Impact:** In production without HTTPS, session cookies are sent in plaintext, enabling session hijacking via network sniffing.

**Fix:** Set `SESSION_SECURE_COOKIE=true` in the production `.env`.

---

### H3. CORS Allows All Origins (`*`) 🟠

**File:** `config/cors.php`

```php
'allowed_origins' => explode(',', (string) env('CORS_ALLOWED_ORIGINS', '*')),
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => false,
```

**Problem:** When `CORS_ALLOWED_ORIGINS` is not set, the default is `*` (wildcard). Combined with `allowed_methods: *` and `allowed_headers: *`, any website can make cross-origin requests to the application's API paths.

**Impact:** Currently no API routes exist (`routes/api.php` does not exist), so this is a dormant vulnerability. If API routes are added, it becomes exploitable.

**Fix:** Explicitly set `CORS_ALLOWED_ORIGINS` in the production `.env` or restrict it in `config/cors.php`.

---

### H4. AuthThrottle Is IP-Only, No Login-Specific Limit 🟠

**File:** `app/Domain/Auth/Http/Middleware/AuthThrottleMiddleware.php`

**Problem:** The global `AuthThrottleMiddleware` applies a flat `30 requests / 60 seconds` rate limit per IP to **all** auth endpoints (login, forgot password, register, etc.). It does not:
- Differentiate between login attempts and other auth requests
- Use account-based throttling (only IP-based)
- Use the stricter `login_max_attempts` (5) value defined in `config/auth.php`

The `Login` Livewire component has its own inline rate limiter (5 attempts/60s), but this is bypassed if the component is accessed directly or via API.

**Impact:** An attacker with rotating proxies can brute-force passwords without hitting the per-IP limit.

**Fix:** Implement login-specific rate limiting using the values from `config/auth.php`:

```php
$maxAttempts = (int) config('auth.throttle.login_max_attempts', 5);
$decaySeconds = (int) config('auth.throttle.login_decay_seconds', 60);
$key = 'login:'.$request->ip().':'.$request->input('email');
```

---

### H5. Missing Indexes on 4 Foreign Key Columns 🟠

**Files:** Migration files for `handbooks`, `reports`, `certificate_templates`, `announcements`

**Problem:** Four foreign key columns use `foreignUuid('created_by')` without chaining `->index()`:

| Table | Column | Missing Index |
|-------|--------|---------------|
| `handbooks` | `created_by` | Index on user lookups |
| `reports` | `graded_by` | Index on grader-based queries |
| `certificate_templates` | `created_by` | Index on creator lookups |
| `announcements` | `created_by` | Index on author queries |

On MySQL/PostgreSQL, foreign keys do NOT automatically create indexes. Queries filtering or sorting by these columns perform full table scans.

**Fix:** Add a migration that creates indexes on these columns:

```php
Schema::table('handbooks', fn (Blueprint $t) => $t->index('created_by'));
Schema::table('reports', fn (Blueprint $t) => $t->index('graded_by'));
Schema::table('certificate_templates', fn (Blueprint $t) => $t->index('created_by'));
Schema::table('announcements', fn (Blueprint $t) => $t->index('created_by'));
```

---

### H6. Duplicate Livewire Component Instances (ThemeSwitcher + LangSwitcher) 🟠

**Files:** `resources/views/shared/layouts/sidebar.blade.php`, `resources/views/shared/ui/navbar-actions.blade.php`

**Problem:** `ThemeSwitcher` and `LangSwitcher` are each mounted **twice** on every authenticated page — once in the sidebar and once in the navbar. Each instance creates a separate Alpine.js component with its own:
- Hydration payload (JSON snapshot sent to the browser)
- Livewire network round-trip for updates
- Event listener registration

**Impact:** Every authenticated page load includes ~8KB of duplicate Livewire hydration data. Theme toggles update two components instead of one.

**Fix:** Render the sidebar instances only on mobile (`lg:hidden`) or remove them:

```blade
{{-- sidebar.blade.php --}}
<div class="lg:hidden">
    <livewire:shared.theme-switcher />
    <livewire:shared.lang-switcher />
</div>
```

---

### H7. `companies` Table Has No Indexes on Search Columns 🟠

**File:** Migration `2026_04_29_112711_create_companies_table.php`

**Problem:** The `companies` table has no indexes on `name` or `industry_sector`. Queries that filter companies by name or sector perform full table scans.

**Impact:** As the partnership database grows, company search becomes progressively slower.

**Fix:** Add indexes in a new migration:

```php
Schema::table('companies', function (Blueprint $table) {
    $table->index('name');
    $table->index('industry_sector');
});
```

---

## Infrastructure — Medium

### M7. Cache Key Literal Not Registered in `CacheKeys` 🟡

**File:** `app/Domain/Auth/Actions/LoginAction.php:105,139`

**Problem:** `LoginAction` uses hardcoded string `'login-failures:'.$user->id` as a cache key. This key is not declared in `App\Domain\Core\Support\CacheKeys`, bypassing the centralized registry. Other developers are unaware this key exists, and systematic cache flushing cannot target it.

**Impact:** Cache key collision (unlikely but possible) and discoverability gap — developers cannot enumerate all cache keys without grepping the codebase.

**Fix:** Register the key pattern in `CacheKeys`:
```php
public const string AUTH_LOGIN_FAILURES = 'auth.login-failures:';
```

---

### M8. Session Security Variables Missing from `.env.example` 🟡

**Files:** `.env.example`, `config/session.php`

**Problem:** Four session security configuration variables are defined in `config/session.php` with secure defaults, but are absent from `.env.example`:

| Variable | Config Default | Purpose |
|---|---|---|
| `SESSION_ENCRYPT` | `true` | Encrypts session data in the store |
| `SESSION_SECURE_COOKIE` | auto (true in production) | HTTPS-only cookie |
| `SESSION_HTTP_ONLY` | `true` | JavaScript cannot access the cookie |
| `SESSION_SAME_SITE` | `lax` | CSRF protection |

While the code defaults are secure, administrators cannot discover or verify these settings without reading the source code. A missing `SESSION_SECURE_COOKIE` in a non-standard environment could silently disable HTTPS enforcement.

**Fix:** Add commented entries to `.env.example`:
```env
# Session security (defaults are secure — uncomment to override)
# SESSION_ENCRYPT=true
# SESSION_SECURE_COOKIE=true
# SESSION_HTTP_ONLY=true
# SESSION_SAME_SITE=lax
```

---

### M9. `IMAGE_DRIVER` Not Exposed in `.env.example` 🟡

**Files:** `.env.example`, `config/media-library.php:213`

**Problem:** `config/media-library.php` reads `env('IMAGE_DRIVER', 'gd')` but this variable is not present in `.env.example`. Administrators who want to switch to ImageMagick for higher-quality conversions cannot discover this option without reading the package configuration.

**Impact:** Schools with `ext-imagick` installed cannot enable it without knowing the undocumented variable name.

**Fix:** Add to `.env.example`:
```env
# Image driver: gd (default, built-in) or imagick (higher quality, requires ext-imagick)
# IMAGE_DRIVER=gd
```

---

### M1. LIKE Queries with Leading Wildcard

**Files:** `CompanyManager.php:71`, `ActivityLog.php:57-60`

**Problem:** Two locations use `LIKE '%value'` (leading wildcard), which prevents B-tree index usage:

1. `CompanyManager::search()` → `where('industry_sector', 'like', "%{$v}%")`
2. `ActivityLog::scopeForModule()` → `LIKE %\{module}\%` pattern on `subject_type`

**Impact:** Full table scan on every company sector search. Activity log queries with module filter degrade on large datasets.

**Fix:** For prefix-matching (e.g., sector starts with), remove the leading `%`. For full-text search, consider SQLite FTS or PostgreSQL `tsvector`.

---

### M2. Job Payloads Stored Unencrypted

**File:** `config/queue.php`, `database` driver

**Problem:** The `jobs` table stores serialized PHP objects as JSON in the `payload` column. The payload contains serialized model data and is not encrypted. If the database is compromised, attackers can:
- Read all data from queued jobs (including sensitive model attributes)
- Possibly craft malicious serialized objects (PHP object injection vectors)

**Fix:** Use the Redis queue driver with TLS in production, or encrypt sensitive data before queueing.

---

### M3. No Framework Rate Limiter Configured

**File:** `bootstrap/app.php`

**Problem:** `RateLimiter::for()` is never called. The framework's built-in `throttle` middleware alias is not configured. Only two custom middlewares (`AuthThrottleMiddleware`, `ProtectSetupRouteMiddleware`) provide rate limiting. All other routes have no protection against abuse.

**Impact:** Admin CRUD endpoints (user creation, settings changes, mass operations) have no rate limiting.

**Fix:** Add named rate limiters:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    // ...
)
->withMiddleware(function (Middleware $middleware) {
    RateLimiter::for('admin', fn ($job) => Limit::perMinute(30));
    RateLimiter::for('api', fn ($job) => Limit::perMinute(60));
});
```

---

### M4. Log Level Set to `debug`

**File:** `.env`

**Problem:** `LOG_LEVEL=debug`. All SQL queries, HTTP requests, and framework internal messages are written to the log file. In production, this can generate gigabytes of log data and potentially leak sensitive information.

**Fix:** Set `LOG_LEVEL=warning` or `error` in the production `.env`. Use the `daily` log channel for automatic rotation.

---

### M5. `APP_DEBUG=true` in Development `.env`

**File:** `.env`

**Problem:** `APP_DEBUG=true` must be set to `false` in production. While this is acceptable for development, CI/CD pipelines must ensure the production `.env` has `APP_DEBUG=false` to prevent stack trace leakage.

**Fix:** Ensure deployment scripts explicitly set `APP_DEBUG=false`.

---

### M6. Duplicate Indexes on `mentees.user_id` and `mentors.user_id`

**File:** Migration files for `mentees` and `mentors`

**Problem:** Both columns have `$table->foreignUuid('user_id')->index()` followed by a separate `$table->index('user_id')` on the next line, creating two identical indexes on the same column. This is redundant and adds write overhead.

**Fix:** Remove the duplicate `$table->index('user_id')` calls from the migration files.

---

## Infrastructure — Low

### L1. `Setup::state()` Race Condition

**File:** `app/Domain/Setup/Models/Setup.php:55`

**Problem:** The `state()` method uses `self::latest('created_at')->first() ?? new self` without locking. Multiple simultaneous setup requests could read stale data, create duplicate setup records, or race on the `is_installed` flag.

**Fix:** Use `firstOrCreate()` with atomic locking or a unique constraint on a singleton primary key.

---

### L2. Nginx vs Middleware `X-Frame-Options` Mismatch

**Files:** `.docker/nginx.conf`, `config/security-headers.php`

**Problem:** The Nginx config sets `X-Frame-Options: SAMEORIGIN` (less strict), while the PHP `SecurityHeaders` middleware sets `X-Frame-Options: DENY` (strict). The middleware value takes precedence in PHP-processed responses, but static files served directly by Nginx use the more permissive SAMEORIGIN.

**Fix:** Align the Nginx config with the middleware: change Nginx to `add_header X-Frame-Options "DENY"`.

---

### L3. `Integrity::verify()` Can `exit(1)` the Application

**File:** `app/Domain/Core/Support/Integrity.php`

**Problem:** The `Integrity::verify()` method is called from `public/index.php` and `AppInfo::all()`. If `composer.json` has been modified with a different author name, the application calls `exit(1)` with a 403 error page. This is a tamper-protection measure but can be a support burden:
- Team deployments where `composer.json` is legitimately updated
- CI/CD pipelines that modify `composer.json` during build
- Package updates that rewrite the `authors` array

**Fix:** Relax the check to log a warning instead of halting, or add an env var to bypass it in CI.

---

### L4. CORS Config References Non-Existent API Routes

**File:** `config/cors.php`

**Problem:** `'paths' => ['api/*', 'sanctum/csrf-cookie']` — but `routes/api.php` does not exist and Sanctum is not installed. This is dead configuration that adds unnecessary CORS header processing.

**Fix:** Remove `api/*` and `sanctum/csrf-cookie` from the CORS paths until API routes are implemented.

---

## SQLite vs MySQL Differences

The application defaults to SQLite in development, but production usually runs MySQL or PostgreSQL. This difference causes several gotchas.

SQLite requires an explicit `PRAGMA foreign_keys = ON` to enforce foreign key constraints. Without this, orphaned records can accumulate silently. The database configuration enables this by default, but custom raw SQL queries must set the pragma manually.

SQLite has limited `ALTER TABLE` support. Most schema changes require recreating the table. This means migration order matters more — adding a column to a table that another migration just modified may fail. Check `Schema::hasColumn()` before adding columns that might already exist.

SQLite does not support `ENUM` types. Enum columns in MySQL are represented as `TEXT` columns with `CHECK` constraints in SQLite. The migration syntax differs, and the `check()` method must be used when adding enum-like columns.

SQLite writes lock the entire database file. Under concurrent write load, "database is locked" errors will occur. This is expected behavior — the solution is to use MySQL or PostgreSQL in production.

## UUID Considerations

UUID primary keys are larger than integer keys (16 bytes vs 4-8 bytes). This means indexes are larger and joins are slightly slower. At the expected data volumes this is not a problem, but it is worth noting for tables that will grow very large.

UUIDs make database dumps and manual queries less convenient — you cannot guess a record's ID or iterate through them sequentially. All queries should use meaningful criteria (email, name, date) rather than relying on ID ordering.

## Queue Worker Requirement

The queue worker is not optional. Without it, notifications are never sent, media conversions never happen, mail never goes out, and scheduled tasks accumulate. In development, the queue can run synchronously (via the `sync` driver) or by running `php artisan queue:work` in a terminal. In production, Supervisor or systemd must keep the worker running.

If jobs appear stuck in the "processing" state, the worker likely crashed. Run the prune-failed command to reset them. If jobs are never picked up, check that the queue connection in `.env` matches the worker's connection.

## Storage Permissions

The `storage/` and `bootstrap/cache/` directories must be writable by the web server user. This includes subdirectories for logs, framework files, views, and cache. On Linux, this typically means `chown -R www-data:www-data storage bootstrap/cache`. Without correct permissions, the application returns blank pages or file upload errors.

SELinux on RHEL-based distributions adds another layer of permissions. The storage directory needs the `httpd_sys_rw_content_t` context label.

The public storage symlink (`public/storage` -> `storage/app/public`) must exist for uploaded files and brand assets to be accessible. This is created by `php artisan storage:link`. If media URLs return 404, the symlink is likely missing.

## Development Workflow Gotchas

If you see "Unable to locate file in Vite manifest," the frontend assets have not been built. Run `npm run build` or `npm run dev` (or `composer run dev` which starts everything).

If configuration changes do not take effect, run `php artisan optimize:clear` to flush cached config, routes, and views. The config cache must be regenerated after any change to `config/*.php` files.

If Livewire components do not update after data changes, check that the component has reactive properties and that `$this->dispatch()` is being used for inter-component communication.

## Translation Gaps — Indonesian (id)

The `lang/id/` directory is missing translations compared to `lang/en/`:

| File | en Keys | id Keys | Gap |
|---|---|---|---|
| `internship.php` | 82 | 69 | **13 keys missing** (legacy flat registration_verification keys) |
| `registration.php` | 45 | 45 | 🟢 ✅ |
| `placement.php` | 57 | 57 | 🟢 ✅ |

Additionally, `user.php` has different key ordering/structure between en and id.

All Indonesian text that falls through missing keys renders in English (Laravel fallback behavior). This affects the admin panel and student-facing features.

## MCP redirect_domains Wildcard

`config/mcp.php` has `'redirect_domains' => ['*']` which allows any redirect URI. For OAuth security, this should be restricted to known application domains.

## Undocumented Environment Variables

7 Boost configuration variables are missing from `.env.example`:

| Variable | Config File | Default |
|---|---|---|
| `BOOST_ENABLED` | `config/boost.php` | `true` |
| `BOOST_BROWSER_LOGS_WATCHER` | `config/boost.php` | `true` |
| `BOOST_PHP_EXECUTABLE_PATH` | `config/boost.php` | `null` |
| `BOOST_COMPOSER_EXECUTABLE_PATH` | `config/boost.php` | `null` |
| `BOOST_NPM_EXECUTABLE_PATH` | `config/boost.php` | `null` |
| `BOOST_VENDOR_BIN_EXECUTABLE_PATH` | `config/boost.php` | `null` |
| `BOOST_CURRENT_DIRECTORY_EXECUTABLE_PATH` | `config/boost.php` | `base_path()` |

Without these in `.env.example`, developers cannot discover or configure Boost.

## Test Artifacts in Storage

`storage/framework/testing/` contains leftover test sessions and disk directories. These are generated by `LazilyRefreshDatabase` and should not be committed or deployed. Ensure `.gitignore` covers these paths.

---

## Backlog — Unresolved Items

### Feature Test Coverage (~68 uncovered Actions)

| Domain | Actions | Feature Tests | Gap |
|---|---|---|---|
| Assessment | 17 | 0 | 🔴 |
| Internship | 21 | 7 | 🔴 |
| Auth | 12 | 0 | 🔴 |
| Admin | 9 | 9 | 🟢 ✅ |
| Attendance | 8 | 0 | 🔴 |
| Partnership | 8 | 8 | 🟢 ✅ |
| Mentor | 8 | 0 | 🔴 |
| Placement | 7 | 7 | 🟢 ✅ |
| Assignment | 7 | 0 | 🔴 |
| School | 9 | 0 | 🔴 |
| Registration | 6 | 2 | 🟡 |
| Document | 4 | 0 | 🔴 |
| Logbook | 4 | 0 | 🔴 |
| Certificate | 4 | 0 | 🔴 |
| Incident | 3 | 0 | 🔴 |
| Mentee | 3 | 0 | 🔴 |
| Schedule | 3 | 0 | 🔴 |
| Guidance | 2 | 2 | 🟢 ✅ |
| Evaluation | 3 | 1 | 🟡 |
| User | 8 | 5 | 🟢 |
| Setup | 9 | 9 | 🟢 |
| Settings | 6 | 6 | 🟢 |

### Cross-Domain Event Flow Documentation 🟢

Which events fire and which listeners react is not documented. Needed for understanding side effects when modifying Actions.

### Real-Time Features (Future) 🟢

Laravel Echo and Reverb are installed but no real-time channels are active. Candidates: notification delivery, dashboard updates, attendance confirmations.

### Queue Job Formalization (Future) 🟢

Evaluate which operations should be queued: certificate generation, report rendering, batch notifications. Currently all notifications use `ShouldQueue`.

### GD8. Acknowledgement Not Used as Gate 🟢

Handbook acknowledgement is purely informational — it does not block any action. Registration, attendance clock-in, logbook submission all work without having acknowledged any handbook. Fix: Add configurable gating logic.

### PD14. Unsorted Translation Keys in `placement.php` 🟡

`lang/en/placement.php` and `lang/id/placement.php` have keys in different orders. This makes diff-review difficult.

### Livewire Form Object Migration (~45 components remaining) 🟡

Livewire components still manage form state via flat `public` properties. Completed for Setup, Auth, Profile, Settings, Internship, Guidance, Registration, Placement. ~45 components remain.

**Convention:** See `docs/conventions.md` Section 9a — Form Objects.

### BaseAction Cannot Enforce `execute()` Signature 🟡

`BaseAction` is an abstract class with no abstract `execute()` method. Each Action defines its own signature. There is no way to enforce a consistent calling convention.

---

## Summary

| Severity | Issue | Category | Status |
|---|---|---|---|
| 🔴 | **K1** Trusted proxies not configured — rate limiting broken behind LB | Infrastructure | ✅ Fixed |
| 🔴 | **K2** Exception hierarchy not consumed — all errors render as 500 | Infrastructure | ✅ Fixed |
| 🔴 | **K3** `after_commit: false` on all queue connections | Infrastructure | ✅ Fixed |
| 🔴 | **K4** `getActiveRegistration()` N+1 query | Performance | ✅ Fixed |
| 🔴 | **K5** SQLite for production — no concurrent write support | Infrastructure | 🟡 Doc (engine choice) |
| 🔴 | **K6** Cache config default `database` instead of `file` | Infrastructure | ✅ Fixed |
| 🔴 | **K7** Queue config default `database` instead of `sync` | Infrastructure | ✅ Fixed |
| 🔴 | **K8** MAIL_MAILER default `smtp` in `.env.example` | Configuration | ✅ Fixed |
| 🟠 | **H1** Session encryption disabled | Security | ✅ Fixed |
| 🟠 | **H2** Session secure cookie flag not set | Security | ✅ Fixed |
| 🟠 | **H3** CORS wildcard origins | Security | ✅ Fixed |
| 🟠 | **H4** AuthThrottle IP-only, config `max_attempts` (5) unused | Security | ✅ Fixed |
| 🟠 | **H5** Missing indexes on 4 FK columns | Performance | ✅ Fixed |
| 🟠 | **H6** Duplicate Livewire: ThemeSwitcher + LangSwitcher ×2 | Performance | ⏳ |
| 🟠 | **H7** Companies table no indexes on search columns | Performance | ⏳ |
| 🟡 | **M1** LIKE with leading wildcard in 2 locations | Performance | ⏳ |
| 🟡 | **M2** Job payloads stored unencrypted | Security | ⏳ |
| 🟡 | **M3** No framework RateLimiter configured | Security | ✅ Fixed |
| 🟡 | **M4** Log level set to debug | Observability | ⏳ |
| 🟡 | **M5** APP_DEBUG=true in .env | Security | ⏳ |
| 🟡 | **M6** Duplicate indexes on mentees/mentors user_id | Performance | ✅ Fixed |
| 🟡 | **M7** Cache key `login-failures` not registered in `CacheKeys` | Architecture | ✅ Fixed |
| 🟡 | **M8** Session security vars missing from `.env.example` | Configuration | ✅ Fixed |
| 🟡 | **M9** `IMAGE_DRIVER` not exposed in `.env.example` | Configuration | ✅ Fixed |
| 🟢 | **L1** Setup::state() race condition | Infrastructure | ⏳ |
| 🟢 | **L2** Nginx vs middleware X-Frame-Options mismatch | Security | ✅ Fixed |
| 🟢 | **L3** Integrity::verify() can exit(1) | Reliability | ⏳ |
| 🟢 | **L4** CORS paths reference non-existent API routes | Config | ✅ Fixed |
| 🟢 | Feature tests missing for ~75 of 164 Actions | Testing | ⏳ |
| 🟢 | Indonesian `internship.php` missing 13 keys | Translation | ⏳ |
| 🟢 | GD8 Acknowledgement not used as gate | Guidance | ⏳ |
| 🟢 | Cross-domain event flow undocumented | Documentation | ⏳ |
| 🟢 | Real-time features (Echo + Reverb) not yet active | Future | ⏳ |
| 🟢 | Queue job formalization not evaluated | Future | ⏳ |
