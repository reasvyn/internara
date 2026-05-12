# Coding Conventions

This document describes the coding conventions used throughout the Internara codebase. Follow these when writing or reviewing code.

## Table of Contents

1. [General PHP](#1-general-php)
2. [File Structure](#2-file-structure)
3. [Naming Conventions](#3-naming-conventions)
4. [Models](#4-models)
5. [Actions](#5-actions)
6. [Entities](#6-entities)
7. [Enums](#7-enums)
8. [Policies](#8-policies)
9. [Livewire Components](#9-livewire-components)
10. [Notifications](#10-notifications)
11. [Exceptions](#11-exceptions)
12. [Controllers & Routes](#12-controllers--routes)
13. [Blade Views](#13-blade-views)
14. [CSS & Tailwind](#14-css--tailwind)
15. [Database Migrations](#15-database-migrations)
16. [Factories](#16-factories)
17. [Tests](#17-tests)
18. [Configuration Files](#18-configuration-files)
19. [Architecture Rules](#19-architecture-rules)

---

## 1. General PHP

### Strict Types

Every PHP file must begin with `declare(strict_types=1)`:

```php
<?php

declare(strict_types=1);

namespace App\Domain\Layer;
```

Migration files may omit `declare(strict_types=1)`.

### Type Hints

Every parameter and return value must have an explicit type hint. Mixed return types must be documented with PHPDoc.

```php
public function execute(string $name, int $limit = 10): Collection
```

### Nullable Types

Use `?Type` syntax rather than `null|Type`:

```php
protected ?string $hint = null;
```

### Single Quotes

Use single quotes for strings unless interpolation or escape sequences are needed:

```php
return 'permission::role.'.$this->value;
```

### Strict Comparison

Always use strict comparison (`===`, `!==`) unless comparing non-strict is intentionally needed.

### Trailing Commas

Use trailing commas in multiline arrays, function calls, and method arguments:

```php
public function __construct(
    protected readonly LogAuditAction $logAuditAction,
    protected readonly UserService $userService,
) {}
```

---

## 2. File Structure

### File Header Order

The exact order in every PHP file:

1. `<?php`
2. Blank line
3. `declare(strict_types=1);`
4. Blank line
5. `namespace App\{Layer}\{SubNamespace};`
6. Blank line
7. `use` statements (alphabetically ordered)
8. Blank line
9. Class/enum/interface/trait declaration

### Import Ordering

Imports are alphabetically ordered and grouped: constants first, then classes/interfaces, then functions:

```php
use App\Actions\Core\LogAuditAction;
use App\Models\User;
use App\Support\SmartLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
```

### Class Member Order

Within a class, members appear in this order:

1. Class attributes (`#[Fillable]`, `#[Hidden]`, `#[Appends]`)
2. Class declaration
3. `extends` and `implements`
4. `use` (traits)
5. Constants
6. Properties
7. Constructor
8. Public methods
9. Protected methods
10. Private methods

---

## 3. Naming Conventions

### Files & Directories

| Layer | Pattern | Example |
|---|---|---|
| Action | `{Verb}{Noun}Action.php` | `CreateUserAction.php` |
| Entity | `{Name}.php` | `Apprentice.php` |
| Model | `{Name}.php` | `User.php` |
| Livewire | `{Name}.php` | `UserManager.php` |
| Policy | `{Name}Policy.php` | `UserPolicy.php` |
| Enum | `{Name}.php` | `AccountStatus.php` |
| Notification | `{Name}Notification.php` | `WelcomeNotification.php` |
| Test | `{Name}Test.php` | `UserManagerTest.php` |
| Request | `{Verb}{Noun}Request.php` | `CreateAssignmentRequest.php` |
| Migration | `YYYY_MM_DD_HHMMSS_create_{table}_table.php` | `YYYY_MM_DD_HHMMSS_create_users_table.php` |

### PHP Identifiers

| Element | Convention | Examples |
|---|---|---|
| Namespace | PascalCase, dot-separated subdirectories | `App\Livewire\User\Admin` |
| Class | PascalCase | `BaseModel`, `SmartLogger` |
| Interface | PascalCase | `LabelEnum`, `ColorableEnum` |
| Trait | PascalCase | `HasFactory`, `AuthorizesRoles` |
| Enum | PascalCase (name), UPPER_SNAKE (cases) | `AccountStatus::VERIFIED` |
| Method | camelCase | `execute()`, `isActive()`, `canTransitionTo()` |
| Property | camelCase | `$isRead`, `$search` |
| Variable | camelCase | `$user`, `$validatedData` |
| Constant | UPPER_SNAKE | `CACHE_PREFIX` |
| Config key | snake_case | `'activity_model'` |
| Column/table | snake_case | `user_id`, `login_history` |
| Route name | dot-separated | `admin.users.index` |

### Boolean Methods

Boolean-returning methods use `is`, `has`, `can`, `should` prefixes:

```php
public function allowsLogin(): bool
public function isTerminal(): bool
public function hasRole(string $role): bool
public function canTransitionTo(self $target): bool
public function shouldReport(): bool
```

---

## 4. Models

### Base Class

All business models extend `BaseModel` which provides UUIDs, non-incrementing keys, and string key type:

```php
abstract class BaseModel extends Model
{
    use HasUuids;

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }
}
```

The `User` model extends `Authenticatable` directly and applies `HasUuids` on its own.

### Mass Assignment Attributes

Use PHP 8 `#[Fillable]` attributes on the class. The older `$fillable` property is not used.

```php
#[Fillable(['name', 'email', 'password'])]
class User extends Authenticatable
{
    // ...
}
```

`#[Hidden]` is used only on the `User` model for sensitive fields.

### Factory Trait

Every model uses the `HasFactory` trait:

```php
use HasFactory;
```

The factory is resolved by Laravel's convention (matching `Database\Factories\ModelNameFactory`). When a custom factory is needed, define `newFactory()`:

```php
protected static function newFactory(): SpecificFactory
{
    return SpecificFactory::new();
}
```

Do not use the `#[Factory]` attribute.

### Relationship Methods

Use singular for `BelongsTo`/`HasOne`, plural for `HasMany`/`BelongsToMany`:

```php
public function user(): BelongsTo
public function profiles(): HasMany
public function roles(): BelongsToMany
```

Explicit foreign keys when the name differs from convention:

```php
return $this->belongsTo(User::class, 'evaluator_id');
```

### Domain Bridge Methods

Models expose their entities through named accessor methods. The pattern is `as{EntityName}()` — never a generic `entity()` method:

```php
public function asNotificationStatus(): NotificationStatus
{
    return NotificationStatus::fromModel($this);
}
```

### Casts

Both property and method style casts are used:

```php
// Property style
protected $casts = [
    'is_read' => 'boolean',
    'data' => 'array',
];

// Method style (preferred for new code)
protected function casts(): array
{
    return [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];
}
```

---

## 5. Actions

### Structure

Actions are single-purpose classes with one `execute()` method:

```php
class CreateUserAction
{
    use HandlesActionErrors;

    public function __construct(
        protected readonly LogAuditAction $logAuditAction,
    ) {}

    public function execute(string $name, string $email): User
    {
        // Validate, perform business logic, audit, return
    }
}
```

### Dependencies

Constructor dependencies use `protected readonly` promotion. Actions are resolved through the container.

### Error Handling

The `HandlesActionErrors` trait wraps callbacks with try-catch, logging, and rethrow as `RuntimeException`. Use it for operations that should never silently fail.

### Transactions

Wrap database mutations in `DB::transaction()`:

```php
return DB::transaction(function () use ($data) {
    $user = User::create($data);
    $this->logAuditAction->execute(...);
    return $user;
});
```

### Naming

Actions are named `{Verb}{Noun}Action`:

```php
CreateUserAction, UpdateProfileAction, VerifyRegistrationAction, DeleteInternshipAction
```

---

## 6. Entities

### Structure

Entities are `final readonly` classes extending `BaseEntity`:

```php
final readonly class Apprentice extends BaseEntity
{
    public function __construct(
        private AccountStatus $status,
        private bool $isLocked,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            status: AccountStatus::tryFrom($model->latestStatus()?->name ?? ''),
            isLocked: $model->locked_at !== null,
        );
    }

    public function isSuspended(): bool
    {
        return $this->status === AccountStatus::SUSPENDED;
    }
}
```

### Rules

- No framework dependencies (no Eloquent, no Facades)
- Only bridge to the ORM is the static `fromModel()` factory
- No `Illuminate\Database\Eloquent\Model` import except in `BaseEntity`
- Pure business logic methods only
- Testable without a database

---

## 7. Enums

### Definition

All enums are string-backed and implement `LabelEnum`:

```php
enum Role: string implements LabelEnum
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';

    public function label(): string
    {
        return __('permission::role.'.$this->value);
    }
}
```

Enums that need colors also implement `ColorableEnum`.

### Business Logic Methods

Enums contain business rule methods directly:

```php
public function allowsLogin(): bool
public function isTerminal(): bool
public function canTransitionTo(self $target): bool
public function validTransitions(): array
public function color(): string
```

---

## 8. Policies

### Structure

All policies extend `BasePolicy`:

```php
class UserPolicy extends BasePolicy
{
    public function viewAny(User $user): bool { ... }
    public function view(User $user, User $model): bool { ... }
    public function create(User $user): bool { ... }
    public function update(User $user, User $model): bool { ... }
    public function delete(User $user, User $model): bool { ... }
}
```

### Shared Traits

`BasePolicy` bundles two concerns:

- `AuthorizesOwnership` — ownership verification helpers
- `AuthorizesRoles` — role-checking helpers (`isAdmin()`, `isTeacher()`, `isStudent()`, `isSupervisor()`)

### Gate Registration

The `super_admin` role bypasses all Gate checks via `Gate::before` in `AppServiceProvider` or TestCase:

```php
Gate::before(function ($user, $ability) {
    return $user->hasRole('super_admin') ? true : null;
});
```

---

## 9. Livewire Components

### Base Classes

Simple components extend `Component`. CRUD table components extend `BaseRecordManager`.

`BaseRecordManager` provides: search, filtering, sorting, record selection, pagination, and toast notifications.

### Attributes

Use `#[Computed]` for computed properties, `#[Layout]` for page layout, and `#[Validate]` for property validation:

```php
#[Computed]
public function filteredUsers(): Collection
{
    return User::query()->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))->get();
}
```

### Layout Naming

Layouts use double-colon syntax referencing anonymous component paths:

```php
#[Layout('layouts::app', ['title' => 'Dashboard'])]
```

Registered paths in `AppServiceProvider`:
- `resources/views/layouts` → `layouts::`
- `resources/views/components/ui` → `ui::`

### Properties

Properties are typed public:

```php
public string $search = '';
public bool $showModal = false;
```

### View Path

Rendered views follow the pattern `livewire.{domain}.{component-name}`:

```php
public function render(): View
{
    return view('livewire.user.admin.user-manager');
}
```

---

## 10. Notifications

### Structure

All notifications implement `ShouldQueue` and use `Queueable`:

```php
class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $password,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'broadcast', CustomDatabaseChannel::class];
    }

    public function toMail($notifiable): MailMessage { ... }
    public function toBroadcast($notifiable): array { ... }
    public function toCustomDatabase($notifiable): array { ... }
}
```

### Constructor

Constructor parameters use `public` promotion (not `private` or `protected`).

### Channels

Every domain notification routes through three channels: `mail`, `broadcast`, `CustomDatabaseChannel::class`.

---

## 11. Exceptions

### Hierarchy

All exceptions extend `AppException`:

```
AppException (abstract, extends RuntimeException)
├── ActionException
├── DomainException
├── InfrastructureException
└── PresentationException
```

### Methods

`AppException` provides:

- `withHint(string $hint): static` — set a user-friendly hint
- `withContext(array $context): static` — add debug context
- `getHint(): ?string`
- `getContext(): array`
- `toCliOutput(): string` — CLI-formatted output
- `isUserFacing(): bool`
- `shouldReport(): bool`

---

## 12. Controllers & Routes

### Controllers

Controller class names end with `Controller` suffix. Controllers are thin — they delegate all business logic to Actions.

Controllers must not import Action or Model classes (enforced by architecture tests).

### Routes

All routes have named routes using `->name()`:

```php
Route::livewire('/users', UserManager::class)->name('admin.users');
Route::get('/users/{user}', [UserController::class, 'show'])->name('admin.users.show');
```

Route names use dot-separated hierarchical naming: `{prefix}.{resource}.{action}`.

### Middleware

- `auth` — authenticated sessions
- `guest` — non-authenticated
- `setup.protected` — setup wizard flow
- `role:{role1|role2}` — role-based gating (pipe-delimited OR)

---

## 13. Blade Views

### Component Namespacing

Two anonymous component paths are registered:

- `resources/views/layouts/` → `x-layouts::*`
- `resources/views/components/ui/` → `x-ui::*`

### Props

Declare all props at the top of the file using `@props`:

```blade
@props([
    'title' => null,
    'header' => null,
])
```

### maryUI Components

Structural UI uses Mary components with `x-mary-*` prefix:

```blade
<x-mary-table :headers="$headers" :rows="$users" />
<x-mary-input wire:model.live="search" placeholder="Search..." />
<x-mary-badge :value="$status->label()" :class="$status->color()" />
<x-mary-toast />
```

### Translation

Use `__()` for all user-facing strings. Translation keys follow dot notation: `domain.key`, `domain.subkey.key`.

### Livewire Directives

- `wire:model.live.debounce.300ms` for search inputs
- `wire:navigate` for SPA-style page transitions
- `wire:click` for event handlers

---

## 14. CSS & Tailwind

### Framework

Tailwind CSS v4 with CSS-first configuration. Import via:

```css
@import 'tailwindcss';
@plugin "daisyui";
@source "../../vendor/robsontenorio/mary/...";
```

### Dark Mode

Dark mode uses a custom variant, not Tailwind's default `dark:` class:

```css
@custom-variant dark (&:where(.dark, .dark *));
```

Toggle by adding/removing the `.dark` class on the `<html>` element.

### Theme

DaisyUI themes (light and dark) are defined in `resources/css/app.css` using OKLCH colors. The light theme is default; dark activates on `prefers-dark`.

### Typography

Instrument Sans is self-hosted and configured as the default sans-serif font:

```css
@theme {
    --font-sans: 'Instrument Sans', 'system-ui', ...;
}
```

---

## 15. Database Migrations

### Naming

- Create: `YYYY_MM_DD_HHMMSS_create_{table}_table.php`
- Modify: `YYYY_MM_DD_HHMMSS_add_{column}_to_{table}_table.php`

### Structure

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_name', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_name');
    }
};
```

### Key Rules

- All primary keys are UUIDs
- Foreign keys use `foreignUuid()` with `constrained()`
- Chain `->nullable()` immediately after the type
- Add `->index()` for frequently queried columns
- Always include `->timestamps()`

---

## 16. Factories

### Structure

```php
namespace Database\Factories;

use App\Models\ModelName;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModelName>
 */
class ModelNameFactory extends Factory
{
    protected $model = ModelName::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
        ];
    }

    public function customState(): static
    {
        return $this->state(fn (array $attributes) => [
            'some_field' => 'custom_value',
        ]);
    }
}
```

### Data Generation

Use `$this->faker->xxx()` for fake data. Custom states return `$this->state(fn () => [...])`.

---

## 17. Tests

### Framework

Pest 4 with `LazilyRefreshDatabase` for feature tests. All test files start with `declare(strict_types=1)`.

### File Structure

```
tests/{Suite}/{Domain}/{Name}Test.php
```

- Feature: `tests/Feature/{Domain}/{Name}Test.php`
- Unit: `tests/Unit/{Layer}/{Domain}/{Name}Test.php`
- Arch: `tests/Arch/{Name}ArchTest.php`

### Test Patterns

Use `describe()` for grouping related tests, `it()` for individual assertions:

```php
describe('CreateUserAction', function () {
    it('creates a user with minimal data', function () {
        $user = app(CreateUserAction::class)->execute(
            name: 'John',
            email: 'john@example.com',
        );

        expect($user)->toBeInstanceOf(User::class);
    });
});
```

Use `beforeEach()` for shared setup:

```php
beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
    $this->actingAs($this->admin);
});
```

### Livewire Testing

```php
Livewire::test(UserManager::class)
    ->assertSuccessful()
    ->set('search', 'Alice')
    ->assertSee('Alice')
    ->assertDontSee('Bob');
```

### Entity Testing

Instantiate entities directly — no database needed:

```php
$entity = new Apprentice(
    status: AccountStatus::SUSPENDED,
    isLocked: false,
);

expect($entity->isSuspended())->toBeTrue();
```

### Architecture Tests

Architecture tests enforce structural rules. The test suite (`tests/Arch/`) validates:
- Strict types and no debug functions
- Layer separation (controllers, notifications, events, services import rules)
- Entity layer (BaseEntity, final readonly)
- Enum layer (string-backed, implement LabelEnum)
- Model layer (model conventions)
- Action layer (action conventions)
- Policy layer (policy conventions)
- Exception hierarchy
- Contracts conventions

---

## 18. Configuration Files

### Structure

All config files return a PHP array with `snake_case` keys:

```php
<?php

declare(strict_types=1);

return [
    'driver' => env('SESSION_DRIVER', 'database'),
    'lifetime' => (int) env('SESSION_LIFETIME', 120),
];
```

Values use `env()` with explicit type casting: `(bool) env(...)`, `(int) env(...)`.

---

## 19. Architecture Rules

These rules are enforced by architecture tests and must not be violated:

| Rule | Description |
|---|---|
| **Strict Types** | All app code uses `declare(strict_types=1)` |
| **No Debug Functions** | No `dd()`, `dump()`, `ray()`, `var_dump()`, `print_r()`, `die()` in app code |
| **Layer Separation** | Controllers must not import Actions or Models |
| **Layer Separation** | Notifications must not import Livewire |
| **Layer Separation** | Events must not import Actions |
| **Layer Separation** | Services must not import Livewire |
| **Entity Purity** | Entities must not import Models (only BaseEntity may import Eloquent) |
| **Entity Structure** | All entities are `final readonly` classes extending `BaseEntity` |
| **Enum Backing** | All enums are string-backed |
| **Enum Contract** | All enums implement `LabelEnum` |
| **Controller Suffix** | All controller classes end with `Controller` |
