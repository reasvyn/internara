---
name: action-refactoring
description: Apply this skill whenever creating, modifying, or reviewing Action classes or when refactoring business logic out of Livewire components or Controllers into proper Action classes. Trigger on any operation that involves validation, persistence, side effects, or business rule enforcement.
---

# Action Refactoring Skill

## When to Activate

Apply this skill whenever creating, modifying, or reviewing Action classes, or when extracting business logic from Livewire components or Controllers. Trigger on any operation involving validation, persistence, side effects, or business rule enforcement.

## The Action Triad

Actions split into three categories, all under `app/{Module}/{SubModule}/Actions/` with a single `execute()` method:

| Type | Base Class | Purpose | Transaction | Logging |
|------|-----------|---------|-------------|---------|
| **Command** | `BaseAction` | Create, update, delete, state transitions | Required | Required |
| **Read** | None (plain class) | Complex queries, aggregation, dashboards | Never | Never |
| **Process** | `BaseAction` | Multi-step orchestration composing Actions | Required | Required |

## Key References

- **BaseAction**: `app/Core/Actions/BaseAction.php` — provides `transaction()`, `log()`, `HandlesActionErrors`
- **HandlesActionErrors**: `app/Core/Support/HandlesActionErrors.php` — catches unexpected `Throwable`, logs via SmartLogger, re-throws as `RuntimeException`
- **SmartLogger**: `app/Core/Support/SmartLogger.php` — structured logging with PII masking, bilingual descriptions, activity log integration
- **Architecture docs**: `docs/architecture.md#action-triad-command-read-process`
- **Action Pattern**: `docs/architecture/action-pattern.md`

## Command Action Contract

```php
class ApproveReportAction extends BaseAction
{
    public function __construct(
        protected readonly NotifyMentorAction $notifyMentor,
    ) {}

    public function execute(Report $report, ApproveReportData $data): Report
    {
        return $this->transaction(function () use ($report, $data) {
            $report->update([
                'status' => ReportStatus::APPROVED->value,
                'score' => $data->score,
                'feedback' => $data->feedback,
                'graded_by' => auth()->id(),
                'graded_at' => now(),
            ]);

            $this->log('report_approved', $report, ['score' => $data->score]);
            event(new ReportApproved($report, auth()->user()));

            return $report;
        });
    }
}
```

### Rules

- MUST extend `BaseAction` (provides `transaction()`, `log()`, `HandlesActionErrors`)
- MUST wrap all DB operations in `$this->transaction()` — auto-detects nested transactions
- MUST call `$this->log()` after success — writes to both system log and Spatie activity log
- MUST be preceded by a policy check in the calling layer
- MUST NOT contain inline `canX()` checks — delegate to Entities
- MUST throw `RejectedException` for rule violations, never `RuntimeException`
- Constructor uses `protected readonly` promotion for dependencies

## Read Action Contract

```php
class InternshipDashboardReader
{
    public function __construct(protected readonly Internship $model) {}

    public function activeCount(): int
    {
        return $this->model
            ->whereIn('status', [InternshipStatus::PUBLISHED->value, InternshipStatus::ACTIVE->value])
            ->count();
    }
}
```

### Rules

- Plain class with constructor injection — no base class required
- MUST NOT call `transaction()` or `log()`
- SHOULD return typed objects or collections

## Process Action Contract

```php
class RegisterStudentProcess extends BaseAction
{
    public function __construct(
        protected readonly CreateRegistrationAction $createRegistration,
        protected readonly AssignPlacementAction $assignPlacement,
        protected readonly NotifyMentorAction $notifyMentor,
    ) {}

    public function execute(RegisterStudentData $data): Registration
    {
        return $this->transaction(function () use ($data) {
            $registration = $this->createRegistration->execute($data);
            $this->assignPlacement->execute($registration, $data->placementId);
            $this->notifyMentor->execute($registration);

            $this->log('student_registered', $registration);
            event(new StudentRegistered($registration));

            return $registration;
        });
    }
}
```

### Rules

- Extends `BaseAction` (transaction + logging at process level)
- Composes other Actions via constructor injection
- MUST handle partial failure — if step 3 of 5 fails, what happens to steps 1-2?
- MUST NOT duplicate logic that exists in Command Actions

## Workflow: Extracting to an Action

1. Identify inline `Model::create/update/delete`, `DB::transaction()`, `Mail::send()`, `Notification::send()` in the component
2. Create the Action class in `app/{Module}/{SubModule}/Actions/`
3. Move validation into the Action (authoritative — the component validates for UX only)
4. Wrap persistence in `$this->transaction()`
5. Add `$this->log()` for audit trail
6. Dispatch module events for significant state changes
7. Inject the Action into the calling component via method parameter
8. Catch `RejectedException` in the component and display a flash message

## Naming Conventions

- Command: `{Verb}{Entity}Action` — `CreateUserAction`, `ApproveRegistrationAction`
- Read: `{Context}Reader`, `Get{Dashboard}Data`, `{Entity}Query`
- Process: `{Verb}{Entity}Process` — `RegisterStudentProcess`, `CloseInternshipProcess`

## Verification

- One `execute()` method only?
- Extends `BaseAction` (Command/Process) or plain class (Read)?
- Business rules delegated to Entity methods?
- All DB writes wrapped in `$this->transaction()`?
- `$this->log()` called after mutation?
- `RejectedException` for domain violations?
- Static utilities delegated to `Support/` classes?
