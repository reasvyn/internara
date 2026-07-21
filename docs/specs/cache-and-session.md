# Cache & Session — Caching Strategy, Invalidation & Session Management

> **Last updated:** 2026-07-21 **Changes:** feat — initial spec covering cache driver strategy,
> key registry, invalidation patterns, session configuration, security, and garbage collection

## Description

Complete specification of Internara's cache and session subsystems. Defines the tiered cache driver
strategy, centralized key registry, event-driven and observer-based invalidation patterns, session
lifecycle, security controls, and garbage collection. These subsystems are foundational to
performance (cache) and authentication state (session) across all 22 modules.

This spec is a focused expansion of the cache and session foundations introduced in
[core-infra.md](core-infra.md) (§6.6, §6.7, §DD-3, §DD-4, §DD-5).

---

## 1. Problem Statements

### PS-1 — Cache Consistency Across Modules

With 22 modules independently reading and writing cached data, stale reads become likely when one
module updates a value that another module's cache still holds. Without a systematic invalidation
strategy, the system would serve outdated settings, stale dashboard statistics, and incorrect
brand colors — eroding user trust.

### PS-2 — Cache Key Collision Risk

Modules independently inventing cache key names leads to collisions. A key like `stats` could mean
dashboard stats in one module and assessment stats in another. Without a centralized registry,
debugging cache misses and performing targeted cache flushes is impossible.

### PS-3 — Session Security in Shared Environments

Schools operate on shared computers (labs, teacher offices). A session left active on a browser
after the user walks away is a credential theft vector. Session fixation, where an attacker
pre-sets a session ID, must also be prevented. The session subsystem must enforce secure defaults
without requiring school IT expertise.

### PS-4 — Session Persistence Across Deployments

Deployments that clear the filesystem (common on shared hosting) destroy file-based sessions,
logging out all active users. This disrupts teachers mid-grading or students mid-submission. The
default session driver must survive process restarts without external services.

### PS-5 — Multi-Tier Configuration Complexity

Schools range from single-server shared hosting (Tier 1) to multi-server Redis-backed deployments
(Tier 3). The cache and session subsystems must work out-of-the-box on Tier 1 while enabling
opt-in performance gains on Tier 2+, without code changes — only `.env` configuration.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide zero-config cache and session for shared hosting (Tier 1) |
| G2  | Ensure 100% of cache keys are centrally registered and discoverable |
| G3  | Invalidate stale cache within 5 seconds of the underlying data changing |
| G4  | Prevent session fixation and enforce secure cookie defaults |
| G5  | Support tier-based driver upgrades (file → Redis) via `.env` only |
| G6  | Ensure session persistence across process restarts with database driver |
| G7  | Provide probabilistic garbage collection that doesn't require cron jobs |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Real-time cache synchronization across multiple servers |
| NG2  | Session sharing across separate application instances |
| NG3  | Multi-tenant session isolation (single-tenant only) |
| NG4  | Custom cache backends beyond Laravel's built-in drivers |
| NG5  | Session-based CSRF token rotation independent of Laravel defaults |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Updates System Settings

**Actor:** School administrator
**Preconditions:** Admin is authenticated, has settings write permission
**Flow:**
1. Admin navigates to System Settings, changes school name
2. Admin saves the form
3. SettingObserver fires on `Setting::updated` event
4. Observer invalidates `settings.{key}`, `settings.all`, and group cache
5. Next request from any user sees the updated school name
**Postconditions:** All users see the updated setting within one request cycle

### UC-2 — Student Logs In from School Lab

**Actor:** Student
**Preconditions:** Student account exists and is active
**Flow:**
1. Student navigates to login page
2. Student submits credentials
3. LoginAction authenticates, creates session, regenerates session ID
4. Session stored in database (default) with encrypted payload
5. HTTP-only cookie sent to browser
**Postconditions:** Student is authenticated; session fixation prevented by regeneration

### UC-3 — Session Expires After Inactivity

**Actor:** Teacher who left computer unattended
**Preconditions:** Teacher was logged in but idle for >120 minutes
**Flow:**
1. Teacher returns, clicks a link
2. Browser sends session cookie
3. Laravel checks `last_activity` timestamp against `lifetime` (120 min)
4. Session is expired; user redirected to login
**Postconditions:** Stale session is invalidated; no data exposure

### UC-4 — Deployment Upgrades from Tier 1 to Tier 2

**Actor:** School IT admin
**Preconditions:** Redis server installed, application on Tier 1 (file cache, database session)
**Flow:**
1. IT admin updates `.env`: `CACHE_STORE=redis`, `SESSION_DRIVER=redis`
2. Runs `php artisan cache:clear` to flush file cache
3. Application immediately starts using Redis for cache and session
4. No code changes, no migration needed
**Postconditions:** Cache and session now backed by Redis; performance improves

### UC-5 — Cache Miss Recomputes Automatically

**Actor:** System (automatic)
**Preconditions:** Cached value has been evicted or expired
**Flow:**
1. Read Action calls `Cache::remember()` with key and callback
2. Cache store returns miss
3. Callback executes, queries database
4. Result stored in cache with configured TTL
5. Subsequent reads hit cache until next expiry
**Postconditions:** Application functions correctly on cache miss; no error surfaced

---

## 4. Functional Requirements

### Cache — Driver & Configuration

| ID   | Requirement |
| ---- | ----------- |
| FR-CD1 | System must default to `file` cache driver (`CACHE_STORE=file`) for Tier 1 |
| FR-CD2 | System must support `redis` cache driver via `CACHE_STORE=redis` env var |
| FR-CD3 | Cache prefix must be `{app_name}-cache-` to prevent cross-app key collisions |
| FR-CD4 | Redis cache must use `REDIS_CACHE_CONNECTION` (default: `cache`) for connection selection |
| FR-CD5 | System must support Redis cluster via `REDIS_CLUSTER=true` env var |

### Cache — Key Registry

| ID   | Requirement |
| ---- | ----------- |
| FR-CR1 | Every cache key MUST be declared in `config/cache-keys.php` (C4 invariant) |
| FR-CR2 | Cache key naming must follow `{module}.{purpose}[.{qualifier}]` convention |
| FR-CR3 | Keys with dynamic suffixes must end with `:` separator (e.g., `notification.unread:`) |
| FR-CR4 | Registry must be a flat PHP array returning string values |
| FR-CR5 | Keys must be accessed via `config('cache-keys.{key_name}')` at runtime |

### Cache — Invalidation

| ID   | Requirement |
| ---- | ----------- |
| FR-CI1 | Setting model changes must be invalidated by `SettingObserver` (created/updated/deleted) |
| FR-CI2 | Observer must invalidate individual key, group key, `settings.all`, and theme keys when applicable |
| FR-CI3 | Cross-module cache invalidation must use event-driven pattern (Action → Event → Listener → forget) |
| FR-CI4 | Direct `Cache::forget()` is allowed for simple, same-module invalidation |
| FR-CI5 | Full cache flush (`cache:clear`) must be used for maintenance only, never in normal operations |

### Cache — Read Action Integration

| ID   | Requirement |
| ---- | ----------- |
| FR-CR6 | `BaseReadAction` must provide `remember(key, callback, ttl)` helper |
| FR-CR7 | `BaseReadAction` must provide `rememberForever(key, callback)` helper |
| FR-CR8 | `BaseReadAction` must provide `forget(key)` helper |
| FR-CR9 | `BaseReadAction` must provide `cacheKey(purpose, ...qualifiers)` auto-building helper |

### Cache — Settings Service

| ID   | Requirement |
| ---- | ----------- |
| FR-CS1 | `Settings::get()` must use `Cache::rememberForever()` with `settings.{key}` pattern |
| FR-CS2 | `Settings::all()` must use `Cache::rememberForever()` with `settings.all` key |
| FR-CS3 | `Settings::group()` must use `Cache::rememberForever()` with `settings.group.{name}` key |
| FR-CS4 | `Settings::forget()` must invalidate individual key, group key, `settings.all`, and theme keys |
| FR-CS5 | `Settings::override()` must allow in-memory overrides that bypass cache entirely |
| FR-CS6 | `Settings` must gracefully handle `QueryException` — log warning, return default, never crash |

### Session — Driver & Configuration

| ID   | Requirement |
| ---- | ----------- |
| FR-SD1 | System must default to `database` session driver for Tier 1 |
| FR-SD2 | System must support `redis` session driver via `SESSION_DRIVER=redis` env var |
| FR-SD3 | Session lifetime must default to 120 minutes of inactivity |
| FR-SD4 | Session encryption must be enabled by default (`SESSION_ENCRYPT=true`) |
| FR-SD5 | Session cookie must be HTTP-only, SameSite=lax, secure in production |

### Session — Lifecycle

| ID   | Requirement |
| ---- | ----------- |
| FR-SL1 | Session ID must be regenerated on every authentication state change (login/logout) |
| FR-SL2 | Session data must be cleared from store on logout |
| FR-SL3 | "Remember me" must create a separate recaller token (5-year default, hashed) |
| FR-SL4 | Session must persist locale preference via `SetLocaleMiddleware` |

### Session — Garbage Collection

| ID   | Requirement |
| ---- | ----------- |
| FR-SG1 | Database sessions must use probabilistic GC with lottery `[2, 100]` (2% chance/request) |
| FR-SG2 | `sessions` table must have indexed `last_activity` column for GC queries |
| FR-SG3 | Redis sessions must rely on Redis key expiry — no application-level GC needed |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-P1 | Cache hit must return in < 1ms (file) or < 0.5ms (Redis) |
| NFR-P2 | Cache miss + callback must not add > 50ms to response time for typical queries |
| NFR-P3 | Settings cache must survive until explicit invalidation (forever TTL) |
| NFR-S1 | Session payload must be encrypted at rest (`SESSION_ENCRYPT=true`) |
| NFR-S2 | Session cookie must be inaccessible to JavaScript (`http_only=true`) |
| NFR-S3 | Session cookie must not be sent to cross-origin sites (`same_site=lax`) |
| NFR-S4 | Session fixation must be prevented by ID regeneration on auth changes |
| NFR-S5 | Password confirmation timeout must default to 15 minutes (`auth.password_timeout`) |
| NFR-R1 | Cache must degrade gracefully — application must function correctly on cache miss |
| NFR-R2 | Settings cache must catch `QueryException` and return defaults without crashing |
| NFR-R3 | Activity log cache failure must not break the calling Action |
| NFR-U1 | Tier 1 deployment must require zero external services for cache and session |
| NFR-U2 | Tier upgrade (file→Redis) must require only `.env` changes and cache flush |
| NFR-M1 | All 26+ cache keys must be discoverable in a single file (`config/cache-keys.php`) |
| NFR-M2 | Cache invalidation logic must be traceable from Observer/Listener to key name |

---

## 6. API / Data Contracts

### 6.1 Cache Configuration

```php
// config/cache.php — key settings
'default' => env('CACHE_STORE', 'file'),

'stores' => [
    'file' => [
        'driver' => 'file',
        'path'   => storage_path('framework/cache/data'),
        'lock'   => storage_path('framework/cache/data'),
    ],
    'redis' => [
        'driver'     => 'redis',
        'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
        'prefix'     => env('APP_NAME', 'internara').'-cache-',
    ],
],
```

### 6.2 Cache Key Registry

```php
// config/cache-keys.php
return [
    'setup_installed'        => 'setup.is_installed',
    'setup_token_generation' => 'setup.token.generation',
    'admin_dashboard_stats'  => 'sysadmin.dashboard.stats',
    'theme_css_variables'    => 'theme.css_variables',
    'brand_colors'           => 'brand.colors',
    'notification_unread'    => 'notification.unread:',
    'core_app_name'          => 'core.app_name',
    'appinfo_metadata'       => 'core.appinfo_metadata',
    'module_livewire'        => 'module.discovered_livewire',
    'module_policies'        => 'module.discovered_policies',
    'module_views'           => 'module.discovered_views',
    'auth_login_lockout'     => 'auth.login.lockout:',
    'dashboard_student'      => 'dashboard.student.',
    'auth_login_attempts'    => 'auth.login.attempts:',
    'auth_login_failures'    => 'auth.login-failures:',
    'health_check'           => 'system.health_check',
    'recover_admin_attempts' => 'auth.recover.attempts:',
    'recovery_otp'           => 'auth.recover.otp:',
    'recovery_otp_hash'      => 'auth.recover.otp_hash:',
    'settings_all'           => 'settings.all',
    'settings_group'         => 'settings.group.',
    'settings_keys'          => 'settings.keys',
    'settings_key'           => 'settings.',
    'school_entity'          => 'academics.school.entity',
    'user_single'            => 'user.',
    'users_count'            => 'users.count',
];
```

### 6.3 BaseReadAction Cache Helpers

```php
// app/Core/Actions/BaseReadAction.php
abstract class BaseReadAction
{
    protected function remember(string $key, callable $callback, int $ttl = 300): mixed;
    protected function rememberForever(string $key, callable $callback): mixed;
    protected function forget(string $key): void;
    protected function cacheKey(string $purpose, string ...$qualifiers): string;
    protected function mask(array $data, array $fields = []): array;
    protected function paginate(Builder $query, int $perPage = 15): LengthAwarePaginator;
}
```

### 6.4 Settings Service

```php
// app/Settings/Services/Settings.php
final class Settings
{
    public static function get(string|array $key, mixed $default = null, bool $skipCache = false): mixed;
    public static function all(bool $skipCache = false): Collection;
    public static function has(string $key): bool;
    public static function group(string $name, bool $skipCache = false): Collection;
    public static function override(array $overrides): void;
    public static function clearOverrides(): void;
    public static function forget(string $key, ?string $group = null): void;
    public static function forgetGroup(string $name): void;
    public static function keys(bool $skipCache = false): Collection;
}
```

### 6.5 SettingObserver

```php
// app/Settings/Observers/SettingObserver.php
final class SettingObserver
{
    public function created(Setting $setting): void;  // → invalidate()
    public function updated(Setting $setting): void;  // → invalidate()
    public function deleted(Setting $setting): void;  // → invalidate()
    // invalidate() clears: settings.{key}, settings.all, settings.group.{group},
    //   theme.css_variables, brand.colors (if theme key)
}
```

### 6.6 Session Configuration

```php
// config/session.php — key settings
'driver'        => env('SESSION_DRIVER', 'database'),
'lifetime'      => 120,           // minutes
'encrypt'       => true,
'expire_on_close' => false,
'cookie'        => env('SESSION_COOKIE', 'internara_session'),
'http_only'     => true,
'secure'        => env('APP_ENV') === 'production',
'same_site'     => 'lax',
'lottery'       => [2, 100],     // 2% GC chance per request
'connection'    => env('SESSION_CONNECTION'),  // for database driver
'table'         => 'sessions',
```

### 6.7 Sessions Table Schema

```
sessions
├── id              VARCHAR(255)   PRIMARY KEY  — session ID (hash)
├── user_id         VARCHAR(36)    NULLABLE      — authenticated user UUID
├── ip_address      VARCHAR(45)    NULLABLE      — client IP
├── user_agent      TEXT           NULLABLE      — browser User-Agent
├── payload         TEXT           NOT NULL      — encrypted session data
└── last_activity   INTEGER        NOT NULL      — UNIX timestamp
    └── INDEX
```

### 6.8 Invalidation Flow — Event-Driven

```
Command Action → dispatchEvent(EntityUpdated)
                      ↓
        CacheInvalidationListener::handle()
                      ↓
        Cache::forget(config('cache-keys.{affected_key}'))
```

### 6.9 Invalidation Flow — Observer-Driven

```
Setting::create/update/delete
        ↓
SettingObserver::created/updated/deleted()
        ↓
Cache::forget('settings.{key}')
Cache::forget('settings.all')
Cache::forget('settings.group.{group}')
Cache::forget('theme.css_variables')  // if theme key
Cache::forget('brand_colors')         // if theme key
```

---

## 7. Design Decisions

### DD-1 — File Cache as Default (Reaffirmed from core-infra DD-4)

**Decision:** Default cache driver is `file`, not Redis.
**Rationale:** Shared hosting deployments (Tier 1) cannot install Redis. File cache works with zero
external services. The `storage/framework/cache/data` directory is automatically managed by Laravel.
**Trade-off:** File cache is slower than Redis (~2-5ms vs ~0.5ms per read) and doesn't support
atomic increment/decrement. Acceptable for single-tenant workloads with <500 concurrent users.

### DD-2 — Database Session as Default (Reaffirmed from core-infra DD-5)

**Decision:** Default session driver is `database`, not `file`.
**Rationale:** Database sessions survive process restarts and `optimize:clear` commands. File
sessions are destroyed when `storage/framework/sessions` is cleared during deployment. The
`sessions` table is auto-created by migration and requires zero configuration.
**Trade-off:** One extra DB query per request for session lookup. Negligible for single-tenant with
<1000 concurrent users.

### DD-3 — SettingObserver Over Event Listeners for Cache Invalidation

**Decision:** Settings cache invalidation uses an Eloquent Observer (`SettingObserver`), not event
listeners.
**Rationale:** Eloquent model events (`created`, `updated`, `deleted`) fire automatically for every
mutation — no need to remember to dispatch events from Actions. This is a model-level concern
(model changed → invalidate its cache) that naturally fits the Observer pattern. Events would
require every Settings-mutating Action to manually dispatch an invalidation event.
**Trade-off:** Observer fires for ALL Setting mutations, including those from tinker or migrations.
This is acceptable because cache invalidation is idempotent.

### DD-4 — Forever TTL for Settings Cache

**Decision:** Settings use `Cache::rememberForever()`, not time-based TTL.
**Rationale:** Settings change infrequently (admin updates). Time-based TTL would serve stale data
for the TTL window after a change. Forever TTL + explicit invalidation on write guarantees
consistency: the cache is always current until the exact moment it's invalidated.
**Trade-off:** If invalidation fails (e.g., Observer throws), stale data persists until
`cache:clear`. Mitigated by Observer wrapping invalidation in try-catch with logging.

### DD-5 — Probabilistic GC for Database Sessions

**Decision:** Use lottery `[2, 100]` for session garbage collection, not cron-based cleanup.
**Rationale:** Probabilistic GC requires no cron job, no scheduler configuration, and scales
naturally with traffic. At 10,000 requests/day, ~200 GC runs/day keeps the sessions table lean.
Schools on shared hosting often cannot configure cron jobs.
**Trade-off:** Stale sessions may persist longer during low-traffic periods. Maximum retention is
bounded by session lifetime (120 min) regardless of GC timing.

### DD-6 — Cache Key Naming with Colon Suffix for Dynamic Keys

**Decision:** Keys with dynamic suffixes use `:` as separator (e.g., `notification.unread:`).
**Rationale:** The colon visually separates the static key prefix from the dynamic qualifier
(user ID, setting name). This convention is consistent across all 26+ registered keys and makes
pattern-based cache flushing possible (e.g., flush all `auth.login.attempts:*` keys).
**Trade-off:** Slightly unusual compared to dot-separated conventions, but the colon is a standard
namespace separator in cache systems (Redis, Memcached).

---

## 8. Success Metrics

### 8.1 Cache Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Cache key registration | 100% of keys in `config/cache-keys.php` | `grep -r "Cache::" app/` → all keys resolve to config |
| Settings consistency | < 1 second stale window | Observer fires on every model event |
| Cache warm time | < 5 seconds | `time php artisan system:cache-warm` |
| Cache miss recovery | Application functions correctly | All `Cache::remember()` callbacks return valid data |

### 8.2 Session Security

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Encryption | Always enabled | `SESSION_ENCRYPT=true` in default config |
| HTTP-only | Cookie inaccessible to JS | `http_only=true` in session config |
| SameSite | Lax by default | `same_site=lax` in session config |
| Lifetime | 120 minutes inactivity timeout | Default config value |
| Fixation prevention | Regenerated on auth change | `session()->regenerate()` in login/logout flow |

### 8.3 Session Reliability

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Persistence | Survives process restart | Database driver default |
| GC efficiency | No manual cron needed | Lottery `[2,100]` produces ~2% GC rate |
| Locale persistence | Survives requests | `SetLocaleMiddleware` reads/writes session |

### 8.4 Tier Compatibility

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Tier 1 zero-config | No external services required | Default `.env` works on shared hosting |
| Tier upgrade | `.env` change only | No code changes for file→Redis migration |

---

## Quick References

- `config/cache.php` — cache store definitions and per-store configuration
- `config/cache-keys.php` — centralized cache key registry (26+ keys)
- `config/session.php` — session driver, lifetime, cookie settings
- `config/settings.php` — theme cache invalidation key list
- `config/database.php` — Redis connection settings under the `redis` key
- `app/Core/Actions/BaseReadAction.php` — `remember()`, `forget()`, `cacheKey()` helpers
- `app/Settings/Services/Settings.php` — settings caching layer (heaviest cache user)
- `app/Settings/Observers/SettingObserver.php` — auto-invalidation on Setting model events
- `app/Settings/Models/Setting.php` — registers `SettingObserver`
- `app/Settings/Support/Brand.php` — brand value caching with auto-invalidation
- `app/Settings/Theme/Support/Theme.php` — CSS variable caching
- `app/Settings/Locale/Http/Middleware/SetLocaleMiddleware.php` — locale persistence via session
- `app/Core/Http/Middleware/LogContext.php` — request context injection (uses session for user)
- `docs/architecture/cache-pattern.md` — cache philosophy, anti-patterns, warming
- `docs/infrastructure/cache.md` — cache driver strategy, invalidation, Redis config
- `docs/infrastructure/session.md` — session configuration and security
- `docs/specs/core-infra.md` — foundation spec (§6.6, §6.7, §DD-3, §DD-4, §DD-5)
