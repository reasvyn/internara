<laravel-boost-guidelines>
> Last updated: 2026-06-04
> Changes: sync with AGENTS.md — added architecture, module invariants, and base class mandate sections

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application.
These guidelines should be followed closely to ensure the best experience when building Laravel
applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are
below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/pulse (PULSE) - v1
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- prettier (PRETTIER) - v3
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has module-specific skills available in `**/skills/**`. You MUST activate the relevant
skill whenever you work in that module—don't wait until you're stuck.

## Architecture (IMPORTANT)

This project uses an **Action-based MVC** architecture:

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

- Backend: `app/{Module}/{Submodule}/` — submodule-rooted components with colocated layers
- Views: `resources/views/{module}/{submodule}/{component}.blade.php` — Blade views mirror submodule
  structure
- Routes: `routes/web/{module}.php` — routes split by module, master `routes/web.php` requires all
- Tests: `tests/{Feature,Unit}/{Module}/{Submodule}/{Name}Test.php` — tests organized by module and
  submodule

### Directory Convention

All submodule code lives under `app/{Module}/{SubmoduleName}/`. Cross-submodule files (shared
Actions, Http, Console, Livewire, Support, Services) stay at the module root.

Views mirror the submodule name but without
``in the path:`resources/views/auth/password/`, `resources/views/user/profile/`.

### MANDATORY: Use Core Base Classes

The Core module provides base classes for every layer. You MUST use them:

| Layer         | Base Class                            | Location                                       |
| ------------- | ------------------------------------- | ---------------------------------------------- |
| Model         | `BaseModel` (or `Authenticatable`)    | `app/Core/Models/BaseModel.php`                |
| Action        | `BaseAction`                          | `app/Core/Actions/BaseAction.php`              |
| Entity        | `BaseEntity` (final readonly)         | `app/Core/Entities/BaseEntity.php`             |
| Policy        | `BasePolicy`                          | `app/Core/Policies/BasePolicy.php`             |
| Livewire CRUD | `BaseRecordManager`                   | `app/Core/Livewire/BaseRecordManager.php`      |
| Controller    | `BaseController`                      | `app/Core/Http/Controllers/BaseController.php` |
| Form Request  | `FormRequest` (Core's, not Laravel's) | `app/Core/Http/Requests/FormRequest.php`       |
| DTO           | `BaseData`                            | `app/Core/Data/BaseData.php`                   |
| Exception     | `AppException` or `ModuleException`   | `app/Core/Exceptions/`                         |
| Enum          | Must implement `LabelEnum`            | `app/Core/Contracts/LabelEnum.php`             |
| Logging       | Use `SmartLogger`                     | `app/Core/Support/SmartLogger.php`             |

Do NOT create custom patterns. These rules are enforced through code review.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a
  file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not
  `discount()`.
- Check for existing components to reuse before writing a new one.

## Conventions (cont.)

- Actions are single-responsibility classes with one `execute()` method. Livewire components
  delegate all business logic to Actions.
- Entities are `final readonly` classes with zero framework dependencies. Models expose them via
  `as{EntityName}()` accessors.
- All enums are string-backed and implement `LabelEnum`. State machine enums implement `StatusEnum`.
- UUID primary keys on all models (via BaseModel/HasUuids). Foreign keys use
  `foreignUuid()->constrained()`.
- Role-Based Access Control (RBAC):
    - Use standard roles: `super_admin`, `admin`, `student`, `teacher`, `supervisor`.
    - Avoid using "Mentor" in industry contexts; use "Supervisor" instead.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run
  `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious
  details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost
  tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in
  tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, module, and port for project URLs. Always
  use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful,
  ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns
  version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`.
  Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use
  `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words
   in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use
  `php artisan list` to discover available commands and `php artisan [command] --help` to check
  parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`,
  `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`,
  `php artisan config:show database.default`. Or read config files directly from the `config/`
  directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user
  approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker
  code.
- Always use single quotes to prevent shell expansion:
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
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex
  logic.
- Use array shape type definitions in PHPDoc blocks.

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

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest
  way to deploy and scale production Laravel applications.
- Ensure queue worker is running: `php artisan queue:work`
- Ensure scheduler is configured: `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`
- Storage link must exist: `php artisan storage:link`

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then
  run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use
  `php artisan test --compact` with a specific filename or filter.
- Tests follow module-first, submodule-based structure:
  `tests/{Feature,Unit}/{Module}/{Submodule}/{Name}Test.php`.
- Code review and static analysis (PHPStan) enforce structural rules.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.).
  You can list available Artisan commands using `php artisan list` and check their parameters with
  `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should
  also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they
  need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do
  not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom
  states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing
  conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature
  test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest"
  error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allows building dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in
  HTTP requests.
- Livewire components are auto-discovered by AppServiceProvider from `app/*/*/Livewire/` and
  `app/*/Livewire/`.
- Component alias pattern (submodule): `{kebab-module}.{kebab-submodule}.{kebab-name}` (e.g.,
  `admin.user.user-manager`)
- Component alias pattern (root): `{kebab-module}.{kebab-name}` (e.g., `user.profile-editor`)
- Views for submodule components: `resources/views/{module}/{submodule}/{component-name}.blade.php`
- Views for root components: `resources/views/{module}/{component-name}.blade.php`
- CRUD table components extend `BaseRecordManager`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before
  finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to
  fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use
  `php artisan make:test --pest SomeFeatureTest` instead of
  `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.
- Structure tests by module and submodule: `tests/Feature/{Module}/{Submodule}/{Name}Test.php` and
  `tests/Unit/{Module}/{Submodule}/{Name}Test.php`.

=== spatie/laravel-medialibrary rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for collections,
  conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads, conversions,
  collections, responsive images, or any code that uses the `HasMedia` interface or
  `InteractsWithMedia` trait.
- Media collections are defined in the module's Models. Storage is handled through the media
  library's integration with Laravel filesystem.

</laravel-boost-guidelines>
