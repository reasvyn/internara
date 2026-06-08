<laravel-boost-guidelines>
> Last updated: 2026-06-08

=== foundation rules ===

# Laravel Boost Guidelines

These guidelines are curated by Laravel maintainers for this application. Follow them closely.

## Foundational Context

PHP 8.4, Laravel v13, Livewire v4, Boost v2. Key packages:

- laravel/framework (LARAVEL) — v13
- livewire/livewire (LIVEWIRE) — v4
- laravel/boost (BOOST) — v2
- laravel/pulse (PULSE) — v1
- laravel/pail (PAIL) — v1
- laravel/pint (PINT) — v1
- laravel/sail (SAIL) — v1
- laravel/mcp (MCP) — v0
- laravel/prompts (PROMPTS) — v0
- pestphp/pest (PEST) — v4
- phpunit/phpunit (PHPUNIT) — v12
- tailwindcss (TAILWINDCSS) — v4
- prettier (PRETTIER) — v3

## Architecture

This project uses an **Action-based MVC** architecture. Code follows a strict path convention:

```
# Module-specific (submodule-rooted)
app/{Module}/{Submodule}/{Component}/{ClassName}.php

# Shared (cross-module)
app/{Component}/{ClassName}.php

# Views mirror app structure
resources/views/{module}/{submodule}/{component-name}.blade.php
resources/views/{component}/{component-name}.blade.php  (shared, e.g. views/livewire/)

# Tests mirror app structure
tests/{Feature,Unit}/{Module}/{Submodule}/{Name}Test.php
tests/{Feature,Unit}/{Component}/{Name}Test.php          (shared)
```

**Examples:**

- `app/User/Profile/Actions/UpdateProfileAction.php` → module submodule component
- `app/Data/AuditCheck.php` → shared component directly under `app/`
- `resources/views/user/profile/profile-editor.blade.php`
- `resources/views/livewire/lang-switcher.blade.php`
- `tests/Feature/User/Profile/UpdateProfileActionTest.php`

### Module Directory Layout

```
app/{Module}/
├── {Submodule}/  Submodule-rooted components (Actions, Models, Policies, Livewire)
├── Types/           Shared value objects, flat enums, rules
├── Http/            Cross-submodule controllers, middleware
├── Console/         Cross-submodule artisan commands
├── Livewire/        Cross-submodule UI (dashboards, global components)
├── Support/         Shared module utilities
└── Services/        Infrastructure services
```

All submodule code lives under `app/{Module}/{Submodule}/{Component}/{ClassName}.php`. Shared
(cross-module) code lives under `app/{Component}/{ClassName}.php`.

**No redundant namespace segments.** The class name must never be repeated in the path.

- ✅ `app/User/Models/User.php` (namespace `App\User\Models`)
- ❌ `app/User/User/Models/User.php` — `User` is repeated in the path
- ✅ `app/Program/Internship/Models/Internship.php` (namespace `App\Program\Internship\Models`)
- ❌ `app/Program/Internship/Internship/Models/Internship.php`

Views mirror the submodule name but without `` in the path: `resources/views/auth/password/`,
`resources/views/user/profile/`.

### MANDATORY: Use Core Base Classes

The Core module provides base classes for every layer. You MUST use them:

| Layer | Base Class | Location |
|-------|-----------|----------|
| Model | `BaseModel` (or `Authenticatable`) | `app/Core/Models/BaseModel.php` |
| Action | `BaseAction` | `app/Core/Actions/BaseAction.php` |
| Entity | `BaseEntity` (final readonly) | `app/Core/Entities/BaseEntity.php` |
| Policy | `BasePolicy` | `app/Core/Policies/BasePolicy.php` |
| Livewire CRUD | `BaseRecordManager` | `app/Core/Livewire/BaseRecordManager.php` |
| Controller | `BaseController` | `app/Core/Http/Controllers/BaseController.php` |
| Form Request | `BaseFormRequest` (Core's, not Laravel's) | `app/Core/Http/Requests/BaseFormRequest.php` |
| DTO | `BaseData` | `app/Core/Data/BaseData.php` |
| Exception | `AppException` or `ModuleException` | `app/Core/Exceptions/` |
| Enum | Must implement `LabelEnum` | `app/Core/Contracts/LabelEnum.php` |
| Logging | Use `SmartLogger` | `app/Core/Support/SmartLogger.php` |

Do NOT create custom patterns. These rules are enforced through code review.

### Skills Activation

This project has module-specific skills available in `.agents/skills/`. Activate the relevant
skill whenever you work in that module — don't wait until you're stuck.

### Conventions

- Follow existing code conventions. Check sibling files for correct structure and naming.
- Use descriptive names: `isRegisteredForDiscounts`, not `discount()`.
- Reuse existing components before writing new ones.
- Actions are single-responsibility with one `execute()` method. Livewire delegates business
  logic to Actions.
- Entities are `final readonly` classes with zero framework dependencies. Models expose them via
  `as{EntityName}()` accessors.
- All enums are string-backed and implement `LabelEnum`. State machine enums implement `StatusEnum`.
- UUID primary keys on all models (via BaseModel/HasUuids). Foreign keys use
  `foreignUuid()->constrained()`.

### Verification Scripts

Do not create verification scripts or tinker when tests cover the functionality. Unit and feature
tests are preferred.

### Frontend Bundling

If the user does not see a frontend change, ask them to run `npm run build`, `npm run dev`, or
`composer run dev`.

### Documentation

- Comprehensive docs at `docs/`. Always refer before making changes.
- Module-specific docs at `docs/modules/{module}.md`.
- Architecture and conventions at `docs/architecture.md` and `docs/conventions.md`.

### Replies

Be concise — focus on what is important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

Laravel Boost is an MCP server with tools designed for this application. Prefer Boost tools over
manual alternatives like shell commands or file reads.

- Use `database-query` for read-only SQL queries instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, module, and port for project URLs.
- Use `browser-logs` to read browser errors and exceptions. Only recent logs are useful.

## Searching Documentation (IMPORTANT)

Always use `search-docs` before making code changes. It returns version-specific docs based on
installed packages automatically.

- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries — package info is already shared.

### Search Syntax

1. Words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. `"Quoted phrases"` for exact position matching: `"infinite scroll"`.
3. Combine: `middleware "rate limit"`.
4. Multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`).
- Use `php artisan list` to discover commands and `--help` to check parameters.
- Inspect routes: `php artisan route:list` with `--method`, `--name`, `--path` filters.
- Read config: `php artisan config:show app.name` or read config files directly.
- Check environment variables by reading `.env` directly.

## Tinker

- Execute PHP in app context for debugging. Prefer tests with factories over model creation.
- Prefer existing Artisan commands over custom tinker code.
- Use single quotes to prevent shell expansion:
  `php artisan tinker --execute 'Your::code();'`
- Double quotes for PHP strings inside:
  `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion:
  `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter
  `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters:
  `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex
  logic.
- Use array shape type definitions in PHPDoc blocks.
- All files must begin with `declare(strict_types=1)`.
- Use `protected readonly` promotion for Action constructor dependencies.

=== module invariants ===

# Module Invariants (DO NOT VIOLATE)

- **Super Admin name is ALWAYS `Administrator`** (from config `setup.defaults.admin_name`).
- **Super Admin username is ALWAYS `superadmin`** (from config `setup.defaults.admin_username`).
- These are canonical, non-customizable credentials enforced by `SetupSuperAdminAction` which only
  accepts `(string $email, string $password)` — no name/username parameters.
- Any code that calls `SetupSuperAdminAction::execute()` must NOT pass name or username.
- The `InitializeSuperAdminAction` (CLI recovery) must also use config defaults, NOT caller-provided
  values.
- `FinalizeSetupAction` must only extract `email` and `password` from `adminData` array.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), the fastest way to
  deploy and scale production Laravel applications.
- Ensure queue worker is running: `php artisan queue:work`
- Ensure scheduler is configured:
  `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`
- Storage link must exist: `php artisan storage:link`

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write or update tests, then run them to confirm.
- Run the minimum number of tests needed. Use `php artisan test --compact --filter=testName`.
- Tests follow module-first submodule-based structure:
  `tests/{Feature,Unit}/{Module}/{Submodule}/{Name}Test.php`.
- Shared component tests: `tests/{Feature,Unit}/{Component}/{Name}Test.php`.
- Code review and static analysis (PHPStan) enforce structural rules.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files. List available commands with
  `php artisan list` and check parameters with `--help`.
- For generic PHP classes, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands.
- Place new models inside `app/{Module}/Models/`, not the root `app/Models/`.

### Model Creation

- When creating models, also create useful factories and seeders. Check
  `php artisan make:model --help` for available options.

### APIs & Eloquent Resources

- Default to Eloquent API Resources and API versioning unless existing routes follow a different
  convention. Follow existing application convention.

### URL Generation

- Prefer named routes and the `route()` function for links.

### Testing

- Use factories for test models. Check factory custom states before manual setup.
- Use `$this->faker->word()` or `fake()->randomDigit()` — follow existing convention.
- Create tests with `php artisan make:test [options] {name}`. Pass `--unit` for unit tests. Most
  tests should be feature tests.

### Vite Error

- If you see `Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest`, run
  `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire builds dynamic, reactive interfaces in PHP without JavaScript.
- Use Alpine.js for client-side interactions.
- Keep state server-side so the UI reflects it. Validate and authorize in actions.
- Livewire components are auto-discovered by AppServiceProvider from `app/*/*/Livewire/` and
  `app/*/Livewire/`.
- Component alias pattern (submodule): `{kebab-module}.{kebab-submodule}.{kebab-name}`
  (e.g., `admin.user.user-manager`)
- Component alias pattern (root): `{kebab-module}.{kebab-name}` (e.g., `user.profile-editor`)
- Component alias pattern (shared): `{kebab-component-name}` (e.g., `livewire.lang-switcher`)
- Views for submodule components: `resources/views/{module}/{submodule}/{component-name}.blade.php`
- Views for root components: `resources/views/{module}/{component-name}.blade.php`
- Views for shared components: `resources/views/{component}/{component-name}.blade.php`
- CRUD table components extend `BaseRecordManager`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- After modifying PHP files, run `vendor/bin/pint --format agent` to fix formatting.
- Do not run `vendor/bin/pint --test --format agent`. Simply run `vendor/bin/pint --format agent`.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use
  `php artisan make:test --pest SomeFeatureTest` instead of `... Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.
- Structure tests by module and submodule:
  `tests/Feature/{Module}/{Submodule}/{Name}Test.php` and
  `tests/Unit/{Module}/{Submodule}/{Name}Test.php`.

=== spatie/laravel-medialibrary rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for
  collections, conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads,
  conversions, collections, responsive images, or any code that uses `HasMedia` or
  `InteractsWithMedia`.
- Media collections are defined in the module's Models. Storage is handled through the media
  library's integration with Laravel filesystem.

</laravel-boost-guidelines>
