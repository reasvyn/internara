# Coding Conventions — PHP Rules, Naming & Testing

> **Last updated:** 2026-07-21 **Changes:** sync — replace ConflictException with
> RejectedException in naming examples

## Description

This document describes conventions for writing code in the Internara codebase. These rules exist to
produce consistent, predictable code that any team member can read without context-switching.

**Architecture patterns** (Actions, Entities, Events, Livewire, caching, testing, etc.) live in
`docs/architecture/*-pattern.md`. This file covers only the conventions that are not
pattern-specific: code style, naming, file structure, and project rules.

---

## Table of Contents

- [0. Documentation-First](#0-documentation-first)
- [1. Architecture Patterns](#1-architecture-patterns)
- [2. General PHP](#2-general-php)
- [3. Security Conventions](#3-security-conventions)
  - [3.1 XSS Prevention](#31-xss-prevention)
  - [3.2 SQL Injection Prevention](#32-sql-injection-prevention)
  - [3.3 Mass Assignment Protection](#33-mass-assignment-protection)
  - [3.4 CSRF Protection](#34-csrf-protection)
  - [3.5 Content Security Policy](#35-content-security-policy)
  - [3.6 File Upload Security](#36-file-upload-security)
  - [3.7 Rate Limiting](#37-rate-limiting)
- [4. Naming Conventions](#4-naming-conventions)
- [5. Console Commands](#5-console-commands)
- [6. Performance Conventions](#6-performance-conventions)
  - [6.1 N+1 Query Prevention](#61-n1-query-prevention)
  - [6.2 Query Optimization](#62-query-optimization)
  - [6.3 Eager Loading Convention](#63-eager-loading-convention)
  - [6.4 Resource Cleanup](#64-resource-cleanup)
  - [6.5 Caching Conventions](#65-caching-conventions)
- [7. Migrations, Factories & Seeders](#7-migrations-factories--seeders)
- [8. Cross-Cutting Protocols](#8-cross-cutting-protocols)
- [9. HTTP & API Conventions](#9-http--api-conventions)
- [10. Dependency Injection Conventions](#10-dependency-injection-conventions)
- [11. Code Quality Enforcement](#11-code-quality-enforcement)
- [12. Testing Conventions](#12-testing-conventions)
- [13. Theming & Visual Consistency](#13-theming--visual-consistency)
- [14. Localization](#14-localization)

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
  `modules/index.md`

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

All architecture patterns are documented in `docs/architecture/`. Refer to the appropriate pattern
doc rather than duplicating conventions here:

| Convention                          | Reference                                                                  |
| ----------------------------------- | -------------------------------------------------------------------------- |
| File structure, module organization | [Modular Pattern](architecture/modular-pattern.md)                         |
| Actions (Command / Read / Process)  | [Action Pattern](architecture/action-pattern.md)                           |
| Models & relationships              | [Model Pattern](architecture/model-pattern.md)                             |
| Entities (business rules)           | [Entity Pattern](architecture/entity-pattern.md)                           |
| Enums (LabelEnum, StatusEnum)       | [Enum Pattern](architecture/enum-pattern.md)                               |
| Policies & authorization            | [Policy Pattern](architecture/policy-pattern.md)                           |
| Livewire components                 | [Livewire Pattern](architecture/livewire-pattern.md)                       |
| Data / DTOs                         | [Data Pattern](architecture/data-pattern.md)                               |
| Events & listeners                  | [Event Pattern](architecture/event-pattern.md)                             |
| Notifications                       | [Event Pattern](architecture/event-pattern.md) (see Notifications section) |
| Controllers, routes, middleware     | [Modular Pattern](architecture/modular-pattern.md)                         |
| Blade views & anonymous components  | [Livewire Pattern](architecture/livewire-pattern.md)                       |
| Cache keys & invalidation           | [Cache Pattern](architecture/cache-pattern.md)                             |
| Cross-module communication          | [Modular Pattern](architecture/modular-pattern.md)                         |
| Exception hierarchy & handling      | [Exception Pattern](architecture/exception-pattern.md)                     |
| Testing                             | [Testing Pattern](architecture/testing-pattern.md)                         |
| Logging & SmartLogger               | [Logging Pattern](architecture/logging-pattern.md)                         |
| Services vs Support                 | [Service Pattern](architecture/service-pattern.md)                         |

---

## 2. General PHP

- Curly braces `{ }` required for all control structures (no omitting braces, even for single-line
  bodies).
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

## 3. Security Conventions

### 3.1 XSS Prevention

- Use `{{ $var }}` (double curly braces) for **all** user-supplied content in Blade — Laravel's
  Blade engine automatically escapes HTML entities.
- `{!! $var !!}` (unescaped) is **only** permitted for trusted content that has been explicitly
  sanitized (e.g., markdown-rendered text passed through a whitelist-based HTML purifier).
- Every `{!! $var !!}` occurrence must have an inline comment justifying why it is safe:

    ```blade
    {{-- Safe: rendered from Markdown with HTML purifier --}}
    {!! $content !!}
    ```

- **Never** use `{!! $var !!}` with raw user input (`$_GET`, `$_POST`, `request()->input()`,
  database fields containing user-submitted HTML).
- Alpine.js `x-html` must follow the same rule — only trusted sanitized content.
- Avoid inline `<script>` tags in Blade; use Alpine.js `x-data` and `@entangle` instead.

### 3.2 SQL Injection Prevention

- Raw SQL (`DB::raw()`, `whereRaw()`, `orderByRaw()`, `havingRaw()`, `selectRaw()`) is **forbidden**
  in application code unless:
    1. The SQL uses **parameterized binding** exclusively (`->whereRaw('col = ?', [$value])`).
    2. An explicit exception is documented in the method's docblock.
- Always use Eloquent's query builder or the `where('column', $value)` syntax — these use
  parameterized binding by default.
- Never concatenate user input into query strings:

    ```php
    // ❌ Wrong — SQL injection vector
    User::whereRaw("name = '$input'")->get();

    // ✅ Correct — parameterized
    User::where('name', $input)->get();
    User::whereRaw('name = ?', [$input])->get();
    ```

### 3.3 Mass Assignment Protection

- Every model **must** use the `#[Fillable]` attribute (PHP 8.4). The legacy `$fillable` property is
  not used anywhere in this codebase.
- **Never** pass raw request input to `create()` or `update()`:

    ```php
    // ❌ Wrong — allows mass assignment of any column
    Model::create($request->all());
    Model::create($this->all());

    // ✅ Correct — explicit allowed keys
    Model::create($request->only(['name', 'email']));
    Model::create($this->form->toArray());
    ```

- All mass assignment is routed through `#[Fillable]` + explicit key selection. `$guarded` is not
  used — always whitelist with `#[Fillable]`.

### 3.4 CSRF Protection

- All state-changing HTML forms (`POST`, `PUT`, `PATCH`, `DELETE`) must include `@csrf` or use
  Livewire (which manages CSRF automatically).
- Exempt routes (for webhook handlers, external API callbacks) are listed in `bootstrap/app.php`
  `validateCsrfTokens(except: [...])`. Each exemption must have a code comment explaining why it is
  required.
- API consumers that authenticate via token (not session) are exempt.

### 3.5 Content Security Policy

- The `SecurityHeaders` middleware (applied globally in the `web` group) sets a strict CSP header.
- Inline `<script>` tags are blocked by default. Use Alpine.js `x-data` / `@click` / `x-on` for
  interactivity instead of inline `onclick` handlers.
- External resources (scripts, fonts, images) must be added to the CSP `default-src` / `script-src`
  / `img-src` directives in `SecurityHeaders` before use.
- The CSP is enforced via `Content-Security-Policy` header, not
  `Content-Security-Policy-Report-Only`. Violations break the page — test thoroughly.

### 3.6 File Upload Security

- All file uploads go through Spatie MediaLibrary (`spatie/laravel-medialibrary`), never
  `Storage::put()`.
- Each media collection defines its own validation rules (max file size, allowed MIME types) via
  `registerMediaCollections()`.
- For file type validation, check MIME type server-side (`$file->getMimeType()`), not just the
  extension — a `.jpg` file can contain arbitrary data.
- Generated filenames are sanitized:
  `Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))`.
- Path traversal is prevented automatically by Spatie's internal storage handling.

### 3.7 Rate Limiting

- Authentication endpoints (`login`, `recover-account`) use the `auth.throttle` middleware.
- Setup wizard token validation uses a dedicated rate limiter (`setup:{$ip}`) with configurable
  attempts and decay window (`config/setup.php security.*`).
- Custom rate limiters are defined in `bootstrap/app.php` and referenced by name in route
  middleware.

---

## 4. Naming Conventions

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
| Exception                  | `{Name}Exception`                                             | `RejectedException`, `ValidationFailedException`                    |
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

## 5. Console Commands

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

## 6. Performance Conventions

### 5.1 N+1 Query Prevention

- **Never** access Eloquent relationships inside Blade loops or Livewire `@foreach` without eager
  loading:

    ```blade
    {{-- ❌ Wrong — N+1: each iteration queries the DB --}}
    @foreach ($users as $user)
        {{ $user->profile->bio }}
    @endforeach

    {{-- ✅ Correct — preloaded --}}
    {{-- Controller/Livewire: User::with('profile')->get() --}}
    ```

- Always use `->with()` on the query before iterating. `->load()` on an existing collection is
  acceptable but `with()` is preferred (fewer queries).
- For conditional eager loading, use `->when()` with `with()`:

    ```php
    User::query()->when($loadProfile, fn($q) => $q->with('profile'))->get();
    ```

- Livewire `render()` methods must not trigger N+1. Use `->with()` in the query, never `->load()`
  inside a loop.
- Read Actions and Livewire queries must be audited for N+1 during code review.

### 5.2 Query Optimization

- For large datasets (≥1,000 rows), use `chunk()` or `lazy()` instead of `get()` to avoid memory
  exhaustion:

    ```php
    // Batch processing
    User::chunk(200, function (Collection $users) { ... });

    // Lazy collection (memory-efficient)
    foreach (User::lazy(200) as $user) { ... }
    ```

- Avoid `$collection->filter()` on large Eloquent collections — move the filter to the database:

    ```php
    // ❌ Wrong — loads all rows, filters in PHP memory
    Model::all()->filter(fn($m) => $m->isActive());

    // ✅ Correct — filters at database level
    Model::where('is_active', true)->get();
    ```

- Use `exists()` instead of `count() > 0` for existence checks.
- Use `pluck()` instead of `get()->pluck()` to avoid hydrating full models.
- Index strategy: every `WHERE` / `ORDER BY` / `JOIN` column used in frequent queries must have a
  database index. Composite indexes for multi-column filters.

### 5.3 Eager Loading Convention

- Default: `->with()` for all regular relationships used in the current view/response.
- Lazy eager loading (`->load()`) for optional relationships loaded conditionally.
- Sub-relationship eager loading: `->with(['posts.comments'])`.
- Constrained eager loading: `->with(['comments' => fn ($q) => $q->where('approved', true)])`.
- Avoid `->loadMissing()` in loops — move to `->with()` on the initial query.

### 5.4 Resource Cleanup

- Temporary files created during request processing must be cleaned up in `finally` blocks:

    ```php
    $tempFile = tempnam(sys_get_temp_dir(), 'export_');
    try {
        // ... generate file content
    } finally {
        @unlink($tempFile);
    }
    ```

- Long-running commands and queued jobs must unset large variables after use:

    ```php
    $chunk = Model::lazy(200);
    foreach ($chunk as $row) {
        // process $row
        unset($row); // release memory
    }
    ```

- Open file handles and streams must be closed explicitly (`fclose()`, `curl_close()`).

### 5.5 Caching Conventions

- Every cache key is declared in `config/cache-keys.php` — never inline strings.
- Cache invalidation follows event-driven pattern: Command Action → event → listener →
  `Cache::forget()`.
- TTL categories: Short (<5 min), Medium (5 min–1 h), Long (1–24 h), Forever.
- Use `remember()` for reads, `forget()` for invalidations. Never use `Cache::put()` with a raw key
  unless registering it in `cache-keys.php` first.

---

## 7. Migrations, Factories & Seeders

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
        return $this->state(fn(array $attrs) => ['status' => InternshipStatus::PUBLISHED->value]);
    }
}
```

### Seeders

- Seeders are idempotent — running them multiple times does not duplicate data.
- Seeding order respects module dependencies: school → user → permissions → internships.
- Use `firstOrCreate()` for reference data, `create()` for test data.

---

## 8. Cross-Cutting Protocols

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
4. **Transparent Compliance Stamping** — Documents compiled using proxy weights or proxy scores must
   be stamped with a metadata tag (`proxy_weights` or `proxy_scores`) for audit trails.

---

## 9. HTTP & API Conventions

### 7.1 HTTP Status Code Mapping

| Scenario         | Status Code                 | When                                         |
| ---------------- | --------------------------- | -------------------------------------------- |
| Resource created | `201 Created`               | `POST` returning the new resource            |
| Resource updated | `200 OK`                    | `PUT`/`PATCH` returning the updated resource |
| Resource deleted | `204 No Content`            | `DELETE` — no response body                  |
| List/read        | `200 OK`                    | `GET` returning resource(s)                  |
| Validation error | `422 Unprocessable Entity`  | `ValidationException`                        |
| Unauthorized     | `401 Unauthorized`          | Missing or invalid authentication            |
| Forbidden        | `403 Forbidden`             | Authenticated but not authorized             |
| Not found        | `404 Not Found`             | Resource does not exist                      |
| Conflict         | `409 Conflict`              | Duplicate / state conflict                   |
| Rate limited     | `429 Too Many Requests`     | Rate limiter hit                             |
| Server error     | `500 Internal Server Error` | Unhandled exception                          |

The exception hierarchy status codes (`AppException::statusCode()`) match this table. Controllers
and API endpoints must return the correct status code — never default to `200` for errors.

### 7.2 Error Response Format (JSON)

All JSON error responses follow a consistent envelope:

```json
{
    "message": "Human-readable description",
    "errors": {
        "field_name": ["Validation error 1", "Validation error 2"]
    }
}
```

- `message` is always present.
- `errors` is present only for validation failures (422).
- Non-validation errors omit the `errors` key.
- Paginated responses include `meta` with `current_page`, `last_page`, `per_page`, `total`.

---

## 10. Dependency Injection Conventions

### 8.1 Constructor Injection (Preferred)

Mandatory, long-lived dependencies are injected via constructor property promotion:

```php
public function __construct(
    protected readonly SomeService $service,
    protected readonly AnotherService $another,
) {}
```

This applies to all classes instantiated by the container: Actions, Services, Middleware, Console
Commands, Controllers, Listeners, Repositories.

### 8.2 Method Injection (Contextual)

Dependencies that vary per call or are only needed in one method are injected as method parameters.
This is the standard pattern for Livewire component methods:

```php
public function save(CreateUserAction $action): void
{
    $action->execute($this->form->toArray());
}
```

### 8.3 Forbidden Patterns

- `app()->make(ClassName::class)` — use constructor or method injection instead.
- `new ClassName()` inside Controllers or Livewire components — let the container resolve.
- `resolve(ClassName::class)` — use method injection.
- Static facades for business logic — `User::where(...)` is acceptable for simple queries but
  complex logic must be inside Actions (injected).

The exception: `app()` is permitted in service providers (`boot()`, `register()`) and factory
methods where the container is not available (e.g., `database/factories/`).

---

## 11. Code Quality Enforcement

| Tool             | What It Enforces                                              | How                                         |
| ---------------- | ------------------------------------------------------------- | ------------------------------------------- |
| **Laravel Pint** | PHP code style (PSR-12 + Laravel conventions)                 | `vendor/bin/pint --dirty` before finalizing |
| **PHPStan**      | Static analysis (type safety, dead code, boundary violations) | `vendor/bin/phpstan analyse --no-progress`  |
| **Prettier**     | Markdown, JSON, YAML, Blade formatting                        | `npm run format`                            |
| **Code Review**  | Architecture conventions, pattern compliance, security        | Manual review of every PR                   |

### Pre-commit Checklist (Author)

- [ ] `declare(strict_types=1)` present
- [ ] No `dd()`, `dump()`, `ray()`, `var_dump()`, `print_r()`, `die()` left in code
- [ ] All user-facing strings use `__()` helper
- [ ] Action follows the correct triad pattern (Command/Read/Process)
- [ ] Command/Process Action accepts DTO (for 3+ params) and returns ActionResponse (for structured
      feedback)
- [ ] Cache keys registered in `config/cache-keys.php`
- [ ] No N+1 queries — eager loading verified
- [ ] No unescaped `{!! !!}` for user content
- [ ] Doc metadata updated: Last updated date + one-line Changes description
- [ ] Tests pass: `php artisan test --compact`
- [ ] Pint clean: `vendor/bin/pint --dirty --format agent`
- [ ] PHPStan passes: `vendor/bin/phpstan analyse --no-progress`
- [ ] Relevant docs updated (see §0 Documentation-First)

### Code Review Checklist (Reviewer)

- [ ] **Pattern compliance**: Action uses correct base class, single `execute()`, `transaction()` +
      `log()` for commands, no `transaction()`/`log()` for reads
- [ ] **Security scan**: No XSS vectors (`{!! $userContent !!}`), no SQL injection (`whereRaw()`
      with concatenation), no mass assignment (`create($request->all())`)
- [ ] **N+1 audit**: All relationship accesses in loops have matching `->with()` on the query
- [ ] **Exception handling**: Business rules throw `RejectedException`, not `RuntimeException`;
      Livewire catches `RejectedException` specifically
- [ ] **Cache invalidation**: Every mutation that changes cached data has corresponding
      `Cache::forget()` or event-driven invalidation
- [ ] **Test coverage**: New Actions have test files; existing tests still pass
- [ ] **Documentation**: Module doc or reference doc updated if behavior changed

---

## 12. Testing Conventions

### 10.1 Mocking Strategy

| Scenario                    | Approach                                | Example                                                                      |
| --------------------------- | --------------------------------------- | ---------------------------------------------------------------------------- |
| External HTTP calls         | `Http::fake()`                          | `Http::fake(['api.example.com/*' => Http::response(...)])`                   |
| SmartLogger (unit tests)    | `Event::fake()` or partial mock         | `Mail::fake()`, `Notification::fake()`                                       |
| SmartLogger (feature tests) | Use real implementation                 | Let SmartLogger write to the log — assertions verify via `ActivityLog` model |
| Eloquent models             | Use factories + real database           | Never mock Eloquent — always test against real DB (feature tests)            |
| File system                 | `Storage::fake()`                       | `Storage::fake('local')` → `Storage::disk('local')->assertExists(...)`       |
| Cache                       | `Cache::shouldReceive()` (only in unit) | Prefer `Cache::forget()` assertions via event listeners in feature tests     |
| Queue                       | `Queue::fake()`                         | `Queue::assertPushed(Job::class)`                                            |
| Notifications               | `Notification::fake()`                  | `Notification::assertSentTo(User::class, ...)`                               |
| Events                      | `Event::fake([SpecificEvent::class])`   | Assert event was dispatched with correct payload                             |

**Rules:**

- Never mock Eloquent models or the Query Builder — use real database in feature tests.
- Never use `Mockery::spy()` for assertions — use Laravel's built-in `fake()` methods.
- Mock external boundaries only (HTTP, mail, queue, filesystem, cache).
- Business logic in Actions is tested with real dependencies — only infrastructure boundaries are
  mocked.

### 10.2 Coverage Requirements

| Layer               | Minimum Coverage | Testing Type |
| ------------------- | ---------------- | ------------ |
| Entities            | 100%             | Unit         |
| Enums               | 100%             | Unit         |
| DTOs / Data         | 100%             | Unit         |
| Command Actions     | ≥ 90%            | Feature      |
| Read Actions        | ≥ 80%            | Feature      |
| Process Actions     | ≥ 90%            | Feature      |
| Livewire components | ≥ 80%            | Feature      |
| Policies            | 100%             | Unit         |
| Console Commands    | ≥ 80%            | Feature      |
| **Overall**         | **≥ 85%**        |              |

Coverage is verified via `php artisan test --coverage` or `composer run test:coverage`. A PR that
causes overall coverage to drop below threshold must add missing tests before merging.

### 10.3 Commit & Branch Conventions

**Branch naming:**

| Type     | Pattern                         | Example                          |
| -------- | ------------------------------- | -------------------------------- |
| Feature  | `feat/{kebab-description}`      | `feat/add-internship-export`     |
| Bug fix  | `fix/{issue-short-description}` | `fix/login-redirect-loop`        |
| Hotfix   | `hotfix/{description}`          | `hotfix/critical-security-patch` |
| Refactor | `refactor/{module}-{scope}`     | `refactor/setup-audit-cache`     |
| Docs     | `docs/{what}`                   | `docs/conventions-security`      |
| Chore    | `chore/{task}`                  | `chore/update-dependencies`      |

**Commit message format:**

```
type(scope): description

- Bullet points for details (optional)
- Reference issues: #123
```

| Type       | When                                |
| ---------- | ----------------------------------- |
| `feat`     | New feature                         |
| `fix`      | Bug fix                             |
| `refactor` | Code change with no behavior change |
| `docs`     | Documentation only                  |
| `chore`    | Maintenance, deps, tooling          |
| `test`     | Adding or fixing tests              |
| `perf`     | Performance improvement             |
| `security` | Security fix                        |

Examples:

```
feat(internship): add CSV export for approved registrations

fix(auth): prevent infinite redirect after setup completion

refactor(setup): move dispatchEvent inside transaction callback
```

### 10.4 Technical Debt Annotation

Use these annotations in code comments for tracking technical debt:

| Annotation                             | Meaning                        | Convention              |
| -------------------------------------- | ------------------------------ | ----------------------- |
| `TODO(username, YYYY-MM-DD): message`  | Planned work                   | Include author and date |
| `FIXME(username, YYYY-MM-DD): message` | Known bug                      | Include author and date |
| `HACK`                                 | Suboptimal code that works     | Must explain why        |
| `XXX`                                  | Danger — fragile or risky code | Must explain the risk   |

```php
// TODO(alice, 2026-07-01): Extract this inline query into a Read Action
// HACK: This works but bypasses the standard validation chain because...
```

---

## 13. Theming & Visual Consistency

### CSS Variable Usage

All components MUST use CSS variables from the Settings/Theme module for brand colors:

```blade
{{-- ✅ Correct — uses CSS variable --}}
<div class="bg-[var(--color-primary)]">

{{-- ❌ Wrong — hardcoded color --}}
<div class="bg-blue-500">
```

- Primary, secondary, accent colors from `brand()` helper or CSS variables
- Never hardcode hex colors in Blade or CSS
- Respect dark/light mode via `theme.dark_mode` setting

### Form Field Icons

Every form field MUST include an icon for visual clarity:

| Field Type    | Icon Examples                          |
| ------------- | -------------------------------------- |
| Text input    | `o-user`, `o-envelope`, `o-phone`     |
| Password      | `o-key`, `o-lock-closed`              |
| Date          | `o-calendar`                          |
| Time          | `o-clock`                             |
| File upload   | `o-cloud-arrow-up`                    |
| Search        | `o-magnifying-glass`                  |
| Select/Dropdown | `o-chevron-down`                    |

Icons use the Heroicons system via maryUI. Pair icons with labels — never use icons as the sole
indicator for accessibility.

---

## 14. Localization

### File Structure

Each module and submodule gets its own flat translation file under `lang/{locale}/`. No
subdirectories. Modules may optionally split submodule keys into separate files.

| Scope           | File                                                | Example Keys                                                                                                       |
| --------------- | --------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| Module root     | `{module}.php`                                      | `auth.php` → `__('auth.login.*')`, `__('auth.forgot_password.*')`                                                  |
| Submodule       | `{submodule}.php`                                   | `login.php` → `__('login.failed')`, `__('login.throttle')`                                                        |
| Shared utility  | `common.php`                                        | Global labels: `__('common.actions.save')`, `__('common.yes')`, `__('common.status.*')`                            |
| Domain-specific | `{domain}.php`                                      | `activity.php` → `__('activity.login_success')` (dynamic DB lookup), `notifications.php` → `__('notifications.*')` |
| Framework       | `passwords.php`, `pagination.php`, `validation.php` | Laravel built-in files — keep as shipped                                                                           |

Submodule files are colocated in the same `lang/{locale}/` directory — no subdirectories. Both
`en` and `id` copies must exist for every submodule file. See
`docs/infrastructure/localization.md` §Submodule Translation Files for details.

### `__()` Usage Rules

1. **Every** user-facing string must use `__()` — never hardcode display text.
2. Exception: layout strings in Blade that are pure markup labels (e.g., `<label>Name</label>`
   without a dynamic counterpart) may remain untranslated if the value is self-explanatory in
   English.
3. Parameters use `:param` syntax: `__('user.welcome', ['name' => $user->name])`.

### Cross-Referencing

- `__('module.key')` resolves to `lang/{locale}/module.php` key `key`.
- Both `en` and `id` locales must always be present.
- When adding a new key, add it to both `lang/en/{file}.php` and `lang/id/{file}.php`.

### When a Key Belongs to Multiple Modules

Keys used across module boundaries belong in one of the shared domain files:

| File                | Contents                                                            |
| ------------------- | ------------------------------------------------------------------- |
| `common.php`        | Truly global labels: `yes`, `no`, `actions`, `status`, field labels |
| `notifications.php` | Notification messages — used by multiple modules                    |
| `activity.php`      | Activity log descriptions — dynamically resolved from DB            |
| `log.php`           | SmartLogger event names — cross-cutting audit trail                 |

Do not duplicate keys across files.

### Dynamic Keys

Some keys are resolved dynamically and cannot be renamed without affecting database values:

```php
{{ __("activity.{$activity->description}") }}
```

The database stores `login_success`, `user_created`, etc. as activity descriptions. These keys must
remain in `activity.php` under their original names.
