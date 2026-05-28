# Coding Conventions
> Last updated: 2026-05-27
> Changes: docs: comprehensive infrastructure, architecture, and conventions overhaul


This document describes conventions for writing code in the Internara codebase. These rules
exist to produce consistent, predictable code that any team member can read without
context-switching.

Conventions are organized from foundational (base classes, structure) to specific
(commands, tests, cache keys). Each section includes a rationale and examples.

---

## 0. Mandatory Base Classes

Every architectural layer has exactly one base class from Core. There is no alternative.

| Layer | Base Class | Provides |
|---|---|---|
| Model | `BaseModel` | UUID PK (`HasUuids`), non-incrementing, string key type |
| Action (Command / Process) | `BaseAction` | `transaction()`, `log()`, `HandlesActionErrors` |
| Action (Read) | None required | Read operations don't need transaction or logging |
| Entity | `BaseEntity` | `final readonly`, `fromModel(Model): static` contract |
| State entity | `BaseState` (extends BaseEntity) | `isState()`, `isStateIn()` for state-machine helpers |
| Policy | `BasePolicy` | `AuthorizesRoles`, `AuthorizesOwnership` traits |
| Livewire CRUD | `BaseRecordManager` | Search, filter, sort, pagination, bulk actions |
| Controller | `BaseController` | Cross-cutting HTTP concerns |
| Form Request | `FormRequest` (Core's) | Consistent `ValidationFailedException` on failure |
| Enum | Implements `LabelEnum` | `label(): string` |
| Status enum | Implements `StatusEnum` (+ LabelEnum) | `canTransitionTo()`, `validTransitions()`, `isTerminal()` |
| Exception | Extends `AppException` or `DomainException` | `HasExceptionContext` trait |
| Cache key | `CacheKeys` constant | Centralized key registry, prevents collisions |
| DTO | `Data` (from `Core/Data/Data.php`) | `toArray()`, `fromArray()`, `from()` |

### Exceptions

- **User model**: Cannot extend `BaseModel` (requires `Authenticatable`). Must manually apply
  `HasUuids` and override `getIncrementing()` / `getKeyType()` for UUID consistency.
- **Notifications**: Extend `Illuminate\Notifications\Notification`, not a Core base class.

---

## 1. File Structure

```
app/Domain/{Domain}/
├── Actions/         → Command, Read, Process — 1 class = 1 use case
├── Models/          → Eloquent persistence layer
├── Livewire/        → Reactive UI components
│   └── Forms/       → Form Objects for complex forms (optional)
├── Policies/        → Authorization gates
├── Enums/           → Constants with behavior (LabelEnum, StatusEnum)
├── Entities/        → Business rules without framework dependencies
├── Data/            → DTOs for typed input/output (optional, gradual)
├── Http/            → Controllers & middleware (optional, Livewire-first)
│   ├── Controllers/
│   └── Middleware/
├── Notifications/   → Mail, database, broadcast alerts (optional)
├── Events/          → Domain events emitted (optional, gradual)
├── Listeners/       → Event subscribers (optional, gradual)
├── Console/         → Artisan commands (optional)
├── Support/         → Pure utility classes, no Eloquent (optional)
└── Contracts/       → Domain interfaces (optional)
```

### Services vs Support

| Directory | Purpose | Example |
|---|---|---|
| `Support/` | Pure utility classes, no Eloquent, no framework dependencies | `Theme`, `CsvHandler`, `PiiMasker` |
| `Services/` | Framework-aware infrastructure code | `EnvironmentAuditor`, `PulseGuard` |

Prefer `Support/` for stateless utilities. Use `Services/` only when the class depends on
framework services (container, config, facades) and does not fit the Action pattern.

---

## 2. General PHP

- `declare(strict_types=1)` in every file except migrations and config.
- Constructor property promotion: `public function __construct(protected readonly X $x) {}`.
  Do not leave empty zero-parameter constructors unless private.
- Explicit return types on every method: `function isAccessible(User $user): bool`.
- Type hints on all parameters: `function find(string $id): ?Model`.
- `===` over `==` unless loose comparison is intentional.
- Trailing commas on multiline arrays, function calls, constructor params.
- `__()` for all user-facing strings. Never hardcode display text.
- No `dd()`, `dump()`, `ray()`, `var_dump()`, `print_r()`, `die()` in committed code.
- Use `match()` instead of long `switch()` blocks when returning a value from an expression.
- `str_contains()` / `str_starts_with()` / `str_ends_with()` over `strpos() === 0`.
- Null-safe operator `?->` and null coalescing `??` over explicit null checks.
- Readonly properties prefer promoted constructor parameters over `#[Readonly]`.

---

## 3. Naming Conventions

| Element | Convention | Example |
|---|---|---|
| Model | Singular `{Name}` | `User`, `AcademicYear`, `Internship` |
| Command Action | `{Verb}{Entity}Action` | `CreateUserAction`, `ApproveRegistrationAction` |
| Read Action | `{Context}Reader`, `Get{Dashboard}Data`, `{Entity}Query` | `InternshipDashboardReader`, `GetStudentStatsData` |
| Process Action | `{Verb}{Entity}Process` | `RegisterStudentProcess`, `CloseInternshipProcess` |
| Entity | `{Name}` | `Apprentice`, `InternshipPeriod`, `RegistrationState` |
| Data / DTO | `{Verb}{Entity}Data` or `{Entity}Data` | `CreateInternshipData`, `ApproveReportData` |
| Livewire | `{Name}` — suffixed with Manager, Editor, Center | `UserManager`, `ProfileEditor`, `RegistrationCenter` |
| Livewire Form | `{Entity}Form` | `AcademicYearForm`, `SchoolForm` |
| Policy | `{Name}Policy` | `UserPolicy`, `InternshipPolicy` |
| Enum | `{Name}` | `AccountStatus`, `InternshipStatus`, `Role` |
| Enum case | `UPPER_SNAKE` | `SUPER_ADMIN`, `DRAFT`, `PENDING_REVIEW` |
| Controller | `{Name}Controller` | `DashboardController`, `ReportController` |
| Middleware | `{Name}Middleware` | `CheckRoleMiddleware`, `SetLocaleMiddleware` |
| Event | `{Entity}{Actioned}` — past tense | `InternshipCreated`, `ReportApproved`, `StudentRegistered` |
| Listener | `{Verb}{Entity}` or react to event name | `NotifyAdminsInternshipCreated`, `LogSetupFinalized` |
| Notification | `{Entity}{NotificationType}Notification` | `InternshipCreatedNotification`, `WelcomeNotification` |
| Console command | `{domain}:{action}` | `system:health`, `admin:recover`, `notifications:prune` |
| Route name | `{prefix}.{resource}.{action}` | `admin.users.index`, `internship.reports.show` |
| Config key | `snake_case` with `{file}.{key}` | `app.name`, `database.default` |
| Column / table | `snake_case` | `user_id`, `academic_year_id`, `academic_years` |
| Boolean method | `is`/`has`/`can`/`should` prefix | `isActive()`, `allowsLogin()`, `canTransitionTo()` |
| Test method | Pest `it()` with descriptive string | `it('creates a user with valid data')` |
| Test file | `{Name}Test.php` | `CreateUserActionTest.php`, `UserManagerTest.php` |
| Factory | `{Name}Factory` | `UserFactory`, `InternshipFactory` |
| Migration | `YYYY_MM_DD_HHMMSS_create_{table}_table.php` | `2026_04_29_092750_create_users_table.php` |

---

## 4. Models

- Extend `BaseModel` (UUID PK, `HasUuids`, non-incrementing, string key type).
  Exception: `User` extends `Authenticatable` with manual `HasUuids`.
- Use `#[Fillable([...])]` attribute for mass assignment, never `$fillable` property.
- Use `HasFactory` trait on every model.
- Use `#[Appends([...])]` for computed accessors.
- Use `#[Cast([...])]` or `protected $casts` for attribute casting.

### Relationships

| Type | Method Name | Example |
|---|---|---|
| `BelongsTo` / `HasOne` | Singular | `user()`, `academicYear()` |
| `HasMany` / `BelongsToMany` | Plural | `users()`, `registrations()` |
| `MorphTo` | Singular | `verifiable()` |
| `MorphMany` | Plural | `comments()` |

Always define the inverse relationship. Use `->foreignUuid()->constrained()` in migrations.

### Entity Accessors

Expose entities via specific named accessors, never a generic `entity()`:

```php
// ✅ Correct
public function asInternshipPeriod(): InternshipPeriod
public function asInternshipState(): InternshipState

// ❌ Wrong
public function entity(): InternshipPeriod
```

### Factory Method

```php
protected static function newFactory(): InternshipFactory
{
    return InternshipFactory::new();
}
```

---

## 5. Actions: Command, Read, Process

Actions are the single entry point for business operations. There are three types, each
with a distinct contract.

### 5a. Command Actions (Mutations)

**Purpose:** Every write to the system — create, update, delete, state transitions.

**Base class:** `BaseAction` (provides `transaction()`, `log()`, `HandlesActionErrors`).

**Contract:**
- Single public `execute()` method. Never add a second public method.
- MUST wrap all database operations in `$this->transaction()`.
- MUST call `$this->log()` after successful mutation.
- SHOULD dispatch a domain event for significant state changes.
- MUST be preceded by a policy check in the calling layer.
- Constructor dependencies use `protected readonly` promotion.

**Naming:** `{Verb}{Entity}Action` — `CreateUserAction`, `ApproveRegistrationAction`.

**Example:**
```php
class SubmitLogbookAction extends BaseAction
{
    public function __construct(
        protected readonly NotifyMentorAction $notifyMentor,
    ) {}

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

### 5b. Read Actions (Queries)

**Purpose:** Complex read operations — aggregation, filtering, cross-domain data assembly.
Not for simple `Model::find()` or `Model::where()` — those stay in Livewire.

**Base class:** None required. A plain class with constructor injection. May use
`HandlesActionErrors` from `BaseAction` but MUST NOT call `transaction()` or `log()`.

**Contract:**
- MUST NOT mutate any database state.
- MUST NOT call `transaction()` or `log()`.
- SHOULD return typed objects or collections, never raw arrays.
- MUST pass through authorization unless the calling layer already authorized.

**Naming:** `{Context}Reader`, `Get{Dashboard}Data`, `{Entity}Query`.

**Example:**
```php
class InternshipProgressReader
{
    public function __construct(
        protected readonly Internship $model,
    ) {}

    public function completionStats(Internship $program): array
    {
        $total = $this->model->registrations()->count();
        $completed = $this->model->registrations()
            ->whereHas('certificates')
            ->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'completion_rate' => $total > 0 ? round($completed / $total * 100, 1) : 0,
        ];
    }
}
```

### 5c. Process Actions (Orchestration)

**Purpose:** Multi-step workflows that coordinate multiple Command and Read Actions.

**Base class:** `BaseAction` (same as Command — transaction + logging at the process level).

**Contract:**
- MUST compose other Actions via constructor injection.
- MUST handle partial failure — what happens to steps 1–2 if step 3 fails?
- SHOULD emit a single domain event representing the completed process.
- MUST NOT duplicate business logic that already exists in Command Actions.

**Naming:** `{Verb}{Entity}Process` — `RegisterStudentProcess`, `CloseInternshipProcess`.

**Example:**
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

### Action Decision Reference

| Scenario | Pattern | Base Class | Transaction | Logging | Event |
|---|---|---|---|---|---|
| Create/update/delete | Command | `BaseAction` | ✅ Required | ✅ Required | ✅ Recommended |
| State transition | Command | `BaseAction` | ✅ Required | ✅ Required | ✅ Required |
| Send notification | Command | `BaseAction` | ✅ Required | ✅ Required | ❌ |
| Simple list query | Inline in Livewire | None | ❌ | ❌ | ❌ |
| Complex aggregated query | Read Action | None | ❌ | ❌ | ❌ |
| Dashboard statistics | Read Action | None | ❌ | ❌ | ❌ |
| Multi-step orchestration | Process | `BaseAction` | ✅ Required | ✅ Required | ✅ Required |

---

## 6. Entities

- `final readonly` class extending `BaseEntity`.
- Zero framework dependencies — no Eloquent, no Facades, no Container.
- All state injected via constructor. No methods that query the database.
- Bridge from persistence via `fromModel(Model): static`.
- Named accessors on models: `asRegistrationState()`, `asInternshipPeriod()`.
- Business logic methods only — no persistence, no HTTP, no I/O.
- State machine entities extend `BaseState` (adds `isState()`, `isStateIn()`).

```php
final readonly class RegistrationState extends BaseEntity
{
    public function __construct(
        public string $status,
        public ?string $placementId,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            status: $model->status,
            placementId: $model->placement_id,
        );
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pending' && $this->placementId !== null;
    }
}
```

**Rationale:** Entities are the testable core of business logic. No database, no mocking,
no setup — just `new RegistrationState(...)` and assert.

---

## 7. Enums

- All enums are `string`-backed.
- All implement `LabelEnum` (provides `label(): string`).
- State machine enums additionally implement `StatusEnum` (provides `canTransitionTo()`,
  `isTerminal()`, `validTransitions()`).
- Optionally implement `ColorableEnum` (provides `color(): string` for UI badges).
- Business logic methods live directly on the enum, not in a separate class.

### Case Convention

Enum cases use `UPPER_SNAKE`. The backing string value stays lowercase:

```php
enum InternshipStatus: string implements StatusEnum
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
```

### Model $attributes Default

Use the enum's `->value` property, never a hardcoded string:

```php
// ✅ Correct
protected $attributes = [
    'status' => InternshipStatus::DRAFT->value,
];

// ❌ Wrong — hardcoded string drifts from enum
protected $attributes = [
    'status' => 'draft',
];
```

---

## 8. Policies

- Extend `BasePolicy` (provides `AuthorizesRoles` and `AuthorizesOwnership` traits).
- Auto-discovered from `app/Domain/*/Policies/` by `DomainServiceProvider`. Convention:
  `{Model}Policy` in the same domain as `{Model}`.
- Cross-domain policies (where a policy gates a model from another domain) must be
  registered manually in `DomainServiceProvider::boot()`.
- `super_admin` bypasses all gates via `Gate::before()`.

```php
class AcademicYearPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, AcademicYear $year): bool
    {
        return $user->hasRole('super_admin');
    }
}
```

---

## 9. Livewire Components

- CRUD table components extend `BaseRecordManager` (provides search, filter, sort,
  pagination, selection, bulk actions).
- Simple page components extend `Component`.
- Components delegate all writes to Command Actions.
- Components delegate complex queries to Read Actions.
- Computed properties use the `#[Computed]` attribute.
- Views live in `resources/views/{domain}/{component-name}.blade.php`.

### Form Objects

Complex forms MUST be extracted into `app/Domain/{Domain}/Livewire/Forms/{Name}Form.php`:

```php
class AcademicYearForm extends Form
{
    public string $name = '';
    public string $start_date = '';
    public string $end_date = '';

    public function rules(?string $excludeId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ];
    }
}
```

**Rules:**
- Form Objects extend `Livewire\Form`, not `BaseAction`.
- Naming: `{Entity}Form` — `UserForm`, `InternshipForm`.
- All form state, validation rules, and `toArray()` logic live inside the Form Object.
- Form Objects validate via explicit `$form->validate()` in the parent component.
- Form Objects must NOT call Actions directly — they prepare data for the component
  to dispatch.

---

## 10. Data / DTOs

DTOs are optional but recommended for Action inputs that have stabilized (3+ parameters
or multiple callers). They live in `app/Domain/{Domain}/Data/`.

- Extend `App\Domain\Core\Data\Data`.
- `final readonly` class with typed constructor parameters.
- Use `Data::fromArray()` during migration for backward compatibility.

```php
final readonly class CreateInternshipData extends Data
{
    public function __construct(
        public string $name,
        public string $startDate,
        public string $endDate,
        public InternshipStatus $status = InternshipStatus::DRAFT,
        public ?string $academicYearId = null,
    ) {}
}
```

**Migration path:**
```
Phase 1 — execute(array $data)            → rapid development
Phase 2 — execute(Data|array $data)       → accepts both (union type)
Phase 3 — execute(Data $data)             → DTO only (final)
```

---

## 11. Events & Listeners

Events decouple side effects from core business logic. They are optional but encouraged
when a Command Action triggers multiple downstream reactions.

### Event Conventions

- Event classes are lightweight DTOs with `public readonly` properties.
- Use the `Dispatchable` trait or `final readonly` class.
- Events belong to the domain that emits them.
- Event naming: `{Entity}{PastTenseAction}` — `InternshipCreated`, `ReportApproved`.

```php
final readonly class InternshipCreated
{
    use Dispatchable;

    public function __construct(
        public readonly Internship $internship,
        public readonly ?User $createdBy = null,
    ) {}
}
```

### Listener Conventions

- Listeners implement `ShouldQueue` for non-critical side effects.
- Listeners can live in any domain.
- Listener naming: describe what the listener does — `NotifyAdminsInternshipCreated`,
  `InvalidateDashboardCache`, `LogSetupFinalized`.

```php
class NotifyAdminsInternshipCreated implements ShouldQueue
{
    public function handle(InternshipCreated $event): void
    {
        $admins = User::role(['super_admin', 'admin'])->get();
        Notification::send($admins, new InternshipCreatedNotification(
            internshipName: $event->internship->name,
        ));
    }
}
```

### Registration

Listeners are registered in `DomainServiceProvider::boot()`:
```php
Event::listen(
    SetupFinalized::class,
    [LogSetupFinalized::class, 'handle'],
);
```

---

## 12. Notifications

- Extend `Illuminate\Notifications\Notification`.
- Implement `ShouldQueue` for channel delivery via queue worker.
- Define channels via the `via()` method.
- Use `CustomDatabaseChannel::class` for in-app database notifications.
- Naming: `{Entity}{NotificationType}Notification` — `InternshipCreatedNotification`.

```php
class InternshipCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $internshipName,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'broadcast', CustomDatabaseChannel::class];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.internship_created.mail_subject'))
            ->line(__('notifications.internship_created.mail_line1', [
                'name' => $this->internshipName,
            ]));
    }

    public function toCustomDatabase($notifiable): array
    {
        return [
            'type' => 'internship_created',
            'title' => __('notifications.internship_created.title'),
            'message' => __('notifications.internship_created.database', [
                'name' => $this->internshipName,
            ]),
            'link' => '/admin/internships',
        ];
    }
}
```

---

## 13. Controllers & Routes

### Controllers

- Controller suffix required: `DashboardController`, `ReportController`.
- Controllers delegate to Actions — no business logic in controller methods.
- Controllers are optional — prefer Livewire components for interactive pages.

### Routes

- Routes are split by domain in `routes/web/{domain}.php`.
- Master `routes/web.php` requires them in dependency order.
- All routes use `->name()`: `Route::get(...)->name('admin.users.index')`.
- Route naming: `{prefix}.{resource}.{action}`.
- Route model binding: use explicit binding with `Route::bind()` or `@` notation.

### Middleware Groups

```php
Route::middleware(['auth', 'auth.throttle'])->group(function () { ... });
Route::middleware(['auth', 'role:super_admin|admin'])->group(function () { ... });
Route::middleware(['guest', 'auth.throttle'])->group(function () { ... });
```

- `auth` — requires authentication.
- `guest` — redirects authenticated users away.
- `role:{roles}` — checks user role via `CheckRoleMiddleware`.
- `auth.throttle` — rate limiting for auth endpoints.
- `verified` — requires email verification (optional, configurable).

---

## 14. Console Commands

- Command signature follows `{domain}:{action}` naming.
- Use verb-noun pairs: `system:health`, `admin:recover`, `notifications:prune`.
- Arguments use curly braces: `{email?}`, `{--force}`.
- Use Laravel's `Command` base class (not a Core base class).
- Commands live in the owning domain's `Console/Commands/` directory.

```php
class HealthCommand extends Command
{
    protected $signature = 'system:health
        {--json : Output results as JSON}';

    protected $description = 'Perform a comprehensive system health check';

    public function handle(): int
    {
        // ...
        return Command::SUCCESS;
    }
}
```

---

## 15. Blade Views

- Livewire views: `resources/views/{domain}/{component-name}.blade.php`.
- Anonymous components: `x-shared::layouts.*`, `x-shared::ui.*`, `x-shared::widgets.*`.
- `@props()` declaration at the top of every component template.
- maryUI components prefixed with `x-mary-`.
- Layouts: `x-shared::layouts.app` (authenticated), `x-shared::layouts.guest` (public).
- Domain-specific layouts in `resources/views/{domain}/layouts/`.

---

## 16. Migrations, Factories & Seeders

### Migrations

- Naming: `YYYY_MM_DD_HHMMSS_create_{table}_table.php`.
- All foreign keys use `foreignUuid()->constrained('{table}')`.
- Follow UUID FK with explicit `onDelete()` / `onUpdate()` behavior:
  - `cascadeOnDelete()` — child cannot exist without parent
  - `onDelete('set null')` — relationship is optional
  - `onDelete('restrict')` — deletion should be prevented
- Composite indexes for common query patterns: `->index(['user_id', 'date'])`.
- Each migration file handles one table or one logical change.
- Indices are created explicitly, not relying on FK auto-indexing.

```php
Schema::create('attendances', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignUuid('registration_id')->constrained('registrations')->cascadeOnDelete();
    $table->date('date');
    $table->time('clock_in');
    $table->time('clock_out')->nullable();
    $table->string('status');
    $table->foreignUuid('verified_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('verified_at')->nullable();
    $table->index(['user_id', 'date']);
    $table->timestamps();
});
```

### Factories

- One factory per model, extending `Illuminate\Database\Eloquent\Factories\Factory`.
- Define model-specific states via `state()` methods.
- Factory naming: `{Name}Factory` — `InternshipFactory`, `UserFactory`.

```php
class InternshipFactory extends Factory
{
    protected $model = Internship::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonths(6),
            'status' => InternshipStatus::DRAFT->value,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => InternshipStatus::PUBLISHED->value,
        ]);
    }
}
```

### Seeders

- Seeders are idempotent — running them multiple times does not duplicate data.
- Seeding order respects domain dependencies: school → user → permissions → internships.
- Use `firstOrCreate()` for reference data, `create()` for test data.

---

## 17. Cache Keys

- Every cache key MUST be declared as a constant in `App\Domain\Core\Support\CacheKeys`.
- Naming: `{domain}.{purpose}[.{qualifier}]`.
- TTL is documented in a comment next to the constant.
- Invalidation trigger is documented in a comment.

```php
final readonly class CacheKeys
{
    /** Invalidation: SetupFinalized event */
    public const string SETUP_INSTALLED = 'setup.is_installed';

    /** TTL: 5 minutes. Invalidation: manual flush */
    public const string ADMIN_DASHBOARD_STATS = 'admin.dashboard.stats';

    /** TTL: forever. Invalidation: Settings update */
    public const string THEME_CSS_VARIABLES = 'theme.css_variables';

    /** Key pattern: notification.unread:{userId} */
    public const string NOTIFICATION_UNREAD = 'notification.unread:';
}
```

---

## 18. Cross-Domain Communication

No domain may import another domain's Models, Actions, or Livewire components directly.
Four communication patterns are allowed, listed from most to least preferred:

### 1. Core Contracts (Layer 3)

Shared interfaces in `App\Domain\Core\Contracts\`. Any domain implements them, any domain
consumes them via the container.

```php
// Core/Contracts/SendsNotifications.php
interface SendsNotifications
{
    public function execute(string $userId, string $type, string $title, ?string $message = null): mixed;
}

// Consumption in any domain
public function __construct(
    protected readonly SendsNotifications $notifications,
) {}
```

### 2. Domain Events (Layer 9)

A Command Action dispatches an event; listeners in any domain react.

```
Internship\Actions\CreateInternshipAction
  → event(new InternshipCreated(...))
    → Internship\Listeners\NotifyAdmins (same domain)
    → Admin\Listeners\InvalidateDashboardCache (different domain)
```

### 3. Action Delegation (Process Actions Only)

Only Process Actions may call other domains' Actions via constructor injection.

### 4. What is NOT Allowed

| ❌ Violation | Correct Alternative |
|---|---|
| `Internship\Models\Internship` imports `School\Models\AcademicYear` | Use AcademicYearId value object, query via School domain |
| `Internship\Policies\CompanyPolicy` gates `Partnership\Models\Company` | Define policy in `Partnership\Policies`, register in `DomainServiceProvider` |
| Livewire component calls `OtherDomain\Models\X::where(...)` | Use Read Action in the target domain |

---

## 19. Testing

### File Structure

Tests mirror source structure:

```
tests/Feature/{Domain}/{Name}Test.php        → Integration tests (Actions, Livewire)
tests/Unit/{Domain}/{Layer}/{Name}Test.php   → Pure unit tests (Entities, Enums, Data)
```

### Naming

```php
// Feature test — describe the Action/Component being tested
describe('CreateInternshipAction', function () {
    it('creates an internship with active academic year', function () { ... });
    it('creates an internship without academic year', function () { ... });
});

// Unit test — describe the Entity or Enum
describe('AccountStatus', function () {
    it('prevents login for suspended accounts', function () { ... });
    it('allows transition from activated to verified', function () { ... });
});
```

### Feature Tests

- Test Command Actions in isolation: factory → execute → assert database/state.
- Test Process Actions: complete workflow + partial failure scenarios.
- Test Livewire components: render → interact → assert state/redirect.
- Use `LazilyRefreshDatabase` for test isolation.
- Do NOT test Eloquent relationships or model scopes directly — test through Actions.

### Unit Tests

- Entities: `new Entity(...)` → assert business rule methods.
- Enums: assert `label()`, transition rules, terminal states.
- Data DTOs: construct via constructor or `fromArray()` → assert `toArray()`.
- Policies: mock user/model → assert boolean gate methods.

### What NOT to Test

- Eloquent model relationships (framework behavior, test through feature tests).
- Simple getters/setters on models.
- Configuration loading.
- Framework-provided functionality (UUID generation, pagination, etc.).

---

## 20. Code Quality Enforcement

| Tool | What It Enforces | How |
|---|---|---|
| **Laravel Pint** | PHP code style (PSR-12 + Laravel conventions) | `vendor/bin/pint` before finalizing changes |
| **PHPStan** | Static analysis (type safety, dead code, boundary violations) | `vendor/bin/phpstan analyse` |
| **Prettier** | Markdown, JSON, YAML, Blade formatting | `npm run format` |
| **Code Review** | Architecture conventions, pattern compliance | Manual review of every PR |

### Pre-commit Checklist

- [ ] `declare(strict_types=1)` present
- [ ] No `dd()`, `dump()`, `var_dump()`, `ray()` left in code
- [ ] All user-facing strings use `__()` helper
- [ ] Action follows the correct triad pattern (Command/Read/Process)
- [ ] Cross-domain imports are via allowed patterns only (contracts, events, delegation)
- [ ] Cache keys registered in `CacheKeys` constants
- [ ] Tests pass: `php artisan test --compact`
- [ ] Pint clean: `vendor/bin/pint --dirty --format agent`
