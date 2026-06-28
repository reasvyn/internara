# Roadmap — Cross-Role Proxy Implementation

> **Last updated:** 2026-06-16
> **Changes:** replace Guidance restructuring (deferred) with Cross-Role Proxy implementation plan

## Description
> **Target:** Application-wide authorization layer
> **Dependencies:** `MentorEntity` (existing), `BasePolicy`, all module policies and Livewire components

---


## 1. Overview

The Cross-Role Proxy ADR ([ADR-014](adr/adr-cross-role-proxy.md)) defines an application-layer
delegation model: teachers can proxy as supervisors, and admins can proxy as both teachers and
supervisors — without multi-role assignment.

The `MentorEntity` (`app/User/Mentor/Entities/MentorEntity.php`) is already fully designed with
proxy-aware capability methods, and the `Registration` model exposes it via `asMentorEntity()`.
**However, no consumer code calls it.** All policies and Livewire components still use inline
`$user->hasRole()` checks that bypass the proxy layer entirely.

This roadmap covers the phased integration of `MentorEntity` across all modules.

---

## 2. Current State (Gaps)

### 2.1 Critical — Blocks Proxy Entirely

| # | File | Issue |
|---|------|-------|
| C1 | `app/User/Dashboard/Livewire/SupervisorDashboard.php:24` | `abort_ununless(hasRole('supervisor'), 403)` — hard 403 blocks teacher/admin proxy |
| C2 | `app/User/Dashboard/Livewire/TeacherDashboard.php:26` | `abort_ununless(hasRole('teacher'), 403)` — hard 403 blocks admin proxy |
| C3 | All 5 MentorEntity domain methods | `canVerifyLogbook()`, `canScoreCompetency()`, `canReviewSupervisionLog()`, `canGradeSubmission()`, `canVerifyAttendance()` are defined but **never called** from any consumer |

### 2.2 High — Bypasses MentorEntity

| # | File | Issue |
|---|------|-------|
| H1 | `app/Journals/Attendance/Policies/AttendancePolicy.php` | Inline mentor queries instead of `->asMentorEntity()->canVerifyAttendance()` |
| H2 | `app/Journals/Logbook/Policies/LogbookPolicy.php` | Inline mentor queries instead of `->asMentorEntity()->canVerifyLogbook()` |
| H3 | `app/Guidance/SupervisionLog/Policies/SupervisionLogPolicy.php` | Raw role check instead of `->asMentorEntity()->canReviewSupervisionLog()` |
| H4 | `app/Assignment/Submission/Policies/SubmissionPolicy.php` | `isTeacher()` without MentorEntity proxy context |
| H5 | `app/Assessment/Livewire/AssessmentGrading.php` | `isAssignedAsMentor()` duplicates MentorEntity logic |
| H6 | `app/Assignment/Submission/Livewire/SubmissionGrading.php` | Query scope duplicating mentor filtering |
| H7 | `app/Journals/Logbook/Livewire/LogbookManager.php` | Query scopes duplicating mentor filtering |
| H8 | `app/Assessment/Policies/AssessmentPolicy.php` | Role checks that should allow proxy scoring |

### 2.3 Medium — BasePolicy Trait

| # | File | Issue |
|---|------|-------|
| M1 | `app/Core/Policies/Concerns/AuthorizesRoles.php` | `isTeacher()`, `isSupervisor()`, `isAdmin()` are raw `hasRole()` — every policy using them bypasses proxy |

---

## 3. Implementation Phases

### Phase 1: Wire MentorEntity into Core (Priority: Critical)

#### Task 1.1 — BasePolicy Proxy Integration

Add a `mentorProxyFor()` helper method to `BasePolicy` that provides a shortcut for MentorEntity
delegation:

```php
// In BasePolicy or a new HasProxySupport trait:
protected function mentorProxyFor(?Registration $registration, User $user): ?MentorEntity
{
    if ($registration === null) {
        return null;
    }

    return $registration->asMentorEntity();
}
```

This ensures every policy can call `$this->mentorProxyFor($reg, $user)?->canVerifyLogbook()` etc.

#### Task 1.2 — Fix Hard 403 Gates

| File | Replacement |
|------|-------------|
| `SupervisorDashboard.php` | Change to redirect with flash if user has proxy capability instead of 403 |
| `TeacherDashboard.php` | Same approach — check `$user->hasRole('admin')` for admin proxy |

**Files:** `SupervisorDashboard.php`, `TeacherDashboard.php`
**Pattern:** Replace `abort_unless(hasRole('X'), 403)` with redirect + flash for proxy users

### Phase 2: Policy Integration (Priority: High)

Migrate each module's policy to delegate authorization to `MentorEntity` instead of inline
`hasRole()` checks.

#### Task 2.1 — Journals/Logbook

**Files:** `app/Journals/Logbook/Policies/LogbookPolicy.php`

Current:
```php
public function view(User $user, Logbook $entry): bool
{
    if ($this->isAdmin($user)) return true;
    if ($this->isTeacher($user) && $entry->registration?->mentors()...role teacher) return true;
    if ($this->isSupervisor($user) && $entry->registration?->mentors()...role supervisor) return true;
    return $entry->user_id === $user->id;
}
```

Target:
```php
public function view(User $user, Logbook $entry): bool
{
    if ($this->isAdmin($user)) return true;
    if ($entry->user_id === $user->id) return true;

    return $this->mentorProxyFor($entry->registration, $user)?->canVerifyLogbook($user) ?? false;
}
```

#### Task 2.2 — Journals/Attendance

**Files:** `app/Journals/Attendance/Policies/AttendancePolicy.php`

Same pattern: replace inline mentor queries with `mentorProxyFor()` + `canVerifyAttendance()`.

#### Task 2.3 — Guidance/SupervisionLog

**Files:** `app/Guidance/SupervisionLog/Policies/SupervisionLogPolicy.php`

Replace inline `$log->supervisor_id === $user->id` + mentor query with
`mentorProxyFor()` + `canReviewSupervisionLog()`.

#### Task 2.4 — Assignment/Submission

**Files:** `app/Assignment/Submission/Policies/SubmissionPolicy.php`

Replace `isTeacher()` check with `mentorProxyFor()` + `canGradeSubmission()`.

#### Task 2.5 — Assessment

**Files:** `app/Assessment/Policies/AssessmentPolicy.php`, `app/Assessment/Livewire/AssessmentGrading.php`

Replace `isAssignedAsMentor()` raw query with `mentorProxyFor()` + `canScoreCompetency()`.

### Phase 3: Livewire Query Scope Integration (Priority: High)

Livewire components that filter data by mentor assignment currently duplicate inline queries.

#### Task 3.1 — LogbookManager

**Files:** `app/Journals/Logbook/Livewire/LogbookManager.php`

Replace inline `->whereHas('registration.mentors', role teacher/supervisor)` with:
```php
$registrations = Registration::whereHasMentor($user)->pluck('id');
$query->whereIn('registration_id', $registrations);
```

Where `whereHasMentor()` is a new scope on Registration that uses `asMentorEntity()` logic.

#### Task 3.2 — SubmissionGrading

**Files:** `app/Assignment/Submission/Livewire/SubmissionGrading.php`

Same pattern — add `Registration::whereHasMentor()` scope and use it.

### Phase 4: AuthorizesRoles Trait Deprecation (Priority: Medium)

**Files:** `app/Core/Policies/Concerns/AuthorizesRoles.php`

The `isTeacher()`, `isSupervisor()`, and `isAdmin()` methods in the trait are used by every policy.
They cannot be removed globally without breaking existing checks. Instead:

1. Add deprecation notice `@deprecated Use mentorProxyFor()->canXxx() instead`
2. Migrate all callers to MentorEntity (Phase 2 covers the module policies)
3. After all callers migrate, remove the `isStudent()`, `isTeacher()`, `isSupervisor()` helpers

`isAdmin()` stays — it gates admin-specific features not subject to proxy (settings, backups, etc.)

### Phase 5: Dashboard Routing (Priority: Low)

**Files:** `app/User/Services/DashboardService.php`

Extend dashboard routing to show appropriate dashboard for proxy users:
- Teacher proxying supervisor → show supervisor dashboard with "Proxy" banner
- Admin proxying teacher/supervisor → show target dashboard with "Proxy" banner

---

## 4. Testing Strategy

| Test | Type | What It Verifies |
|------|------|------------------|
| `MentorEntityTest` | Unit | All 5 domain methods with teacher→supervisor proxy, admin→teacher proxy, direct role access |
| `LogbookPolicyProxyTest` | Feature | Teacher can verify logbook for assigned student (proxy); admin can verify any |
| `AttendancePolicyProxyTest` | Feature | Teacher can verify attendance via proxy; unrelated teacher cannot |
| `SupervisionLogPolicyProxyTest` | Feature | Teacher can review supervision log via proxy |
| `SubmissionPolicyProxyTest` | Feature | Teacher can grade submission via proxy |
| `AssessmentGradingProxyTest` | Feature | Teacher can score supervisor competency via proxy |
| `SupervisorDashboardAccessTest` | Feature | Teacher with proxy access sees dashboard (no 403) |
| `TeacherDashboardAccessTest` | Feature | Admin sees teacher dashboard (no 403) |

### Entity Test Example

```php
test('teacher can proxy as supervisor for assigned student', function () {
    $teacher = User::factory()->make(['id' => 't-1']);
    $teacher->assignRole('teacher');

    $mentors = collect([
        tap(new User, fn ($u) => $u->forceFill(['id' => 't-1']))
            ->setRelation('pivot', (object) ['role' => 'teacher']),
    ]);

    $entity = new MentorEntity(
        registrationId: 'reg-1',
        mentors: $mentors,
    );

    expect($entity->canProxyAsSupervisor($teacher))->toBeTrue();
    expect($entity->canVerifyLogbook($teacher))->toBeTrue();
    expect($entity->canScoreCompetency($teacher, 'supervisor'))->toBeTrue();
});
```

---

## 5. Integration Order

| # | Phase | Task | Files | Depends On |
|---|-------|------|-------|------------|
| 1 | 1 | BasePolicy proxy helper | `BasePolicy.php` or new trait | — |
| 2 | 1 | Fix SupervisorDashboard 403 | `SupervisorDashboard.php` | — |
| 3 | 1 | Fix TeacherDashboard 403 | `TeacherDashboard.php` | — |
| 4 | 2 | LogbookPolicy | `LogbookPolicy.php` | 1 |
| 5 | 2 | AttendancePolicy | `AttendancePolicy.php` | 1 |
| 6 | 2 | SupervisionLogPolicy | `SupervisionLogPolicy.php` | 1 |
| 7 | 2 | SubmissionPolicy | `SubmissionPolicy.php` | 1 |
| 8 | 2 | AssessmentPolicy + AssessmentGrading | `AssessmentPolicy.php`, `AssessmentGrading.php` | 1 |
| 9 | 3 | LogbookManager query scope | `LogbookManager.php` | 1 |
| 10 | 3 | SubmissionGrading query scope | `SubmissionGrading.php` | 1 |
| 11 | 4 | AuthorizesRoles deprecation + migration | `AuthorizesRoles.php`, all policies | 2, 3 |
| 12 | 5 | Dashboard routing | `DashboardService.php` | 2 |
| 13 | — | Tests | All test files | 1–5 |

---

## 6. No-Change Zones

The following are intentionally excluded from proxy:

| Feature | Reason |
|---------|--------|
| `User/Mentor/Entities/MentorEntity.php` | This IS the proxy implementation — no changes needed |
| `Enrollment/Registration/Models/Registration.php` | `asMentorEntity()` bridge already exists |
| `AuthorizesRoles::isAdmin()` | Admin gates admin-specific features (settings, backups, Pulse) — no proxy needed |
| Route middleware (`role:teacher`) | Proxy is checked at policy layer, not route layer — route stays role-based |
| Super admin bypass (`BasePolicy::before()`) | Super admin already bypasses all checks — orthogonal to proxy |
