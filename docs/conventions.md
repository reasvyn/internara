# Coding Conventions

> **Last updated:** 2026-06-17
> **Changes:** sync — add curly braces rule to §2 General PHP; fix pre-commit checklist missing print_r/die; fix broken ADR-014 link

This document describes conventions for writing code in the Internara codebase. These rules exist to
produce consistent, predictable code that any team member can read without context-switching.

**Architecture patterns** (Actions, Entities, Events, Livewire, caching, testing, etc.) live in
`docs/architecture/*-pattern.md`. This file covers only the conventions that are not pattern-specific:
code style, naming, file structure, and project rules.

---

## 0. Documentation-First

### Documentation as Single Source of Truth

Documentation is the **authoritative reference** for the system. It defines what the system does,
how it is structured, and why decisions were made. Code implements what documentation describes —
not the other way around. When documentation and implementation disagree, documentation is the SSOT
and implementation must be corrected.

### Document First, Then Implement

Every change — feature, refactor, bug fix — begins with documentation. Before writing a single line
of code, the relevant docs must be updated to describe the intended outcome. This applies at all
scales:

- **New feature** → document the feature in `key-features.md`, update the module's conceptual doc
  (`{module}.md`) and API reference (`{module}-reference.md`)
- **Architecture change** → update `architecture.md` and any affected ADRs
- **Bug fix** → if the fix changes behavior, update the affected docs
- **Refactor** → if the refactor moves code between modules, update both modules' docs and
  `module-index.md`

Implementation follows documentation. The docs describe the target state; code catches up.

### Two Documentation Tiers

Each module has two documents serving different audiences:

| Document                             | Audience                             | Content                                                                                         |
| ------------------------------------ | ------------------------------------ | ----------------------------------------------------------------------------------------------- |
| `docs/modules/{module}.md`           | Architects, developers, stakeholders | Purpose, design principles, module boundary — pure conceptual design, no implementation details |
| `docs/modules/{module}-reference.md` | Developers, reviewers                | Full API reference — file paths, class names, table schemas, dependency graphs                  |

When describing a module's behavior, write the conceptual doc. When listing files or classes, write
the reference doc. Never mix implementation details into conceptual docs.

### Documentation Updates as Part of Definition of Done

A change is not complete until the relevant documentation is updated. This is enforced through code
review — a PR that changes code without corresponding doc updates is incomplete and must not be
merged.

---

## 1. Architecture Patterns

All architecture patterns are documented in `docs/architecture/`. Refer to the appropriate
pattern doc rather than duplicating conventions here:

| Convention                          | Reference                                          |
| ----------------------------------- | -------------------------------------------------- |
| File structure, module organization | [Modular Pattern](architecture/modular-pattern.md) |
| Actions (Command / Read / Process)  | [Action Pattern](architecture/action-pattern.md)   |
| Models & relationships              | [Model Pattern](architecture/model-pattern.md)     |
| Entities (business rules)           | [Entity Pattern](architecture/entity-pattern.md)   |
| Enums (LabelEnum, StatusEnum)       | [Enum Pattern](architecture/enum-pattern.md)       |
| Policies & authorization            | [Policy Pattern](architecture/policy-pattern.md)   |
| Livewire components                 | [Livewire Pattern](architecture/livewire-pattern.md) |
| Data / DTOs                         | [Data Pattern](architecture/data-pattern.md)       |
| Events & listeners                  | [Event Pattern](architecture/event-pattern.md)     |
| Notifications                       | [Event Pattern](architecture/event-pattern.md) (see Notifications section) |
| Controllers, routes, middleware     | [Modular Pattern](architecture/modular-pattern.md) |
| Blade views & anonymous components  | [Livewire Pattern](architecture/livewire-pattern.md) |
| Cache keys & invalidation           | [Cache Pattern](architecture/cache-pattern.md)     |
| Cross-module communication          | [Modular Pattern](architecture/modular-pattern.md) |
| Exception hierarchy & handling      | [Exception Pattern](architecture/exception-pattern.md) |
| Testing                             | [Testing Pattern](architecture/testing-pattern.md) |
| Logging & SmartLogger               | [Logging Pattern](architecture/logging-pattern.md) |
| Services vs Support                 | [Service Pattern](architecture/service-pattern.md) |

---

## 2. General PHP

- Curly braces `{ }` required for all control structures (no omitting braces, even for single-line bodies).
- `declare(strict_types=1)` in every file except migrations and config.
- Constructor property promotion: `public function __construct(protected readonly X $x) {}`. Do not
  leave empty zero-parameter constructors unless private.
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

| Element                    | Convention                                                    | Example                                                               |
| -------------------------- | ------------------------------------------------------------- | --------------------------------------------------------------------- |
| Submodule directory        | Singular `{Name}` (submodule root concept)                    | `User`, `Profile`, `Internship`, `Placement`                          |
| Types directory            | `Types/` for small value objects                              | `Types/Gender.php`, `Types/BloodType.php`                             |
| Model                      | Singular `{Name}`                                             | `User`, `AcademicYear`, `Internship`                                  |
| Command Action             | `{Verb}{Entity}Action`                                        | `CreateUserAction`, `ApproveRegistrationAction`                       |
| Read Action                | `Read{Entity}Action`                                          | `ReadTeacherDashboardAction`, `ReadActivityLogAction`                 |
| Process Action             | `Process{Entity}Action`                                       | `ProcessRegistrationAction`, `ProcessReportFinalizationAction`        |
| Entity                     | `{Name}`                                                      | `Apprentice`, `InternshipPeriod`, `RegistrationState`, `SchoolEntity` |
| Data / DTO                 | `{Verb}{Entity}Data` or `{Entity}Data` (extending `BaseData`) | `SetupTokenData`, `AuditCheck`                                        |
| Livewire                   | `{Name}` — suffixed with Manager, Editor, Center              | `UserManager`, `ProfileEditor`, `RegistrationCenter`                  |
| Livewire alias (submodule) | `{kebab-module}.{kebab-submodule}.{kebab-name}`               | `admin.user.user-manager`                                             |
| Livewire alias (root)      | `{kebab-module}.{kebab-name}`                                 | `user.profile-editor`                                                 |
| Livewire Form              | `{Entity}Form` (extending `Livewire\Form`)                    | `AcademicYearForm`, `SchoolForm`                                      |
| Policy                     | `{Name}Policy`                                                | `UserPolicy`, `InternshipPolicy`                                      |
| Exception                  | `{Name}Exception`                                             | `ConflictException`, `ValidationFailedException`                      |
| Controller                 | `{Name}Controller`                                            | `DashboardController`, `ReportController`                             |
| Middleware                 | `{Name}Middleware`                                            | `CheckRoleMiddleware`, `SetLocaleMiddleware`                          |
| Event                      | `{Entity}{Actioned}` — past tense                             | `InternshipCreated`, `ReportApproved`, `StudentRegistered`            |
| Listener                   | `{Verb}{Entity}` or react to event name                       | `NotifyAdminsInternshipCreated`, `LogSetupFinalized`                  |
| Notification               | `{Entity}{NotificationType}Notification`                      | `InternshipCreatedNotification`, `WelcomeNotification`                |
| Console command            | `{module}:{action}`                                           | `system:health`, `admin:recover`, `notifications:prune`               |
| Route name                 | `{prefix}.{resource}.{action}`                                | `admin.users.index`, `internship.reports.show`                        |
| Config key                 | `snake_case` with `{file}.{key}`                              | `app.name`, `database.default`                                        |
| Column / table             | `snake_case`                                                  | `user_id`, `academic_year_id`, `academic_years`                       |
| Boolean method             | `is`/`has`/`can`/`should` prefix                              | `isActive()`, `allowsLogin()`, `canTransitionTo()`                    |
| Test method                | Pest `it()` with descriptive string                           | `it('creates a user with valid data')`                                |
| Test file                  | `{Name}Test.php`                                              | `CreateUserActionTest.php`, `UserManagerTest.php`                     |
| Factory                    | `{Name}Factory`                                               | `UserFactory`, `InternshipFactory`                                    |
| Migration                  | `YYYY_MM_DD_HHMMSS_create_{table}_table.php`                  | `2026_04_29_092750_create_users_table.php`                            |

---

## 4. Console Commands

- Command signature follows `{module}:{action}` naming.
- Use verb-noun pairs: `system:health`, `admin:recover`, `notifications:prune`.
- Arguments use curly braces: `{email?}`, `{--force}`.
- Use Laravel's `Command` base class (not a Core base class).
- Commands live in the owning module's `Console/Commands/` directory.

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

## 5. Migrations, Factories & Seeders

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
        return $this->state(
            fn(array $attrs) => ['status' => InternshipStatus::PUBLISHED->value],
        );
    }
}
```

### Seeders

- Seeders are idempotent — running them multiple times does not duplicate data.
- Seeding order respects module dependencies: school → user → permissions → internships.
- Use `firstOrCreate()` for reference data, `create()` for test data.

---

## 6. Cross-Cutting Protocols

### Cross-Role Proxy Protocol

Internara implements a **Cross-Role Proxy** mechanism (see [ADR-014](adr/adr-cross-role-proxy.md))
that allows teachers to act as proxy for supervisors, and admins to proxy for both teachers and
supervisors — all at the application layer without multi-role assignment.

1. **Actions & Parameters Optionality** — Any Action that performs verification or sign-off must
   accept nullable parameters or have proxy paths if the industry supervisor is unavailable.
2. **Inactivity Window (Journals/Attendance)** — Reflective logbooks and attendances support a
   teacher proxy after an inactivity window (default: 48 hours). The Command Action must transition
   to `FINALIZED` / `VERIFIED`, record `proxy_role = 'supervisor'` in the activity log, and clear
   the supervisor's pending queue.
3. **Grading & Rubric Proxy** — End-of-placement evaluations support proxy entry and weight
   redistribution via the Cross-Role Proxy gate when the supervisor has not submitted scores.
4. **Transparent Compliance Stamping** — Documents compiled using proxy weights or proxy scores
   must be stamped with a metadata tag (`proxy_weights` or `proxy_scores`) for audit trails.

---

## 7. Code Quality Enforcement

| Tool             | What It Enforces                                              | How                                         |
| ---------------- | ------------------------------------------------------------- | ------------------------------------------- |
| **Laravel Pint** | PHP code style (PSR-12 + Laravel conventions)                 | `vendor/bin/pint` before finalizing changes |
| **PHPStan**      | Static analysis (type safety, dead code, boundary violations) | `vendor/bin/phpstan analyse`                |
| **Prettier**     | Markdown, JSON, YAML, Blade formatting                        | `npm run format`                            |
| **Code Review**  | Architecture conventions, pattern compliance                  | Manual review of every PR                   |

### Pre-commit Checklist

- [ ] `declare(strict_types=1)` present
- [ ] No `dd()`, `dump()`, `ray()`, `var_dump()`, `print_r()`, `die()` left in code
- [ ] All user-facing strings use `__()` helper
- [ ] Action follows the correct triad pattern (Command/Read/Process)
- [ ] Cache keys registered in `config/cache-keys.php`
- [ ] Tests pass: `php artisan test --compact`
- [ ] Pint clean: `vendor/bin/pint --dirty --format agent`

---

## 8. Localization

### File Structure

Each module and submodule gets its own flat translation file under `lang/{locale}/`. No subdirectories.

| Scope | File | Example Keys |
|---|---|---|
| Module root | `{module}.php` | `auth.php` → `__('auth.login.*')`, `__('auth.forgot_password.*')` |
| Submodule | `{submodule}.php` | `superadmin.php` → `__('superadmin.create.*')`, `__('superadmin.field_*')` |
| Shared utility | `common.php` | Global labels: `__('common.actions.save')`, `__('common.yes')`, `__('common.status.*')` |
| Domain-specific | `{domain}.php` | `activity.php` → `__('activity.login_success')` (dynamic DB lookup), `notifications.php` → `__('notifications.*')` |
| Framework | `passwords.php`, `pagination.php`, `validation.php` | Laravel built-in files — keep as shipped |

### `__()` Usage Rules

1. **Every** user-facing string must use `__()` — never hardcode display text.
2. Exception: layout strings in Blade that are pure markup labels (e.g., `<label>Name</label>` without
   a dynamic counterpart) may remain untranslated if the value is self-explanatory in English.
3. Parameters use `:param` syntax: `__('user.welcome', ['name' => $user->name])`.

### Cross-Referencing

- `__('module.key')` resolves to `lang/{locale}/module.php` key `key`.
- Both `en` and `id` locales must always be present.
- When adding a new key, add it to both `lang/en/{file}.php` and `lang/id/{file}.php`.

### When a Key Belongs to Multiple Modules

Keys used across module boundaries belong in one of the shared domain files:

| File | Contents |
|---|---|
| `common.php` | Truly global labels: `yes`, `no`, `actions`, `status`, field labels |
| `notifications.php` | Notification messages — used by multiple modules |
| `activity.php` | Activity log descriptions — dynamically resolved from DB |
| `log.php` | SmartLogger event names — cross-cutting audit trail |

Do not duplicate keys across files.

### Dynamic Keys

Some keys are resolved dynamically and cannot be renamed without affecting database values:

```php
{{ __("activity.{$activity->description}") }}
```

The database stores `login_success`, `user_created`, etc. as activity descriptions. These keys must
remain in `activity.php` under their original names.
