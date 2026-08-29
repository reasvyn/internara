# Cross-Role Proxy

| Field | Value |
|-------|-------|
| Status | Accepted |
| Deciders | Reas Vyn |
| Date | 2026-08-16 |
| Technical Story | [Flat RBAC](adr-flat-rbac-with-functional-roles.md) and [Assessment Spec](../specs/ARDA6-assessment.md) |

## Context and Problem Statement

Internara uses flat RBAC with five fixed roles (`super_admin`, `admin`, `teacher`, `supervisor`,
`student`) — one role per user, no multi-role. Yet the fieldwork domain is asymmetric: teachers
must be able to act on behalf of supervisors and admins on behalf of both teachers and
supervisors. Four operational forces drive this:

1. **Supervisor inactivity** — industry supervisors may not log in for days; student workflows
   (logbook verification, assessments, supervision logs) cannot wait.
2. **Quality assurance** — supervisors may submit incomplete or non-standard entries teachers must
   correct or override.
3. **Accountability** — the school bears ultimate responsibility for student outcomes.
4. **Unforeseen circumstances** — supervisors may leave, change roles, or become unreachable.

Prior art as **"Dual Mentor Fallback"** (assessment grading only) was too narrow and misnamed —
"fallback" implies last resort, whereas proxy is an intentional tool. Giving teachers a second
role (`teacher` + `supervisor`) would trivially unlock access but introduces five concerns:

| Concern | Impact |
|---------|--------|
| Audit confusion | Log shows teacher ID but cannot distinguish primary vs proxy role |
| Workload obfuscation | Accountability for teaching vs proxy duties cannot be separated |
| Policy complexity | Every policy method duplicates `hasRole X OR Y` checks |
| Scope creep | Teacher accidentally gains all supervisor-only industry features |
| Schema pollution | `model_has_roles` pivot needs metadata for primary vs delegated |

**Decision Drivers:**

* No multi-role assignment — keep one role per user
* Audit clarity — every proxy action traceable to both actor and proxied role
* Per-method, per-policy granularity and scope isolation
* No schema change; application-layer only

## Considered Options

* **Multi-role assignment** — grant `supervisor` role to teachers who need it.
  *Pros:* trivial. *Cons:* audit confusion, scope overbreadth, policy complexity, schema pollution;
  rejected per table above.*
* **Dual Mentor Fallback (narrow)** — teacher fills supervisor scores only in assessment.
  *Pros:* solved one case. *Cons:* not general; misnamed; no audit or scope discipline.*
* **Cross-Role Proxy at application layer (chosen)** — runtime permission check, no role expansion;
  proxy capability delegated to `MentorEntity` via the Registration bridge.
  *Pros:* no schema change, auditable, scope-isolated, gradually adoptable per domain.*

## Decision Outcome

**Chosen option: Cross-Role Proxy at the application layer** — a user retains exactly one role;
proxy is a runtime permission check, not a role expansion.

**Proxy Hierarchy:**

```
Admin ── dapat proxy ──> Teacher ── dapat proxy ──> Supervisor
  │                           │
  └────── dapat proxy ─────────┘
```

| Acting User | Can Proxy As | Scope |
|-------------|--------------|-------|
| `admin` | `teacher`, `supervisor` | Any student in any program |
| `teacher` | `supervisor` | Only students assigned to that teacher's mentorship |
| `supervisor`, `student` | — | No proxy capability |

**Definition:** a user with role X can perform an action requiring role Y without changing their
own role; the record stores both identities: who acted and in whose stead.

**Implementation Pattern:**

**1. Proxy Gate via MentorEntity** — policies delegate to `MentorEntity` through
`Registration::asMentorEntity()`:

```php
class SupervisionLogPolicy extends BasePolicy
{
    public function review(User $user, SupervisionLog $log): bool
    {
        if ($log->supervisor_id === $user->id) return true;
        $registration = $log->registration;
        if ($registration === null) return false;
        return $registration->asMentorEntity()->canReviewSupervisionLog($user);
    }
}
```

**2. Audit Trail** — activity log stores proxy metadata in `properties` JSON (no new columns):

```php
activity()
    ->causedBy($user) // teacher who acted
    ->performedOn($model)
    ->withProperties(['proxy_role' => 'supervisor', 'proxy_reason' => 'supervisor_inactive'])
    ->event('verified')->log('logbook_verified_via_proxy');
```

**3. Policy Layer Integration** — every supervisor-scoped policy delegates:

```php
public function verify(User $user, Logbook $entry): bool
{
    return $entry->registration?->asMentorEntity()->canVerifyLogbook($user) ?? false;
}
```

**4. MentorEntity (Entity-Model Separation)** — business rules in `app/User/Mentor/Entities/MentorEntity.php`,
bridged via `Registration::asMentorEntity(): MentorEntity` using `MentorEntity::fromModel()`.
Categories: role queries (`isTeacher`, `isSupervisor`, `isMentor`), proxy gates
(`canProxyAsSupervisor`, `canProxyAsTeacher`), and domain capabilities
(`canVerifyLogbook`, `canScoreCompetency`, `canReviewSupervisionLog`, `canGradeSubmission`).

Excerpt:

```php
final readonly class MentorEntity extends BaseEntity
{
    public function __construct(private string $registrationId, private Collection $mentors) {}
    public static function fromModel(Model $model): static {
        $mentors = $model->relationLoaded('mentors') ? $model->mentors : $model->mentors()->get();
        return new self(registrationId: $model->id, mentors: $mentors);
    }
    public function canProxyAsSupervisor(User $user): bool {
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) return true;
        if ($user->hasRole('teacher') && $this->isTeacher($user)) return true;
        return false;
    }
    public function canVerifyLogbook(User $user): bool {
        if ($this->isSupervisor($user)) return true;
        return $this->canProxyAsSupervisor($user);
    }
}
```

**Testability** — MentorEntity unit-tests without DB:

```php
test('teacher can proxy supervisor for assigned student', function () {
    $teacher = User::factory()->make(['id' => 't-1']); $teacher->assignRole('teacher');
    $mentors = collect([tap(User::factory()->make(['id' => 't-1']), fn($u) => $u->pivot = (object)['role'=>'teacher'])]);
    $entity = new MentorEntity(registrationId: 'reg-1', mentors: $mentors);
    expect($entity->canProxyAsSupervisor($teacher))->toBeTrue();
});
```

**5. Livewire Layer** — `@can('verify', $logbook)` gates buttons; policy-driven visibility
means no Livewire change — UI optionally shows a "Verify as Proxy" badge when
`session('proxy_mode')` is active.

**Explicit Proxy Mode (Optional):** teacher toggles "Act as Supervisor", session flag
`proxy_role = 'supervisor'` set, banner shown, logging gains proxy context automatically.
Base implementation needs no UI — proxy is implicit via policy gate.

**What Does NOT Change:**

| Aspect | Stays | Reason |
|--------|-------|--------|
| User role assignment | Single role | `spatie/laravel-permission` unchanged |
| `model_has_roles` | No new columns | No schema change |
| `BasePolicy::before()` | Super-admin bypass only | Proxy is directed capability, not bypass |
| Route middleware | Role-based (`role:teacher`) | Proxy checked at policy layer |

This replaces inline role checks with a single, testable source of truth.

**Coverage Map** — adopted per domain, independently:

| Module | Action | Proxy Path | Priority |
|--------|--------|------------|----------|
| Journals/Logbook | Verify logbook | Teacher → Supervisor | High |
| Assessment | Score competency (supervisor) | Teacher → Supervisor | High |
| Assessment | Finalize assessment | Teacher → Supervisor | High |
| Journals/SupervisionLog | Review supervision log | Teacher → Supervisor | Medium |
| Journals/SupervisionLog | Review supervision log | Admin → Teacher | Low |
| Assignment/Submission | Grade submission | Teacher → Supervisor | Medium |

**Replaces:** earlier "Dual Mentor Fallback" concept — all docs updated to "Cross-Role Proxy".

**Comparison:**

| Aspect | Multi-Role (Rejected) | Cross-Role Proxy (Selected) |
|--------|-----------------------|-----------------------------|
| Implementation | Assign supervisor role to teacher | Runtime check in policy |
| Schema | None | None |
| Audit | No proxy context | `activity_log.properties` records `proxy_role` |
| Scope | All supervisor features unlocked | Per-method, per-policy granularity |
| Test | Multi-role setup | `canProxyAs()` with mock registration |
| Understanding | Simple but misleading | Explicit delegation |

### Positive Consequences

* No schema change — entirely application-layer; no migrations
* Audit clarity — proxy context in `activity_log.properties`
* Scope isolation — only students already mentored
* Backward compatible and gradually adoptable per domain

### Negative Consequences

* Policy verbosity — proxy clause per method, mitigated by shared helper
* Testing surface — each proxy path needs its own case
* UI complexity if explicit proxy mode is added

## Links

* [Flat RBAC](adr-flat-rbac-with-functional-roles.md) — flat roles this mechanism builds on
* [Entity-Model Separation](adr-entity-model-separation.md) — Entity pattern for MentorEntity
* [Policy Pattern](../guides/arch/policy-pattern.md) — where proxy gates live
* [Assessment Module](../refs/modules/assessment.md) — first proxy adopter
* [Journals Module](../refs/modules/journals.md) — logbook verification use case
