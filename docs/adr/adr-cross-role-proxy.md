# ADR-014: Cross-Role Proxy

> **Last updated:** 2026-06-16 **Changes:** sync — initial metadata sync with new format

## Description

Authorization supports proxy relationships where one user (e.g., kepala_program) can act on behalf
of another (e.g., pembimbing) for selected operations within the same internship context.

## Context

Internara operates a flat RBAC model (`docs/adr/adr-flat-rbac-with-functional-roles.md`) with five
fixed roles: `super_admin`, `admin`, `teacher`, `supervisor`, `student`. Each user holds exactly one
role — there is no multi-role assignment.

However, the vocational fieldwork management domain has an inherent asymmetry: **teachers must be
able to act on behalf of supervisors** in certain situations, and **admins must be able to act on
behalf of both teachers and supervisors**. The reasons are operational, not architectural:

1. **Supervisor inactivity** — Industry supervisors may not log in for days or weeks. Student
   workflows (logbook verification, assessment scoring, supervision log review) cannot wait.
2. **Quality assurance** — Supervisors may submit incomplete, inaccurate, or non-standard entries.
   Teachers need the authority to correct or override them.
3. **Accountability** — The school (teacher/admin) bears ultimate responsibility for student
   outcomes. They must have the tools to enforce standards.
4. **Unforeseen circumstances** — Supervisors may leave the company, change roles, or be
   unreachable. The program cannot stall.

### Problem

Giving teachers and admins multiple roles (e.g., `teacher` + `supervisor`) would trivially solve the
access problem:

- ✅ Teacher could verify logbooks (normally supervisor-only)
- ✅ Teacher could score supervisor competencies (normally supervisor-only)
- ✅ Teacher could review supervision logs (normally supervisor-only)
- ✅ Admin could do everything (already has `super_admin` bypass)

But multi-role assignment introduces serious downsides:

| Concern                  | Impact                                                                                                                            |
| ------------------------ | --------------------------------------------------------------------------------------------------------------------------------- |
| **Audit confusion**      | "Who acted as the supervisor?" — the log shows a teacher's ID, but the system cannot distinguish primary role from proxy role     |
| **Workload obfuscation** | A teacher with both roles cannot be held accountable separately for teaching duties vs proxy actions                              |
| **Policy complexity**    | Every policy method would need to check "does user have role X OR role Y?" — duplicating the proxy logic across dozens of methods |
| **Scope creep**          | A teacher with `supervisor` role could unintentionally access supervisor-only features meant for industry supervisors only        |
| **Schema pollution**     | The `model_has_roles` pivot table would need additional metadata to distinguish primary vs delegated roles                        |

### Prior Art

The concept previously existed as **"Dual Mentor Fallback"** — a mechanism limited to assessment
grading where teachers could fill in supervisor scores. It was too narrow in scope and misnamed
("fallback" implies last resort, whereas proxy is an intentional tool).

## Decision

Implement **Cross-Role Proxy** at the **application layer only** — no changes to the user model,
role assignments, or permission schema. A user retains exactly one role. The proxy capability is a
runtime permission check, not a role expansion.

### Proxy Hierarchy

```
Admin ── dapat proxy ──> Teacher ── dapat proxy ──> Supervisor
  │                           │
  └────── dapat proxy ─────────┘
```

| Acting User  | Can Proxy As            | Scope                                               |
| ------------ | ----------------------- | --------------------------------------------------- |
| `admin`      | `teacher`, `supervisor` | Any student in any program                          |
| `teacher`    | `supervisor`            | Only students assigned to that teacher's mentorship |
| `supervisor` | —                       | No proxy capability needed                          |
| `student`    | —                       | No proxy capability needed                          |

### Definition

**Cross-Role Proxy** means a user with role X can perform an action that normally requires role Y,
without changing their own role. The system records the action with both identities: who performed
it and in whose stead.

### Implementation Pattern

#### 1. Proxy Gate — Delegates to MentorEntity

Policies delegate proxy checks to `MentorEntity` via the `asMentorEntity()` bridge on Registration,
keeping policy methods slim:

```php
class SupervisionLogPolicy extends BasePolicy
{
    public function review(User $user, SupervisionLog $log): bool
    {
        // Direct authorization
        if ($log->supervisor_id === $user->id) {
            return true;
        }

        // Cross-role proxy: delegate to MentorEntity
        $registration = $log->registration;

        if ($registration === null) {
            return false;
        }

        return $registration->asMentorEntity()->canReviewSupervisionLog($user);
    }
}
```

#### 2. Audit Trail via Activity Log

When a proxy action occurs, the activity log records:

```php
activity()
    ->causedBy($user) // The teacher who acted
    ->performedOn($model) // The affected record
    ->withProperties([
        'proxy_role' => 'supervisor', // The role being proxied
        'proxy_reason' => 'supervisor_inactive', // Optional: why
    ])
    ->event('verified')
    ->log('logbook_verified_via_proxy');
```

No new database columns. The `properties` JSON on `activity_log` stores the proxy metadata.

#### 3. Policy Layer Integration

Every policy that gates a supervisor-scoped action delegates to `MentorEntity` for proxy checks.
This keeps policy methods declarative — the entity handles the full proxy matrix:

```php
public function verify(User $user, Logbook $entry): bool
{
    return $entry->registration?->asMentorEntity()->canVerifyLogbook($user) ?? false;
}
```

#### 4. Entity Layer Integration — MentorEntity

The proxy business rules are encapsulated in a `MentorEntity` following the Entity-Model Separation
pattern. This keeps proxy logic testable without a database and reusable across all modules.

The entity lives in `app/User/Mentor/Entities/MentorEntity.php` (User module owns the mentor
concept) and is bridged via the Registration model:

```php
// On Registration model:
public function asMentorEntity(): MentorEntity
{
    return MentorEntity::fromModel($this);
}
```

**MentorEntity** exposes three categories of methods:

| Category            | Methods                                                                                                                      | Purpose                                             |
| ------------------- | ---------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------- |
| Role queries        | `isTeacher($user)`, `isSupervisor($user)`, `isMentor($user)`                                                                 | Direct role checks against mentor assignments       |
| Proxy gates         | `canProxyAsSupervisor($user)`, `canProxyAsTeacher($user)`                                                                    | Cross-role proxy eligibility                        |
| Domain capabilities | `canVerifyLogbook($user)`, `canScoreCompetency($user, $role)`, `canReviewSupervisionLog($user)`, `canGradeSubmission($user)` | Proxy-aware capability checks for specific features |

```php
final readonly class MentorEntity extends BaseEntity
{
    public function __construct(private string $registrationId, private Collection $mentors) {}

    public static function fromModel(Model $model): static
    {
        $mentors = $model->relationLoaded('mentors') ? $model->mentors : $model->mentors()->get();

        return new self(registrationId: $model->id, mentors: $mentors);
    }

    // Teacher can proxy supervisor for their assigned students
    public function canProxyAsSupervisor(User $user): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }
        if ($user->hasRole('admin')) {
            return true;
        }
        if ($user->hasRole('teacher') && $this->isTeacher($user)) {
            return true;
        }
        return false;
    }

    // Admin can proxy teacher
    public function canProxyAsTeacher(User $user): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }
        if ($user->hasRole('admin')) {
            return true;
        }
        return false;
    }

    // Proxy-aware domain check
    public function canVerifyLogbook(User $user): bool
    {
        if ($this->isSupervisor($user)) {
            return true;
        }
        return $this->canProxyAsSupervisor($user);
    }

    public function canScoreCompetency(User $user, string $evaluatorRole): bool
    {
        if ($evaluatorRole === 'teacher' && $this->isTeacher($user)) {
            return true;
        }
        if ($evaluatorRole === 'supervisor' && $this->isSupervisor($user)) {
            return true;
        }
        if ($evaluatorRole === 'supervisor' && $this->canProxyAsSupervisor($user)) {
            return true;
        }
        if ($this->canProxyAsTeacher($user)) {
            return true;
        }
        return false;
    }
}
```

This replaces inline role checks scattered across policies with a single, testable source of truth.
Usage in a policy:

```php
class LogbookPolicy extends BasePolicy
{
    public function verify(User $user, Logbook $entry): bool
    {
        $registration = $entry->registration;

        if ($registration === null) {
            return false;
        }

        return $registration->asMentorEntity()->canVerifyLogbook($user);
    }
}
```

**Testability advantage** — MentorEntity can be unit-tested without any database:

```php
test('teacher can proxy supervisor for assigned student', function () {
    $teacher = User::factory()->make(['id' => 't-1']);
    $teacher->assignRole('teacher');

    $mentors = collect([
        tap(
            User::factory()->make(['id' => 't-1']),
            fn($u) => ($u->pivot = (object) ['role' => 'teacher']),
        ),
    ]);

    $entity = new MentorEntity(registrationId: 'reg-1', mentors: $mentors);

    expect($entity->canProxyAsSupervisor($teacher))->toBeTrue();
});
```

#### 5. Livewire Layer

Livewire components show proxy-accessible actions conditionally:

```blade
@can ('verify', $logbook)
    <x-mary-button label="Verify" wire:click="verify('{{ $logbook->id }}')" />
@endcan
```

If the policy grants access via proxy, the button appears. The UI can optionally show a "Verify as
Proxy" badge if `session('proxy_mode')` is active.

### Explicit Proxy Mode (Optional Enhancement)

For sensitive actions, an explicit "Act as Proxy" mode can be activated:

1. Teacher clicks **"Act as Supervisor"** toggle in the UI
2. Session flag `proxy_role = 'supervisor'` is set
3. All subsequent actions within that session include the proxy context
4. UI shows a persistent banner: "You are acting as proxy for Supervisor — PT Teknologi Maju"
5. Logging includes the proxy context automatically

This is optional and can be introduced later. The base implementation requires no UI changes — proxy
is determined implicitly by the policy gate.

### What Does NOT Change

| Aspect                  | Stays                       | Reason                                                    |
| ----------------------- | --------------------------- | --------------------------------------------------------- |
| User role assignment    | Single role per user        | `spatie/laravel-permission` unchanged                     |
| `model_has_roles` table | No new columns              | No schema changes                                         |
| `BasePolicy::before()`  | Super admin bypass only     | Proxy is not a bypass — it's a directed capability        |
| Route middleware        | Role-based (`role:teacher`) | Proxy is checked at the policy level, not the route level |

## Consequences

### Positive

- **No schema changes** — entirely application-layer. No migrations, no new tables.
- **Audit clarity** — `activity_log.properties` records proxy context. Every action is traceable to
  both the actual user and the role they proxied.
- **Scope isolation** — A teacher proxying supervisor can only act on students they are already
  mentoring. No blanket access to all supervisor functions.
- **Backward compatible** — Existing policies continue to work. Proxy is additive (`||`), not
  replacement.
- **Gradual adoption** — Each domain (logbook, assessment, supervision log, assignment) adopts proxy
  independently via its own policy. No big-bang migration.

### Negative

- **Policy verbosity** — Each policy method that supports proxy needs an additional `canProxyAs()`
  clause. Mitigated by the shared helper.
- **Testing surface** — Each proxy path needs its own test case (user with role X acting as role Y).
- **UI complexity** — If explicit proxy mode is added, the UI needs session management and
  persistent indicators.

### Neutral

- **The `canProxyAs()` helper becomes a core dependency** — lives in `BasePolicy` and imported
  wherever needed.

## Proxy Coverage Map

The following modules and actions adopt proxy. Each is implemented independently when the feature is
built or refactored.

| Module                      | Action                                       | Proxy Path           | Priority |
| --------------------------- | -------------------------------------------- | -------------------- | -------- |
| **Journals/Logbook**        | Verify logbook entry                         | Teacher → Supervisor | High     |
| **Assessment**              | Score competency (evaluator_role=supervisor) | Teacher → Supervisor | High     |
| **Assessment**              | Finalize assessment                          | Teacher → Supervisor | High     |
| **Journals/SupervisionLog** | Review supervision log                       | Teacher → Supervisor | Medium   |
| **Journals/SupervisionLog** | Review supervision log                       | Admin → Teacher      | Low      |
| **Assignment/Submission**   | Grade submission                             | Teacher → Supervisor | Medium   |

## Replaces

This ADR supersedes the earlier "Dual Mentor Fallback" concept. All documentation referring to "Dual
Mentor Fallback" is updated to "Cross-Role Proxy" — see docs sync.

## Comparison: Multi-Role vs Cross-Role Proxy

| Aspect          | Multi-Role (Rejected)                            | Cross-Role Proxy (Selected)                      |
| --------------- | ------------------------------------------------ | ------------------------------------------------ |
| Implementation  | Assign `supervisor` role to teacher user         | Runtime check in policy layer                    |
| Schema change   | None (uses existing pivot)                       | None                                             |
| Audit trail     | No proxy context — user appears as supervisor    | `activity_log.properties` records proxy_role     |
| Scope control   | All supervisor features unlocked (overbroad)     | Per-method, per-policy granularity               |
| Test complexity | Tests need multi-role setup                      | Tests call `canProxyAs()` with mock registration |
| Understanding   | Simple but misleading (teacher IS a supervisor?) | Explicit about delegation                        |

## References

- `app/Core/Channels/CustomDatabaseChannel.php` — Notification channel (proxy notifications)
- `app/User/Mentor/Entities/MentorEntity.php` — Entity encapsulating cross-role proxy business rules
- `app/Enrollment/Registration/Models/Registration.php` — Bridge `asMentorEntity()` method
- `docs/architecture/policy-pattern.md` — Policy pattern reference
- `docs/adr/adr-flat-rbac-with-functional-roles.md` — Existing RBAC decision
- `docs/adr/adr-entity-model-separation.md` — Entity-Model Separation pattern
- `docs/adr/adr-cross-role-proxy.md` — This document
- `docs/modules/assessment.md` — Assessment module (first proxy adopter)
- `docs/modules/journals.md` — Logbook verification (proxy use case)
- `docs/guide/18-assignment-and-assessment.md` — User guide
- `app/Program/Internship/Listeners/NotifyAdminsInternshipCreated.php` — Existing notification
  pattern
