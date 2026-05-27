# ADR-003: Action Pattern over Service Classes

## Status
Accepted

## Context

Business operations need a home. Two competing patterns exist in the Laravel ecosystem:

1. **Service classes**: A class with multiple public methods representing related operations
   (e.g., `RegistrationService` with `register()`, `approve()`, `reject()`, `withdraw()`).
2. **Action classes**: One class per operation, each with a single public `execute()` method
   (e.g., `RegisterStudentAction`, `ApproveRegistrationAction`, `RejectRegistrationAction`).

Service classes tend to grow over time — what starts as a 3-method service becomes a 20-method
god class with mixed responsibilities. They are difficult to test (one test file must cover all
methods), difficult to decorate (cross-cutting concerns like logging apply to the whole class),
and encourage shared mutable state between related operations.

Action classes keep each operation isolated. However, treating all operations as the same kind
of action is also insufficient. The system performs three fundamentally different kinds of
operations:

- **Mutations** — writes that create, update, or delete state. These need transactions,
  logging, and error handling.
- **Reads** — queries that retrieve and aggregate data without changing state. These need
  neither transactions nor logging.
- **Orchestrations** — multi-step workflows that coordinate multiple mutations and reads.
  These need transaction management at the process level, not at each individual step.

Treating all three as identical action classes forces reads to carry unnecessary transaction
overhead and forces mutations to skip orchestration boundaries.

## Decision

Business operations are organized into three distinct action types, all living under
`app/Domain/{Domain}/Actions/` and following the single `execute()` method convention.

### 1. Command Actions (Mutations)

**Purpose:** Every write to the system. Create, update, delete, transition state,
send notifications, upload files.

**Base class:** `BaseAction` (provides `transaction()`, `log()`, `HandlesActionErrors`)

**Contract:**
- MUST wrap all database operations in `$this->transaction()`
- MUST call `$this->log()` after successful mutation
- SHOULD dispatch domain events for significant state changes
- MUST be preceded by a policy check in the calling layer

**Naming:** `{Verb}{Entity}Action` — `RegisterStudentAction`, `ApproveRegistrationAction`

**Example:**
```php
class SubmitLogbookAction extends BaseAction
{
    public function execute(Logbook $entry, array $data): Logbook
    {
        return $this->transaction(function () use ($entry, $data) {
            $entry->update([
                'content' => $data['content'],
                'status' => LogbookStatus::SUBMITTED->value,
            ]);

            $this->log('logbook_submitted', $entry);

            event(new LogbookSubmitted($entry));

            return $entry;
        });
    }
}
```

### 2. Read Actions (Queries)

**Purpose:** Complex read operations that involve aggregation, filtering, authorization,
or cross-domain data assembly. Not for simple `Model::find()` or `Model::where()` —
those stay in Livewire components.

**Base class:** None required. A plain class with constructor injection. May use
`HandlesActionErrors` from BaseAction but MUST NOT call `transaction()` or `log()`.

**Contract:**
- MUST NOT mutate any database state
- MUST NOT call `transaction()` or `log()` from BaseAction
- SHOULD return typed objects or collections, never raw arrays
- MUST pass through authorization (unless the calling layer already authorized)

**Naming:** `{Context}Reader`, `Get{Dashboard}Data`, `{Entity}Query`

**Example:**
```php
class InternshipDashboardReader
{
    public function __construct(
        protected readonly Internship $model,
    ) {}

    public function activeCount(): int
    {
        return $this->model->whereIn('status', [
            InternshipStatus::PUBLISHED->value,
            InternshipStatus::ACTIVE->value,
        ])->count();
    }

    public function recentRegistrations(int $days = 7): Collection
    {
        return Registration::where('created_at', '>=', now()->subDays($days))
            ->with('mentee.user', 'internship')
            ->limit(20)
            ->get();
    }
}
```

### 3. Process Actions (Orchestration)

**Purpose:** Multi-step workflows that coordinate multiple Command and Read Actions.
Process Actions exist when a single use case requires multiple mutations, conditional
branching, or external service calls.

**Base class:** `BaseAction` (same as Command, with transaction + logging at the process level).

**Contract:**
- MUST compose other Actions via constructor injection
- MUST handle partial failure — if step 3 of 5 fails, what happens to steps 1–2?
- SHOULD emit a single domain event representing the completed process
- MUST NOT duplicate business logic that already exists in Command Actions

**Naming:** `{Verb}{Entity}Process` — `RegisterStudentProcess`, `CloseInternshipProcess`

**Example:**
```php
class CloseInternshipProcess extends BaseAction
{
    public function __construct(
        protected readonly CheckCloseReadinessAction $checkReadiness,
        protected readonly FinalizeAssessmentsAction $finalizeAssessments,
        protected readonly IssueCertificatesAction $issueCertificates,
        protected readonly ArchiveProgramAction $archiveProgram,
    ) {}

    public function execute(Internship $program): void
    {
        $this->transaction(function () use ($program) {
            $readiness = $this->checkReadiness->execute($program);
            if (! $readiness->allPassed()) {
                throw RejectedException::withHint(
                    'Cannot close program: readiness checks failed'
                );
            }

            $this->finalizeAssessments->execute($program);
            $this->issueCertificates->execute($program);
            $this->archiveProgram->execute($program);

            $this->log('program_closed', $program);
            event(new ProgramClosed($program));
        });
    }
}
```

### Decision Table

| Scenario | Pattern | Base Class | Transaction | Logging | Event |
|---|---|---|---|---|---|
| Create a record | Command | `BaseAction` | ✅ Required | ✅ Required | ✅ Recommended |
| Update a record | Command | `BaseAction` | ✅ Required | ✅ Required | ✅ Recommended |
| Delete a record | Command | `BaseAction` | ✅ Required | ✅ Required | ✅ Recommended |
| State transition | Command | `BaseAction` | ✅ Required | ✅ Required | ✅ Required |
| Send notification | Command | `BaseAction` | ✅ Required | ✅ Required | ❌ |
| Simple list query | Inline in Livewire | None | ❌ | ❌ | ❌ |
| Complex aggregated query | Read Action | None | ❌ | ❌ | ❌ |
| Dashboard statistics | Read Action | None | ❌ | ❌ | ❌ |
| Multi-step orchestration | Process | `BaseAction` | ✅ Required | ✅ Required | ✅ Required |

## Consequences

- **Positive**: Each action type has a contract that matches its actual needs — mutations
  have transactions and logging, reads do not carry that overhead.
- **Positive**: The three types mirror CQRS without the infrastructure cost. Same models,
  same database — different class contracts.
- **Positive**: Process Actions solve the coordination problem that previously forced
  orchestration logic into Livewire components or into single Actions that violated
  single responsibility.
- **Positive**: Each action is independently testable. Test files map 1:1 with action classes.
  Read Actions need no database setup for simple tests.
- **Positive**: Cross-cutting concerns (transactions, logging, error handling) are centralized
  in `BaseAction` for Commands and Processes — individual actions only write business logic.
- **Negative**: Three patterns to learn instead of one. Developers must distinguish between
  Command, Read, and Process when creating new actions.
- **Negative**: Existing codebase has ~150 Actions written before this triad was formalized.
  Some reads still extend `BaseAction` unnecessarily. Gradual migration needed.
- **Negative**: Some very simple Read Actions may feel like boilerplate compared to inline
  Eloquent queries in Livewire. The distinction is: if the query is complex enough to need
  a named, testable, reusable class, make it a Read Action.

## Migration Path

Existing codebase has ~150 Actions written before the Command/Read/Process split. Migration
is gradual and non-breaking:

1. Identify Read Actions that extend `BaseAction` but never call `transaction()` or `log()`.
   These can be converted to plain classes immediately.
2. Identify Process Actions that call other Actions via constructor injection. Formalize
   their process-level contracts.
3. New Actions follow the triad from day one.

## References

- `app/Domain/Core/Actions/BaseAction.php` — base class for Command and Process Actions
- `app/Domain/Core/Support/HandlesActionErrors.php` — error handling trait
- `docs/architecture.md` — Action Triad section
- `docs/conventions.md` — Section 5 (Actions)
