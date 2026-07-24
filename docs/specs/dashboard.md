# Dashboard — Role-Based Data Aggregation & Caching

> **Last updated:** 2026-07-22 **Changes:** feat — split from login-and-dashboard.md; dashboard
> routing, role-specific data aggregation, caching, cache invalidation, UI components

## Description

Specification of Internara's role-based dashboard system: automatic routing to role-appropriate
dashboards, four role-specific data aggregation Actions with cached results, event-driven cache
invalidation, and a base Livewire component hierarchy. Login and authentication throttling are
covered in [authentication.md](authentication.md).

---

## 1. Problem Statements

### PS-1 — Role-Appropriate Dashboard Views

With 5 distinct roles (`super_admin`, `admin`, `student`, `teacher`, `supervisor`), a single
dashboard layout cannot serve all users effectively. Admins need system-wide statistics (25+
data points across people, internships, registrations, placements, attendance, logbooks,
certificates, and companies). Students need academic progress. Teachers need supervision
queues. Supervisors need intern activity. Each role requires dedicated data aggregation and UI.

### PS-2 — Dashboard Data Freshness vs Performance

Dashboard statistics involve expensive aggregation queries — the admin dashboard alone executes
15+ queries across 8 models (`User`, `Internship`, `Registration`, `Placement`, `Attendance`,
`Logbook`, `Certificate`, `Company`). Without caching, every load triggers these queries (2+ seconds).
With caching, stale data may be shown. The system must balance freshness (under 5 minutes) with
performance (under 200ms cache hit, under 2s cache miss).

### PS-3 — Expensive Aggregation Queries

Role-specific queries involve `whereHas` subqueries through pivot tables (e.g., registration →
mentors → user) and multi-table aggregations. The teacher dashboard executes 6 complex queries
with nested relationship filters. Running these on every page load is unacceptable for shared
school computers with limited bandwidth.

### PS-4 — Cache Invalidation on Structural Changes

Dashboard statistics depend on structural entities (departments, academic years) that change
infrequently but affect every load. Without targeted invalidation, users see outdated counts
for up to 5 minutes after a structural change.

### PS-5 — Cross-Role Proxy Viewing

Teachers sometimes act as supervisors for specific interns. Without proxy-aware routing, the
proxy user sees their own dashboard instead of the target role's dashboard.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Route users to role-appropriate dashboards automatically based on highest-priority role |
| G2  | Provide 4 role-specific dashboards: Admin, Student, Teacher, Supervisor |
| G3  | Cache all dashboard statistics with 300-second (5-minute) TTL |
| G4  | Invalidate dashboard cache synchronously on department and academic year changes |
| G5  | Provide a `UserDashboard` base Livewire component with shared functionality |
| G6  | Support proxy-aware routing for teachers acting as supervisors |
| G7  | Provide system readiness checks on the admin dashboard (DB, mail, cache, queue, storage) |
| G8  | Delegate all dashboard data aggregation to Read Actions, never inline in Livewire |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Real-time dashboard updates via WebSocket or Server-Sent Events |
| NG2  | User-customizable dashboard widgets or layout |
| NG3  | Dashboard export (PDF, CSV) — covered by the Reports module |
| NG4  | Cross-tenant dashboard aggregation (single-tenant system) |
| NG5  | Mobile-specific dashboard layouts |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Dashboard Loads with 25+ Stats

**Actor:** Admin or Super Admin
**Preconditions:** User authenticated with `admin` or `super_admin` role
**Flow:**
1. Admin navigates to `/dashboard`
2. `DashboardController` → `DashboardService::getDashboardForUser()` resolves to `sysadmin.dashboard`
3. Controller redirects to `/admin/dashboard`
4. `AdminDashboard` Livewire component mounts
5. `ReadAdminDashboardAction::execute()` reads cache key `sysadmin.dashboard.stats` (300s TTL)
6. On cache miss: 15+ queries across 8 models, result stored in cache
7. Returns: people counts, internship stats, registration pipeline, placement data, attendance, logbooks, certificates, companies, throughput, audit entries, and 5 readiness checks (DB, mail, cache, queue, storage)
**Postconditions:** Dashboard renders in < 200ms (cache hit) or < 2s (cache miss)

### UC-2 — Student Dashboard Loads with Academic Progress

**Actor:** Student
**Preconditions:** User authenticated with `student` role
**Flow:**
1. Student navigates to `/dashboard`, `DashboardService` redirects to `/student/dashboard`
2. `StudentDashboard` mounts with role gate in `boot()`
3. `ReadStudentDashboardAction::execute($userId)` reads cache key `dashboard.student.{userId}` (300s TTL)
4. Queries registration, logbooks, attendance, assignments, handbooks
5. Returns: active registration, journal counts, attendance %, assignment counts, handbook counts
**Postconditions:** Dashboard shows student's academic progress and submission status

### UC-3 — Teacher Dashboard Loads with Supervision Queue

**Actor:** Teacher
**Preconditions:** User authenticated with `teacher` role
**Flow:**
1. Teacher navigates to `/dashboard`, redirected to `/teacher/dashboard`
2. `TeacherDashboard` mounts with role gate (allows `teacher` or `admin`)
3. `ReadTeacherDashboardAction::execute()` reads cache key `sysadmin.dashboard.stats.teacher.{userId}` (300s TTL)
4. Queries supervised students, pending journals, active companies, ungraded submissions, supervision logs, unresolved incidents
**Postconditions:** Dashboard shows teacher's supervision workload

### UC-4 — Supervisor Dashboard Loads with Intern Activity

**Actor:** Supervisor
**Preconditions:** User authenticated with `supervisor` role
**Flow:**
1. Supervisor navigates to `/dashboard`, redirected to `/supervisor/dashboard`
2. `SupervisorDashboard` mounts with role gate (allows `supervisor`, `admin`, or `teacher`)
3. `ReadSupervisorDashboardAction::execute()` reads cache key `sysadmin.dashboard.stats.supervisor.{userId}` (300s TTL)
4. Queries active interns, pending evaluations, verified/pending journals, pending attendance
**Postconditions:** Dashboard shows intern activity and pending verification tasks

### UC-5 — Dashboard Cache Invalidated on Department Change

**Actor:** Admin creating/updating/deleting a department
**Preconditions:** Admin has department management permission
**Flow:**
1. Admin creates, updates, or deletes a department
2. CRUD Action dispatches `DepartmentCreated`/`DepartmentUpdated`/`DepartmentDeleted` event
3. `ClearDashboardCacheOnDepartmentChange` listener calls `Cache::forget()` on `admin_dashboard_stats` key
4. Next dashboard load triggers fresh data aggregation
**Postconditions:** Dashboard statistics reflect the change within one request cycle

### UC-6 — Dashboard Cache Invalidated on Academic Year Change

**Actor:** Admin managing academic years
**Preconditions:** Admin has academic year management permission
**Flow:**
1. Admin creates, activates, updates, or deletes an academic year
2. CRUD Action dispatches `AcademicYearCreated`/`AcademicYearActivated`/`AcademicYearUpdated`/`AcademicYearDeleted`
3. `ClearDashboardCacheOnYearChange` listener calls `Cache::forget()` on `admin_dashboard_stats` key
4. Next dashboard load triggers fresh data aggregation
**Postconditions:** Dashboard statistics reflect the year change within one request cycle

### UC-7 — Unknown Role Falls Back to Generic Dashboard

**Actor:** User with unrecognized or missing role
**Preconditions:** User authenticated but role does not match any known dashboard
**Flow:**
1. User navigates to `/dashboard`
2. `DashboardService::getDashboardForUser()` falls through all `match` cases to `default`
3. Returns `user.dashboard` route, controller redirects to `/my-dashboard`
4. `UserDashboard` base component renders with user info and recent activities
**Postconditions:** User sees a generic dashboard with basic profile info and recent activity log

---

## 4. Functional Requirements

### Dashboard — Routing

| ID   | Requirement |
| ---- | ----------- |
| FR-DR1 | `GET /dashboard` must invoke `DashboardController` which calls `DashboardService::getDashboardForUser()` and redirects to the resolved route |
| FR-DR2 | `super_admin`/`admin` → route `sysadmin.dashboard` (`/admin/dashboard`) |
| FR-DR3 | `student` → route `student.dashboard` (`/student/dashboard`) |
| FR-DR4 | `teacher` → route `teacher.dashboard` (`/teacher/dashboard`) |
| FR-DR5 | `supervisor` → route `supervisor.dashboard` (`/supervisor/dashboard`) |
| FR-DR6 | Unrecognized role → route `user.dashboard` (`/my-dashboard`) |
| FR-DR7 | Role priority: `super_admin`/`admin` first, then `student`, `teacher`, `supervisor`, then default — via `match(true)` with ordered cases |
| FR-DR8 | `DashboardService::getProxyDashboardForUser()` must return `supervisor.dashboard` for `teacher` role, `null` otherwise |

### Dashboard — Data Aggregation

| ID   | Requirement |
| ---- | ----------- |
| FR-DD1 | `ReadAdminDashboardAction` must extend `BaseReadAction`, accept no parameters, and return 28 keys: `totalStudents`, `totalTeachers`, `totalSupervisors`, `totalMentors`, `totalCompanies`, `totalPartnerships`, `totalDepartments`, `activeInternships`, `allInternships`, `registrationsPending`, `registrationsActive`, `registrationsCompleted`, `registrationsTotal`, `placementTotal`, `placementFilled`, `placementCapacity`, `placementsByInternship`, `attendanceVerified`, `attendanceUnverified`, `logbookVerified`, `logbookPending`, `certificatesIssued`, `certificatesRevoked`, `certificatesTotal`, `companiesActive`, `placementRate`, `totalAuditEntries`, `failedLogins7d`, `activeUsersToday` |
| FR-DD2 | `ReadStudentDashboardAction` must extend `BaseReadAction`, accept `string $userId`, and return 8 keys: `registration` (?Registration), `totalJournals`, `verifiedJournals`, `attendancePercent` (float), `assignmentSubmittedCount`, `assignmentTotalCount`, `handbookReadCount`, `handbookTotalCount` |
| FR-DD3 | `ReadTeacherDashboardAction` must extend `BaseReadAction`, accept no parameters (reads `Auth::id()`), and return 6 keys: `supervisedStudents`, `pendingJournals`, `activeCompanies`, `ungradedSubmissions`, `supervisionLogsCount`, `unresolvedIncidents` |
| FR-DD4 | `ReadSupervisorDashboardAction` must extend `BaseReadAction`, accept no parameters (reads `Auth::id()`), and return 5 keys: `activeInterns`, `pendingEvaluations`, `verifiedJournals`, `pendingJournals`, `pendingAttendance` |
| FR-DD5 | `ReadStudentDashboardAction` must throw `RejectedException` if user not found |
| FR-DD6 | `ReadStudentDashboardAction` must handle null registration gracefully (default counts to 0, attendance to 100.0) |
| FR-DD7 | `ReadTeacherDashboardAction` and `ReadSupervisorDashboardAction` must scope queries to the authenticated user's supervised registrations via `whereHas('mentors', fn ($q) => $q->where('user_id', $userId))` |

### Dashboard — Caching

| ID   | Requirement |
| ---- | ----------- |
| FR-DC1 | All dashboard data must be cached via `Cache::remember()` with 300-second TTL |
| FR-DC2 | Admin cache key: `config('cache-keys.admin_dashboard_stats')` → `sysadmin.dashboard.stats` |
| FR-DC3 | Student cache key: `config('cache-keys.dashboard_student') . $userId` → `dashboard.student.{userId}` |
| FR-DC4 | Teacher cache key: `config('cache-keys.admin_dashboard_stats') . 'teacher.' . $userId` → `sysadmin.dashboard.stats.teacher.{userId}` |
| FR-DC5 | Supervisor cache key: `config('cache-keys.admin_dashboard_stats') . 'supervisor.' . $userId` → `sysadmin.dashboard.stats.supervisor.{userId}` |
| FR-DC6 | Cache keys must be declared in `config/cache-keys.php` — no inline key strings |

### Dashboard — Cache Invalidation

| ID   | Requirement |
| ---- | ----------- |
| FR-DI1 | `ClearDashboardCacheOnDepartmentChange` must listen to `DepartmentCreated`, `DepartmentUpdated`, `DepartmentDeleted` and call `Cache::forget()` on `admin_dashboard_stats` key |
| FR-DI2 | `ClearDashboardCacheOnYearChange` must listen to `AcademicYearCreated`, `AcademicYearActivated`, `AcademicYearUpdated`, `AcademicYearDeleted` and call `Cache::forget()` on `admin_dashboard_stats` key |
| FR-DI3 | Cache invalidation listeners must execute synchronously (not queued) |

### Dashboard — UI Components

| ID   | Requirement |
| ---- | ----------- |
| FR-DU1 | `UserDashboard` must extend `Component` with `#[Layout('core::layouts.app')]`, provide `getUser()` (returns `?User`) and `getRecentActivities()` (5 most recent `ActivityLog` entries), render `user.dashboard.index` |
| FR-DU2 | `AdminDashboard` must extend `UserDashboard`, call `ReadAdminDashboardAction::execute()` in `mount()`, run 5 readiness checks, render `user.dashboard.admin` with `$stats` and `$readiness` |
| FR-DU3 | `StudentDashboard` must extend `UserDashboard`, enforce `abort_unless(hasRole('student'), 403)` in `boot()`, call `ReadStudentDashboardAction::execute($userId)` in `mount()`, render `user.dashboard.student` |
| FR-DU4 | `TeacherDashboard` must extend `UserDashboard`, enforce role gate in `boot()` (allows `teacher` or `admin`), call `ReadTeacherDashboardAction::execute()` in `mount()`, render `user.dashboard.teacher` |
| FR-DU5 | `SupervisorDashboard` must extend `UserDashboard`, enforce role gate in `boot()` (allows `supervisor`, `admin`, or `teacher`), call `ReadSupervisorDashboardAction::execute()` in `mount()`, render `user.dashboard.supervisor` |
| FR-DU6 | `AdminDashboard` readiness checks: DB (`DB::connection()->getPdo()`), mail (host not empty/localhost), cache (write-read roundtrip), queue (sync = ready, otherwise DB), storage (symlink + writable logs/cache dirs) |
| FR-DU7 | Each dashboard must use constructor injection for its Read Action in `mount()` — no service locator |

### Dashboard — Routes

| ID   | Requirement |
| ---- | ----------- |
| FR-RT1 | `GET /dashboard` — `auth` middleware, named `dashboard` |
| FR-RT2 | `GET /admin/dashboard` — `auth` + `role:super_admin\|admin`, named `sysadmin.dashboard` |
| FR-RT3 | `GET /student/dashboard` — `auth` + `role:student`, named `student.dashboard` |
| FR-RT4 | `GET /teacher/dashboard` — `auth` + `role:teacher`, named `teacher.dashboard` |
| FR-RT5 | `GET /supervisor/dashboard` — `auth` + `role:supervisor`, named `supervisor.dashboard` |
| FR-RT6 | `GET /my-dashboard` — `auth`, named `user.dashboard` |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-P1 | Dashboard load must complete in < 200ms on cache hit |
| NFR-P2 | Dashboard load must complete in < 2s on cache miss |
| NFR-P3 | Cache hit rate should exceed 90% under normal usage (5-min TTL covers repeated visits) |
| NFR-P4 | Student dashboard cache must be per-user (key includes `$userId`) to prevent cross-user data leakage |
| NFR-R1 | Dashboard must function correctly if any individual stat query fails — remaining stats render |
| NFR-R2 | `ReadStudentDashboardAction` must handle missing user via `RejectedException` |
| NFR-R3 | `ReadStudentDashboardAction` must handle null registration gracefully (default counts to 0) |
| NFR-R4 | Cache invalidation listeners must execute synchronously — no queued jobs |
| NFR-R5 | `AdminDashboard` readiness checks must catch exceptions and return `false`, never throw |
| NFR-A1 | All dashboard UI must meet WCAG 2.1 Level AA |
| NFR-A2 | Dashboard must be navigable via keyboard alone (tab order follows logical reading order) |
| NFR-A3 | Readiness check status must be conveyed via both color and text (not color alone) |
| NFR-L1 | All dashboard labels must use `__()` translation helper |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |
| NFR-M1 | Dashboard data aggregation must be delegated to Read Actions, never inline in Livewire |
| NFR-M2 | Read Actions must extend `BaseReadAction` and follow the Action Triad pattern |
| NFR-M3 | Cache keys must be declared in `config/cache-keys.php` — no ad-hoc key strings |
| NFR-M4 | Each dashboard component must be a single-responsibility class under `app/User/Dashboard/Livewire/` |

---

## 6. API / Data Contracts

### 6.1 DashboardService

```php
// app/User/Services/DashboardService.php (47 lines)
class DashboardService
{
    public function getDashboardForUser(User $user): string;
    public function getProxyDashboardForUser(User $user): ?string;
    public function getSharedStats(): array;
}
```

### 6.2 DashboardController

```php
// app/User/Http/Controllers/DashboardController.php
class DashboardController extends BaseController
{
    public function __invoke(Request $request, DashboardService $dashboardService): RedirectResponse;
}
```

### 6.3 UserDashboard (Base Component)

```php
// app/User/Dashboard/Livewire/UserDashboard.php (31 lines)
#[Layout('core::layouts.app')]
class UserDashboard extends Component
{
    public function getUser(): ?User;
    public function getRecentActivities(): Collection; // 5 most recent ActivityLog
    public function render(): View; // user.dashboard.index
}
```

### 6.4 Role-Specific Dashboard Components

```php
// AdminDashboard — extends UserDashboard, 28 stats + 5 readiness checks
class AdminDashboard extends UserDashboard
{
    public array $stats = [];
    public array $readiness = []; // database, mail, cache, queue, storage
    public function mount(ReadAdminDashboardAction $statsAction): void;
}

// StudentDashboard — extends UserDashboard, 8 stats
class StudentDashboard extends UserDashboard
{
    public ?Registration $registration = null;
    public int $totalJournals = 0;
    public int $verifiedJournals = 0;
    public float $attendancePercent = 100.0;
    public int $assignmentSubmittedCount = 0;
    public int $assignmentTotalCount = 0;
    public int $handbookReadCount = 0;
    public int $handbookTotalCount = 0;
    public function boot(): void; // abort_unless student role
    public function mount(ReadStudentDashboardAction $action): void;
}

// TeacherDashboard — extends UserDashboard, 6 stats
class TeacherDashboard extends UserDashboard
{
    public int $supervisedStudents = 0;
    public int $pendingJournals = 0;
    public int $activeCompanies = 0;
    public int $ungradedSubmissions = 0;
    public int $supervisionLogsCount = 0;
    public int $unresolvedIncidents = 0;
    public function boot(): void; // allows teacher or admin
    public function mount(ReadTeacherDashboardAction $action): void;
}

// SupervisorDashboard — extends UserDashboard, 5 stats
class SupervisorDashboard extends UserDashboard
{
    public int $activeInterns = 0;
    public int $pendingEvaluations = 0;
    public int $verifiedJournals = 0;
    public int $pendingJournals = 0;
    public int $pendingAttendance = 0;
    public function boot(): void; // allows supervisor, admin, or teacher
    public function mount(ReadSupervisorDashboardAction $action): void;
}
```

### 6.5 Read Actions

```php
// app/SysAdmin/Actions/ReadAdminDashboardAction.php (91 lines)
final class ReadAdminDashboardAction extends BaseReadAction
{
    public function execute(): array;
    // Cache::remember('sysadmin.dashboard.stats', 300, closure)
    // Returns 28 keys across 9 sections:
    //   People (7), Internships (2), Registration Pipeline (4),
    //   Placements (4), Attendance (2), Logbooks (2), Certificates (3),
    //   Companies (1), Throughput (1), Audit (3)
}

// app/User/Dashboard/Actions/ReadStudentDashboardAction.php (102 lines)
final class ReadStudentDashboardAction extends BaseReadAction
{
    public function execute(string $userId): array;
    // Cache::remember('dashboard.student.' . $userId, 300, closure)
    // Queries: Registration, Logbook, Attendance, Assignment, Submission, Document, Activity
    // @throws RejectedException if user not found
}

// app/User/Dashboard/Actions/ReadTeacherDashboardAction.php (86 lines)
final class ReadTeacherDashboardAction extends BaseReadAction
{
    public function execute(): array;
    // Cache::remember('sysadmin.dashboard.stats.teacher.' . Auth::id(), 300, closure)
    // Queries: Registration, Logbook, Submission, SupervisionLog, IncidentReport
    // All scoped to user's supervised registrations via mentors relationship
}

// app/User/Dashboard/Actions/ReadSupervisorDashboardAction.php (73 lines)
final class ReadSupervisorDashboardAction extends BaseReadAction
{
    public function execute(): array;
    // Cache::remember('sysadmin.dashboard.stats.supervisor.' . Auth::id(), 300, closure)
    // Queries: Registration, EvaluationResponse, Logbook, Attendance
    // All scoped to user's supervised registrations via mentors relationship
}
```

### 6.6 Cache Invalidation Listeners

```php
// app/User/Dashboard/Listeners/ClearDashboardCacheOnDepartmentChange.php (18 lines)
final class ClearDashboardCacheOnDepartmentChange
{
    public function handle(DepartmentCreated|DepartmentDeleted|DepartmentUpdated $event): void;
    // Cache::forget(config('cache-keys.admin_dashboard_stats'))
}

// app/User/Dashboard/Listeners/ClearDashboardCacheOnYearChange.php (19 lines)
final class ClearDashboardCacheOnYearChange
{
    public function handle(AcademicYearCreated|AcademicYearActivated|AcademicYearUpdated|AcademicYearDeleted $event): void;
    // Cache::forget(config('cache-keys.admin_dashboard_stats'))
}
```

### 6.7 Cache Keys

```php
// config/cache-keys.php
'admin_dashboard_stats' => 'sysadmin.dashboard.stats',  // Admin, Teacher, Supervisor
'dashboard_student'     => 'dashboard.student.',         // Student (suffix: userId)
```

### 6.8 Routes

```php
// routes/web/user.php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::livewire('/my-dashboard', UserDashboard::class)->name('user.dashboard');
});

Route::prefix('admin')->name('sysadmin.')->middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::livewire('/dashboard', AdminDashboard::class)->name('dashboard');
});

Route::prefix('student')->name('student.')->middleware(['auth', 'role:student'])->group(function () {
    Route::livewire('/dashboard', StudentDashboard::class)->name('dashboard');
});

Route::prefix('teacher')->name('teacher.')->middleware(['auth', 'role:teacher'])->group(function () {
    Route::livewire('/dashboard', TeacherDashboard::class)->name('dashboard');
});

Route::prefix('supervisor')->name('supervisor.')->middleware(['auth', 'role:supervisor'])->group(function () {
    Route::livewire('/dashboard', SupervisorDashboard::class)->name('dashboard');
});
```

### 6.9 Event → Listener Registration

```
DepartmentCreated  → ClearDashboardCacheOnDepartmentChange
DepartmentUpdated  → ClearDashboardCacheOnDepartmentChange
DepartmentDeleted  → ClearDashboardCacheOnDepartmentChange
AcademicYearCreated   → ClearDashboardCacheOnYearChange
AcademicYearActivated → ClearDashboardCacheOnYearChange
AcademicYearUpdated   → ClearDashboardCacheOnYearChange
AcademicYearDeleted   → ClearDashboardCacheOnYearChange
```

---

## 7. Design Decisions

### DD-1 — Dashboard Routing via Service, Not Middleware

**Decision:** Role-based routing uses `DashboardService::getDashboardForUser()` from
`DashboardController`, not role-specific middleware.
**Rationale:** A service allows priority-based role selection (super_admin > admin) and
fallback logic via `match(true)`. Middleware cannot express priority ordering and would
require complex role-expression logic across multiple routes.
**Trade-off:** Extra service class (47 lines). Mitigated by single-responsibility. Alternative
rejected: inline role check in controller (violates SRP).

### DD-2 — Dashboard Cache with 5-Minute TTL

**Decision:** Dashboard statistics cached via `Cache::remember()` with 300-second TTL.
**Rationale:** Dashboard data is read-heavy and write-light. Real-time accuracy is not required.
5-minute TTL balances freshness with performance. Event-driven invalidation covers structural
changes (departments, academic years). Non-structural changes accept up to 5 minutes staleness.
**Trade-off:** Up to 5 minutes stale for non-structural changes. Acceptable — no business
decision depends on sub-5-minute accuracy.

### DD-3 — Base Dashboard Component with Role Specialization

**Decision:** `UserDashboard` is a base Livewire component; role dashboards extend it (template
method pattern).
**Rationale:** Common functionality (user retrieval, recent activities, layout attribute) is
shared. Each dashboard adds its own aggregation and role gating. Avoids duplication across 4
dashboards while keeping each self-contained.
**Trade-off:** PHP single inheritance limits extension. Mitigated by lightweight base (31 lines)
and independent leaf classes.

### DD-4 — Per-User Cache Keys for Student Dashboard

**Decision:** Student cache key includes user ID (`dashboard.student.{userId}`); admin/teacher/
supervisor use role-prefixed keys.
**Rationale:** Student data is user-specific (own registration, journals, attendance). Per-user
keys prevent data leakage. Admin data is system-wide (single key). Teacher/supervisor keys
include user ID because queries are scoped to supervised registrations.
**Trade-off:** More cache keys for students. Acceptable — bounded by enrollment (< 1000).

### DD-5 — Synchronous Cache Invalidation via Listeners

**Decision:** Cache invalidation uses synchronous event listeners, not queued jobs.
**Rationale:** Structural entity changes are low-frequency admin operations. The next request
must read fresh data. Queued invalidation creates race conditions with stale reads.
**Trade-off:** Slight overhead on department/year CRUD. Negligible for admin operations.

### DD-6 — Readiness Checks in Component, Not Action

**Decision:** Infrastructure readiness checks (DB, mail, cache, queue, storage) run in
`AdminDashboard::mount()`, not in `ReadAdminDashboardAction::execute()`.
**Rationale:** Readiness checks are infrastructure concerns, not business data. They test
system health via exception handling and config inspection — a different concern from domain
aggregation. Keeping them in the component separates concerns cleanly.
**Trade-off:** Readiness checks are not cached. Acceptable — each check is lightweight (< 50ms
total for all 5).

---

## 8. Success Metrics

### Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Cache hit load | < 200ms | `Cache::remember()` TTL 300s |
| Cache miss load | < 2s | Fresh aggregation queries |
| Invalidation accuracy | < 1s stale after entity change | Synchronous listener |
| Cache hit rate | > 90% | 5-min TTL covers repeated visits |

### Data Coverage

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Admin stat coverage | 28 keys | `ReadAdminDashboardAction` return array |
| Student stat coverage | 8 keys | `ReadStudentDashboardAction` return array |
| Teacher stat coverage | 6 keys | `ReadTeacherDashboardAction` return array |
| Supervisor stat coverage | 5 keys | `ReadSupervisorDashboardAction` return array |
| Readiness check coverage | 5 checks | DB, mail, cache, queue, storage |

### Routing & Invalidation

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Role-based routing | 100% correct | `DashboardService` match cases |
| Proxy routing | Teachers see supervisor dashboard | `getProxyDashboardForUser()` |
| Fallback routing | Unknown role → `/my-dashboard` | Default match case |
| Cache key registration | 100% in `config/cache-keys.php` | No ad-hoc strings |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [authentication.md](authentication.md) | Authenticated user with role; session state for dashboard personalization |

### Build Guide
After implementing this spec, role-based dashboards display relevant stats, quick actions, and notifications for each user type (admin, teacher, student, supervisor). This is the landing page after login. Dashboard stats are read-only views — the data comes from other modules. The next phase is to build the institutional structure (departments, academic years) that programs and enrollment depend on.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [department-management.md](department-management.md) | Admin dashboard shows department count; department data feeds program statistics |
| 2 | [academic-year-management.md](academic-year-management.md) | Dashboard shows active academic year; year status affects all module visibility |

---

## Quick References

- `app/User/Services/DashboardService.php` — role-based dashboard resolution (47 lines)
- `app/User/Http/Controllers/DashboardController.php` — dashboard routing controller
- `app/User/Dashboard/Livewire/UserDashboard.php` — base dashboard component (31 lines)
- `app/User/Dashboard/Livewire/AdminDashboard.php` — admin dashboard with readiness checks
- `app/User/Dashboard/Livewire/StudentDashboard.php` — student dashboard
- `app/User/Dashboard/Livewire/TeacherDashboard.php` — teacher dashboard
- `app/User/Dashboard/Livewire/SupervisorDashboard.php` — supervisor dashboard
- `app/SysAdmin/Actions/ReadAdminDashboardAction.php` — admin data aggregation (91 lines)
- `app/User/Dashboard/Actions/ReadStudentDashboardAction.php` — student aggregation (102 lines)
- `app/User/Dashboard/Actions/ReadTeacherDashboardAction.php` — teacher aggregation (86 lines)
- `app/User/Dashboard/Actions/ReadSupervisorDashboardAction.php` — supervisor aggregation (73 lines)
- `app/User/Dashboard/Listeners/ClearDashboardCacheOnDepartmentChange.php` — dept invalidation
- `app/User/Dashboard/Listeners/ClearDashboardCacheOnYearChange.php` — year invalidation
- `config/cache-keys.php` — centralized cache key declarations
- `routes/web/user.php` — all dashboard route definitions
- **Related spec:** [authentication.md](authentication.md) — Login, logout, throttling
