# Login & Dashboard — Authentication Flow, Throttling & Role-Based Dashboards

> **Last updated:** 2026-07-21 **Changes:** feat — initial spec covering login, logout, dual
> throttling, session lifecycle, role-based dashboard routing, and dashboard data aggregation

## Description

Complete specification of Internara's login authentication flow, logout process, and role-based
dashboard system. Defines the dual-throttling mechanism (HTTP middleware + cache-based lockout),
credential validation, session regeneration, and the four role-specific dashboards (Admin, Student,
Teacher, Supervisor) with their cached data aggregation and cache invalidation strategies.

---

## 1. Problem Statements

### PS-1 — Credential Stuffing & Brute Force

Schools are high-value targets for credential stuffing attacks — students and teachers reuse
passwords across services. Without rate limiting, an attacker can attempt thousands of
password combinations per minute. The system must throttle both at the HTTP layer and at the
application layer to prevent automated attacks.

### PS-2 — Session Fixation on Shared Computers

Schools operate on shared computers (labs, teacher offices). If a session ID is not regenerated
on login, an attacker who pre-set a session ID in the browser can hijack the authenticated
session. Session regeneration is mandatory on every authentication state change.

### PS-3 — Role-Appropriate Dashboard Views

With 5 distinct roles (super_admin, admin, student, teacher, supervisor), a single dashboard
layout cannot serve all users effectively. Admins need system-wide statistics, students need
academic progress, teachers need supervision queues, and supervisors need intern activity.
Each role requires a tailored data aggregation and UI.

### PS-4 — Dashboard Data Freshness vs Performance

Dashboard statistics (25+ data points for admin, 5-10 for others) involve expensive aggregation
queries. Without caching, every dashboard load triggers multiple database queries. With caching,
stale data may be shown. The system must balance freshness (< 5 minutes) with performance (< 200ms
dashboard load).

### PS-5 — Account Lockout Recovery

When a user is locked out after 10 failed attempts, they need clear feedback about when they can
retry. Without exponential backoff, an attacker can simply wait the minimum lockout period and
retry. With aggressive backoff, legitimate users may be locked out for hours.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide dual throttling: HTTP middleware (5 attempts/60s) + cache lockout (10 failures → exponential backoff) |
| G2  | Support both email and username login identifiers |
| G3  | Regenerate session ID on every authentication state change |
| G4  | Route users to role-appropriate dashboards automatically |
| G5  | Cache dashboard statistics with 5-minute TTL |
| G6  | Invalidate dashboard cache on relevant entity changes (departments, academic years) |
| G7  | Display account status before login attempt (locked, suspended, setup required) |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Multi-factor authentication (MFA) |
| NG2  | Social login (Google, GitHub) |
| NG3  | Passwordless login (magic links, WebAuthn) |
| NG4  | Real-time dashboard updates (WebSocket/SSE) |
| NG5  | Dashboard customization by end users |

---

## 3. User Stories / Use Cases

### UC-1 — Student Logs In with Email

**Actor:** Student
**Preconditions:** Student account exists, status is `activated` or `verified`
**Flow:**
1. Student navigates to `/login`
2. Enters email and password
3. `LoginAction` resolves identifier as email (FILTER_VALIDATE_EMAIL)
4. Checks lockout status via cache key `auth.login.lockout:{hash}`
5. Looks up user by email, checks account status via `asApprentice()`
6. Calls `Auth::attempt()` with credentials
7. On success: clears failed attempts, regenerates session, dispatches `LoginSucceeded`
8. `SendRoleWelcomeNotification` sends first-login welcome by role
**Postconditions:** Student is authenticated, redirected to `/student/dashboard`

### UC-2 — Account Locked After 10 Failed Attempts

**Actor:** Attacker (or student who forgot password)
**Preconditions:** No prior lockout for this identifier
**Flow:**
1. 10 consecutive failed login attempts for the same identifier
2. Each attempt increments `auth.login.attempts:{hash}` (24h TTL)
3. On 10th failure: lockout key `auth.login.lockout:{hash}` set to `10 * 2^(10-10) = 10 seconds`
4. 11th failure: lockout duration = `10 * 2^(11-10) = 20 seconds`
5. 15th failure: lockout duration = `10 * 2^(15-10) = 320 seconds` (~5 min)
6. After lockout expires: attempts counter still holds, next failure extends lockout
7. On successful login: both attempts and lockout counters cleared
**Postconditions:** Attacker is rate-limited; legitimate user can retry after lockout expires

### UC-3 — Admin Dashboard Loads with 25+ Stats

**Actor:** Admin / Super Admin
**Preconditions:** Admin is authenticated with `admin` or `super_admin` role
**Flow:**
1. Admin navigates to `/dashboard`
2. `DashboardController` → `DashboardService` detects role, redirects to `/admin/dashboard`
3. `AdminDashboard` Livewire component mounts
4. Calls `ReadAdminDashboardAction::execute()` with `remember()` (300s TTL)
5. Returns: people counts, internship stats, registration/placement data, attendance, logbooks,
   certificates, company count, throughput metrics, 5 readiness checks (DB, mail, cache, queue, storage)
**Postconditions:** Dashboard renders in < 200ms (cache hit) or < 2s (cache miss with fresh data)

### UC-4 — Dashboard Cache Invalidated on Department Change

**Actor:** Admin creating/updating/deleting a department
**Preconditions:** Admin has department management permission
**Flow:**
1. Admin creates/updates/deletes a department
2. Department CRUD Action dispatches `DepartmentCreated`/`DepartmentUpdated`/`DepartmentDeleted` event
3. `ClearDashboardCacheOnDepartmentChange` listener handles the event
4. Listener calls `Cache::forget()` on affected dashboard cache keys
5. Next dashboard load triggers fresh data aggregation
**Postconditions:** Dashboard statistics reflect the department change within one request cycle

### UC-5 — User Logs Out

**Actor:** Any authenticated user
**Preconditions:** User is logged in
**Flow:**
1. User clicks logout button
2. `POST /logout` → `AuthController::logout()`
3. `auth()->logout()` — clears authentication state
4. `session()->invalidate()` — destroys session data
5. `session()->regenerateToken()` — regenerates CSRF token
6. Redirect to `/login`
**Postconditions:** Session destroyed, CSRF token rotated, user redirected to login

---

## 4. Functional Requirements

### Login — Authentication

| ID   | Requirement |
| ---- | ----------- |
| FR-LI1 | System must accept both email and username as login identifiers |
| FR-LI2 | `LoginAction` must detect identifier type via `FILTER_VALIDATE_EMAIL` |
| FR-LI3 | System must look up user by the detected field (`email` or `username`) |
| FR-LI4 | System must check account status via `$user->asApprentice()->status()->allowsLogin()` |
| FR-LI5 | System must reject login for `PROVISIONED`, `SUSPENDED`, and `ARCHIVED` statuses |
| FR-LI6 | System must check `$user->asApprentice()->isLocked()` before credential validation |
| FR-LI7 | System must check `$user->asApprentice()->requiresSetup()` and reject if true |
| FR-LI8 | Successful login must call `session()->regenerate()` to prevent session fixation |

### Login — Throttling

| ID   | Requirement |
| ---- | ----------- |
| FR-LT1 | `AuthThrottleMiddleware` must enforce 5 login attempts per 60 seconds per IP |
| FR-LT2 | `LoginAction` must enforce cache-based lockout: 10 failures → exponential backoff |
| FR-LT3 | Lockout duration formula: `10 * 2^(attempts - 10)` seconds |
| FR-LT4 | Failed attempt counter stored in `auth.login.attempts:{hash}` with 24h TTL |
| FR-LT5 | Lockout key stored in `auth.login.lockout:{hash}` with duration-based TTL |
| FR-LT6 | Successful login must clear both attempts and lockout counters |
| FR-LT7 | Lockout check must happen BEFORE user lookup (prevent user enumeration timing) |

### Login — Events & Logging

| ID   | Requirement |
| ---- | ----------- |
| FR-LE1 | Failed login must dispatch `LoginFailed` event with identifier and reason |
| FR-LE2 | `LogLoginFailed` listener must log via SmartLogger with PII masking |
| FR-LE3 | Successful login must dispatch `LoginSucceeded` event with user model |
| FR-LE4 | `SendRoleWelcomeNotification` must send first-login welcome notification by role |
| FR-LE5 | Login success must be logged via `$this->log()` with user as subject |

### Login — Form & UI

| ID   | Requirement |
| ---- | ----------- |
| FR-LF1 | `Login` Livewire component must use `LoginForm` Form Object for validation |
| FR-LF2 | `LoginForm` must validate: identifier required, password required |
| FR-LF3 | Login page must be accessible at `/login` with `guest` middleware |
| FR-LF4 | Login page must display flash messages for errors and lockout notifications |

### Logout

| ID   | Requirement |
| ---- | ----------- |
| FR-LO1 | `POST /logout` must call `auth()->logout()` |
| FR-LO2 | Must call `session()->invalidate()` to destroy session data |
| FR-LO3 | Must call `session()->regenerateToken()` to rotate CSRF token |
| FR-LO4 | Must redirect to `/login` after logout |

### Dashboard — Routing

| ID   | Requirement |
| ---- | ----------- |
| FR-DR1 | `GET /dashboard` must route to role-appropriate dashboard via `DashboardService` |
| FR-DR2 | `super_admin`/`admin` → `/admin/dashboard` (AdminDashboard) |
| FR-DR3 | `student` → `/student/dashboard` (StudentDashboard) |
| FR-DR4 | `teacher` → `/teacher/dashboard` (TeacherDashboard) |
| FR-DR5 | `supervisor` → `/supervisor/dashboard` (SupervisorDashboard) |
| FR-DR6 | Unknown role → `/my-dashboard` (UserDashboard fallback) |

### Dashboard — Data Aggregation

| ID   | Requirement |
| ---- | ----------- |
| FR-DD1 | `ReadAdminDashboardAction` must return 25+ stats: people, internships, registrations, placements, attendance, logbooks, certificates, companies, throughput, audit, 5 readiness checks |
| FR-DD2 | `ReadStudentDashboardAction` must return: registration, journals (total/verified), attendance %, assignments (submitted/total), handbook (read/total) |
| FR-DD3 | `ReadTeacherDashboardAction` must return: supervised students, pending journals, active companies, ungraded submissions, supervision logs, unresolved incidents |
| FR-DD4 | `ReadSupervisorDashboardAction` must return: active interns, pending evaluations, verified/pending journals, pending attendance |
| FR-DD5 | All dashboard data must be cached via `remember()` with 300s TTL |

### Dashboard — Cache Invalidation

| ID   | Requirement |
| ---- | ----------- |
| FR-DI1 | `ClearDashboardCacheOnDepartmentChange` must listen to Department CRUD events |
| FR-DI2 | `ClearDashboardCacheOnYearChange` must listen to AcademicYear CRUD events |
| FR-DI3 | Cache invalidation must clear affected dashboard cache keys on event dispatch |

### Dashboard — UI

| ID   | Requirement |
| ---- | ----------- |
| FR-DU1 | All dashboards must extend `UserDashboard` base Livewire component |
| FR-DU2 | `UserDashboard` must provide `getUser()` and `getRecentActivities()` |
| FR-DU3 | Each dashboard must enforce role checks in `boot()` via `abort_unless()` |
| FR-DU4 | All dashboards must use `#[Layout('core::layouts.app')]` |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-P1 | Login action must complete in < 500ms (cache miss) or < 100ms (cache hit) |
| NFR-P2 | Dashboard load must complete in < 200ms (cache hit) or < 2s (cache miss) |
| NFR-P3 | Lockout check must happen before user lookup to prevent timing-based user enumeration |
| NFR-S1 | Session ID must be regenerated on every login and logout |
| NFR-S2 | CSRF token must be regenerated on logout |
| NFR-S3 | Login failures must not reveal whether the identifier exists (generic error message) |
| NFR-S4 | Lockout duration must increase exponentially to deter automated attacks |
| NFR-R1 | Dashboard must function correctly if any individual stat query fails |
| NFR-R2 | Login must gracefully handle database unavailability (reject, don't crash) |
| NFR-U1 | Lockout message must include the number of seconds to wait |
| NFR-U2 | Dashboard must display role-appropriate content without manual selection |
| NFR-M1 | Dashboard data aggregation must be delegated to Read Actions, not inline in Livewire |

---

## 6. API / Data Contracts

### 6.1 LoginData DTO

```php
// app/Auth/Login/Data/LoginData.php
final readonly class LoginData extends BaseData
{
    public function __construct(
        public string $identifier,
        public string $password,
        public bool $remember = false,
    ) {}
}
```

### 6.2 LoginAction

```php
// app/Auth/Login/Actions/LoginAction.php
final class LoginAction extends BaseCommandAction
{
    public function execute(string $identifier, string $password, bool $remember = false): Authenticatable;
    // Pipeline: DTO → lockout check → user lookup → account status → Auth::attempt → session regenerate → events
    // Lockout: 10 * 2^(attempts - 10) seconds exponential backoff
    // Cache keys: auth.login.lockout:{crc32b_hash}, auth.login.attempts:{crc32b_hash}
}
```

### 6.3 AuthThrottleMiddleware

```php
// app/Auth/Login/Http/Middleware/AuthThrottleMiddleware.php
// HTTP layer: 5 login attempts per 60 seconds per IP
// General auth: 30 attempts per 60 seconds
// Config: config('auth.throttle.login_max_attempts'), config('auth.throttle.login_decay_seconds')
```

### 6.4 AccountStatus State Machine

```
PROVISIONED → ACTIVATED, SUSPENDED
ACTIVATED → VERIFIED, SUSPENDED, ARCHIVED
VERIFIED → RESTRICTED, SUSPENDED, ARCHIVED, INACTIVE
PROTECTED → (terminal)
RESTRICTED → VERIFIED, SUSPENDED, ARCHIVED
SUSPENDED → ACTIVATED, VERIFIED, ARCHIVED
INACTIVE → VERIFIED, ARCHIVED, SUSPENDED
ARCHIVED → (terminal)

allowsLogin(): ACTIVATED, VERIFIED, PROTECTED, RESTRICTED, INACTIVE
isTerminal(): PROTECTED, ARCHIVED
```

### 6.5 Dashboard Routing

```php
// app/User/Services/DashboardService.php
// Role priority: super_admin/admin → sysadmin.dashboard
//               student → student.dashboard
//               teacher → teacher.dashboard
//               supervisor → supervisor.dashboard
//               fallback → user.dashboard
```

### 6.6 Dashboard Data Contracts

```php
// ReadAdminDashboardAction returns:
[
    'stats' => [
        'total_users' => int, 'total_admins' => int, 'total_students' => int,
        'total_teachers' => int, 'total_supervisors' => int,
        'active_internships' => int, 'total_registrations' => int,
        'total_placements' => int, 'attendance_rate' => float,
        'total_logbooks' => int, 'verified_logbooks' => int,
        'total_certificates' => int, 'total_companies' => int,
        'throughput' => [...], 'audit' => [...],
    ],
    'readiness' => [
        'database' => bool, 'mail' => bool, 'cache' => bool,
        'queue' => bool, 'storage' => bool,
    ],
]

// ReadStudentDashboardAction returns:
['registration' => [...], 'journals' => [...], 'attendance' => float, 'assignments' => [...], 'handbook' => [...]]

// ReadTeacherDashboardAction returns:
['supervised_students' => int, 'pending_journals' => int, 'active_companies' => int, 'ungraded_submissions' => int, 'supervision_logs' => int, 'unresolved_incidents' => int]

// ReadSupervisorDashboardAction returns:
['active_interns' => int, 'pending_evaluations' => int, 'verified_journals' => int, 'pending_journals' => int, 'pending_attendance' => int]
```

### 6.7 Routes

```php
// routes/web/auth.php
Route::get('/login', Login::class)->middleware(['guest', 'auth.throttle'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// routes/web/user.php
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');
Route::get('/admin/dashboard', AdminDashboard::class)->middleware(['auth', 'role:super_admin|admin'])->name('sysadmin.dashboard');
Route::get('/student/dashboard', StudentDashboard::class)->middleware(['auth', 'role:student'])->name('student.dashboard');
Route::get('/teacher/dashboard', TeacherDashboard::class)->middleware(['auth', 'role:teacher'])->name('teacher.dashboard');
Route::get('/supervisor/dashboard', SupervisorDashboard::class)->middleware(['auth', 'role:supervisor'])->name('supervisor.dashboard');
Route::get('/my-dashboard', UserDashboard::class)->middleware('auth')->name('user.dashboard');
```

---

## 7. Design Decisions

### DD-1 — Dual Throttling (HTTP + Cache)

**Decision:** Two independent throttling layers: `AuthThrottleMiddleware` (HTTP, IP-based) and
`LoginAction` cache lockout (application, identifier-based).
**Rationale:** HTTP throttling prevents volumetric attacks (thousands of requests from one IP).
Cache lockout prevents credential stuffing across IPs (same username targeted from many IPs).
Neither alone is sufficient.
**Trade-off:** Two independent systems to maintain. Mitigated by clear separation: middleware
handles HTTP-level, Action handles application-level.

### DD-2 — Exponential Backoff for Lockout

**Decision:** Lockout duration increases exponentially: `10 * 2^(attempts - 10)` seconds.
**Rationale:** Linear backoff (e.g., fixed 5 minutes) allows attackers to simply wait and retry.
Exponential backoff makes repeated attacks progressively more expensive. After 15 attempts, the
lockout is ~5 minutes; after 20 attempts, ~50 minutes.
**Trade-off:** Legitimate users who forget their password and retry repeatedly face increasing
delays. Mitigated by clear lockout messages showing the wait time.

### DD-3 — Identifier Hash for Cache Keys

**Decision:** Cache keys use `crc32b` hash of the identifier, not the raw identifier.
**Rationale:** Prevents cache key injection (e.g., special characters in username) and keeps
cache keys a fixed length. The hash is one-way — an attacker who gains cache access cannot
recover the original identifier.
**Trade-off:** CRC32B is not collision-resistant, but for rate-limiting purposes, a collision
means two different identifiers share a lockout — acceptable false positive.

### DD-4 — Dashboard Routing via Service, Not Middleware

**Decision:** Role-based dashboard routing uses `DashboardService::getDashboardForUser()`, not
role-specific middleware.
**Rationale:** A service allows priority-based role selection (super_admin takes precedence over
admin) and fallback logic. Middleware would require multiple route definitions with complex
role-expression logic. The service approach is more readable and testable.
**Trade-off:** Extra service class. Mitigated by the service being 47 lines with a single method.

### DD-5 — Dashboard Cache with 5-Minute TTL

**Decision:** Dashboard statistics cached via `remember()` with 300-second TTL.
**Rationale:** Dashboard data is read-heavy and write-light. Real-time accuracy is not required —
admins check dashboards periodically, not continuously. 5-minute TTL balances freshness with
performance. Cache invalidation events (department/year changes) ensure immediate updates for
structural changes.
**Trade-off:** Up to 5 minutes of staleness for non-structural data changes (e.g., new logbook
submission). Acceptable for dashboard use cases.

### DD-6 — Base Dashboard Component with Role Specialization

**Decision:** `UserDashboard` is a base Livewire component; role-specific dashboards extend it.
**Rationale:** Common functionality (user retrieval, recent activities, layout) is shared via
inheritance. Each role dashboard adds its own data aggregation and role gating. This follows the
template method pattern and avoids code duplication across 4 dashboards.
**Trade-off:** PHP single inheritance limits extension. Mitigated by the base being lightweight
(31 lines) and role dashboards being self-contained.

---

## 8. Success Metrics

### 8.1 Login Security

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Throttle enforcement | 5 attempts/60s HTTP + 10 failures lockout | `AuthThrottleMiddleware` + `LoginAction` |
| Lockout exponential | Duration doubles after 10 failures | Cache key TTL inspection |
| Session regeneration | On every login/logout | `session()->regenerate()` in LoginAction + AuthController |
| User enumeration prevention | Lockout check before user lookup | `LoginAction::checkLockout()` before `User::where()` |

### 8.2 Dashboard Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Cache hit load | < 200ms | `remember()` TTL 300s |
| Cache miss load | < 2s | Fresh aggregation queries |
| Invalidation accuracy | < 1s stale after entity change | Event listener fires on CRUD |
| Stat coverage | 25+ admin, 5-10 others | Read Action return arrays |

### 8.3 User Experience

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Lockout feedback | Shows seconds to wait | `auth.throttle` translation key |
| Role routing | Automatic, no manual selection | `DashboardService` priority logic |
| Logout completeness | Session + CSRF invalidated | `auth()->logout()` + `invalidate()` + `regenerateToken()` |

---

## Quick References

- `app/Auth/Login/Actions/LoginAction.php` — login pipeline with dual throttling
- `app/Auth/Login/Data/LoginData.php` — login DTO
- `app/Auth/Login/Events/LoginFailed.php` — failed login event
- `app/Auth/Login/Events/LoginSucceeded.php` — successful login event
- `app/Auth/Login/Http/Middleware/AuthThrottleMiddleware.php` — HTTP rate limiting
- `app/Auth/Login/Listeners/LogLoginFailed.php` — SmartLogger integration
- `app/Auth/Login/Listeners/SendRoleWelcomeNotification.php` — first-login welcome
- `app/Auth/Login/Livewire/Login.php` — login Livewire component
- `app/Auth/Login/Livewire/Forms/LoginForm.php` — login form validation
- `app/User/Http/Controllers/AuthController.php` — logout handler
- `app/User/Http/Controllers/DashboardController.php` — dashboard routing
- `app/User/Services/DashboardService.php` — role-based dashboard resolution
- `app/User/Dashboard/Livewire/UserDashboard.php` — base dashboard component
- `app/User/Dashboard/Livewire/AdminDashboard.php` — admin dashboard (25+ stats)
- `app/User/Dashboard/Livewire/StudentDashboard.php` — student dashboard
- `app/User/Dashboard/Livewire/TeacherDashboard.php` — teacher dashboard
- `app/User/Dashboard/Livewire/SupervisorDashboard.php` — supervisor dashboard
- `app/SysAdmin/Actions/ReadAdminDashboardAction.php` — admin data aggregation
- `app/User/Dashboard/Actions/ReadStudentDashboardAction.php` — student data aggregation
- `app/User/Dashboard/Actions/ReadTeacherDashboardAction.php` — teacher data aggregation
- `app/User/Dashboard/Actions/ReadSupervisorDashboardAction.php` — supervisor data aggregation
- `app/User/Dashboard/Listeners/ClearDashboardCacheOnDepartmentChange.php` — cache invalidation
- `app/User/Dashboard/Listeners/ClearDashboardCacheOnYearChange.php` — cache invalidation
- `app/User/Enums/AccountStatus.php` — 8-state status machine with transition guards
- `config/auth.php` — throttle configuration
- `routes/web/auth.php` — login/logout routes
- `routes/web/user.php` — dashboard routes
- `docs/modules/auth.md` — Auth module overview
- `docs/modules/auth-reference.md` — Auth module technical reference
