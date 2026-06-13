# Model Pattern Reference

> **Last updated:** 2026-06-10
>
> **Purpose:** Comprehensive reference on Eloquent Model (Active Record) patterns used in the
> Internara codebase. Models handle **persistence and relationships only** — business rules live in
> [Entities](entity-pattern.md).

---

## 1. Active Record Philosophy

Models extend Eloquent's Active Record implementation and are responsible for:

- **Persistence** — reading/writing database rows
- **Relationships** — defining and querying associations between tables
- **Attribute casting** — transforming raw column values into PHP types
- **Query scopes** — reusable query fragments
- **Media management** — file uploads and image conversions
- **Entity bridging** — exposing `as{Entity}()` accessors that delegate to pure domain objects

Business rules, invariant enforcement, and state-machine logic **do not** belong in models. They are
extracted into Entity classes (see [entity-pattern.md](entity-pattern.md)).

```php
// ✅ Model: persistence + relationship + entity bridge
class Internship extends BaseModel
{
    public function registrations(): HasMany { ... }
    public function asInternshipState(): InternshipState
    {
        return InternshipState::fromModel($this);
    }
}

// ❌ Business logic in model — extract to Entity
public function canTransitionTo(string $status): bool { ... }
```

---

## 2. BaseModel Contract

All models (except User) extend `App\Core\Models\BaseModel`, which configures:

| Concern              | Implementation                                        |
| -------------------- | ----------------------------------------------------- |
| UUID primary key     | `use HasUuids;` (Laravel's trait, generates UUID v7)  |
| Non-incrementing     | Inherits `$incrementing = false` from `HasUuids`      |
| String key type      | Inherits `$keyType = 'string'` from `HasUuids`        |
| Common scopes        | `scopeActive()`, `scopeInactive()`, `scopeRecent()`, `scopeCreatedAfter()`, `scopeCreatedBefore()`, `scopeOrdered()` |

```php
namespace App\Core\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    use HasUuids;

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeRecent(Builder $query, int $limit = 50): Builder
    {
        return $query->latest()->limit($limit);
    }

    public function scopeCreatedAfter(Builder $query, string $date): Builder
    {
        return $query->where('created_at', '>=', $date);
    }

    public function scopeCreatedBefore(Builder $query, string $date): Builder
    {
        return $query->where('created_at', '<=', $date);
    }

    public function scopeOrdered(Builder $query, string $column = 'created_at', string $direction = 'desc'): Builder
    {
        return $query->orderBy($column, $direction);
    }
}
```

Model example extending `BaseModel`:

```php
namespace App\Program\Internship\Models;

use App\Core\Models\BaseModel;
use Database\Factories\InternshipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[
    Fillable([
        'academic_year_id',
        'name',
        'start_date',
        'end_date',
        'description',
        'status',
        'phases',
        'required_document_ids',
        'grading_weights',
    ]),
]
class Internship extends BaseModel
{
    use HasFactory;

    protected $casts = [ /* ... */ ];
}
```

---

## 3. BaseAuthenticatable

The `User` model cannot extend `BaseModel` because Laravel's authentication system requires it to
extend `Illuminate\Foundation\Auth\User` (or `Authenticatable`). `BaseAuthenticatable` bridges this
gap by applying the same UUID and scope conventions to the authenticatable base:

```php
namespace App\Core\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;

abstract class BaseAuthenticatable extends Authenticatable
{
    use HasUuids;

    // Same scopes as BaseModel: scopeActive, scopeInactive, scopeRecent, etc.
}
```

The `User` model extends `BaseAuthenticatable`:

```php
namespace App\User\Models;

use App\Core\Models\BaseAuthenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends BaseAuthenticatable implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia, Notifiable, HasRoles;
    // ...
}
```

Note that `HasUuids` is applied again explicitly — this is harmless (PHP traits are idempotent) and
makes the UUID dependency visible without digging into the parent class.

---

## 4. Model Directory Structure

Models live inside their owning module, following the two-tier path convention:

```
app/{Module}/{Submodule}/Models/{Model}.php
```

Examples:

| File Path                                           | Namespace                                      |
| --------------------------------------------------- | ---------------------------------------------- |
| `app/User/Models/User.php`                          | `App\User\Models`                              |
| `app/Settings/Models/Setting.php`                   | `App\Settings\Models`                          |
| `app/Program/Internship/Models/Internship.php`      | `App\Program\Internship\Models`                |
| `app/Enrollment/Registration/Models/Registration.php` | `App\Enrollment\Registration\Models`         |
| `app/Academics/AcademicYear/Models/AcademicYear.php` | `App\Academics\AcademicYear\Models`           |
| `app/Core/Models/BaseModel.php`                     | `App\Core\Models` (shared base)                |

One model per file. No model files in shared `app/Models/` — models always belong to a module.

---

## 5. UUID Primary Key Convention

All tables use **UUID v7** (time-ordered) as primary keys. This is enforced by `BaseModel` and
`BaseAuthenticatable` via Laravel's `HasUuids` trait, which generates ordered UUIDs that preserve
B-tree insertion locality.

### Automatic UUID (BaseModel)

```php
abstract class BaseModel extends Model
{
    use HasUuids;
    // $incrementing = false, $keyType = 'string' inherited from HasUuids
}
```

### Explicit UUID (non-BaseModel)

For the rare model that cannot extend `BaseModel`, apply `HasUuids` manually:

```php
class Setting extends BaseModel
{
    use HasFactory, InteractsWithMedia;

    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
}
```

`Setting` uses a string primary key (`key` column) rather than UUID, but it still sets
`$incrementing = false` and `$keyType = 'string'` to match the convention.

### Foreign Keys in Migrations

All foreign key columns use `foreignUuid()` with explicit `onDelete()` behavior:

```php
Schema::create('registrations', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('student_id')->constrained('users')->cascadeOnDelete();
    $table->foreignUuid('internship_id')->constrained('internships')->cascadeOnDelete();
    $table->foreignUuid('placement_id')->nullable()->constrained('placements')->onDelete('set null');
    $table->index(['student_id', 'internship_id']);
    $table->timestamps();
});
```

No mixed key types are permitted. Enforced through code review.

---

## 6. `#[Fillable]` Attribute Convention

Mass assignment protection uses PHP 8 **attributes**, not the traditional `$fillable` property.
This keeps the fillable declaration adjacent to the class signature for visibility.

```php
#[
    Fillable([
        'name',
        'email',
        'username',
        'password',
        'status',
        'is_active',
    ]),
]
class User extends BaseAuthenticatable implements HasMedia
{
    // ...
}
```

Multi-line attribute syntax is required when the array spans multiple values. For a single value,
inline is acceptable:

```php
#[Fillable(['key', 'value', 'type', 'description', 'group'])]
class Setting extends BaseModel implements HasMedia
{
    // ...
}
```

The traditional `$fillable` property is **not used** anywhere in the codebase. All models use
`#[Fillable]`.

---

## 7. Relationship Naming Convention

Relationships follow a strict singular/plural convention based on cardinality:

| Type                        | Method Name | Example                       |
| --------------------------- | ----------- | ----------------------------- |
| `BelongsTo` / `HasOne`      | Singular    | `user()`, `academicYear()`    |
| `HasMany` / `BelongsToMany` | Plural      | `users()`, `registrations()`  |
| `MorphTo`                   | Singular    | `verifiable()`                |
| `MorphMany`                 | Plural      | `comments()`                  |

Always define the inverse relationship. The optional `$foreignKey` parameter is used when the column
name deviates from convention:

```php
public function student(): BelongsTo
{
    return $this->belongsTo(User::class, 'student_id');
}

public function registrations(): HasMany
{
    return $this->hasMany(Registration::class, 'student_id');
}

public function internship(): BelongsTo
{
    return $this->belongsTo(Internship::class);
}

public function placements(): HasMany
{
    return $this->hasMany(Placement::class);
}

public function report(): HasOne
{
    return $this->hasOne(Report::class, 'registration_id');
}

// Custom foreign key on both sides
public function supervisionLogs(): HasMany
{
    return $this->hasMany(SupervisionLog::class, 'registration_id');
}
```

---

## 8. Entity Accessor Pattern

Models expose entities through **named accessors** using the `as{EntityName}()` pattern. This is the
bridge between the persistence layer (Model) and the domain layer (Entity). Never use a generic
`entity()` method.

```php
// Program/Internship/Models/Internship.php
public function asInternshipPeriod(): InternshipPeriod
{
    return InternshipPeriod::fromModel($this);
}

public function asInternshipState(): InternshipState
{
    return InternshipState::fromModel($this);
}

// Enrollment/Registration/Models/Registration.php
public function asRegistrationState(): RegistrationState
{
    return RegistrationState::fromModel($this);
}

// User/Models/User.php
public function asApprentice(): Apprentice
{
    return Apprentice::fromModel($this);
}

// Settings/Models/Setting.php
public function asSetting(): SettingEntity
{
    return SettingEntity::fromModel($this);
}
```

A model may expose multiple entity accessors when it contains data for multiple domain concepts
(e.g., `Internship` exposes both `InternshipPeriod` and `InternshipState`).

---

## 9. Scope Pattern

Query scopes encapsulate common WHERE conditions. Scopes are defined at the model level and
chain naturally through Eloquent queries.

### Base Scopes (inherited from BaseModel / BaseAuthenticatable)

```php
Model::active()          // WHERE is_active = true
Model::inactive()        // WHERE is_active = false
Model::recent(20)        // ORDER BY created_at DESC LIMIT 20
Model::createdAfter($d)  // WHERE created_at >= $d
Model::createdBefore($d) // WHERE created_at <= $d
Model::ordered('name')   // ORDER BY name DESC
```

### Model-Specific Scopes

Scopes on `User`:

```php
$query->locked()          // WHERE locked_at IS NOT NULL
$query->unlocked()        // WHERE locked_at IS NULL
$query->active()          // unlocked + setup_required = false (overrides base)
$query->roleType('admin') // WHERE has role 'admin'
// Inherited from HasRoles trait:
$query->role('superadmin')
$query->withoutRole('student')
```

Scopes on `Setting`:

```php
$query->group('general')           // WHERE group = 'general'
$query->byKey('site.name')         // WHERE key = 'site.name'
$query->inGroup(['general', 'seo']) // WHERE group IN (...)
$query->ofType(SettingType::STRING) // WHERE type = 'string'
$query->searchable('logo')          // WHERE key LIKE '%logo%' OR description LIKE '%logo%'
```

Scopes on `Announcement`:

```php
$query->published()
$query->draft()
$query->scheduled()
$query->pendingPublish()
```

### Convention

- Scope method returns `Builder`.
- Scope parameters are explicit and typed — avoid `...$args` patterns.
- Scopes are the **only** query logic on models. Complex query assembly belongs in Read Actions.

---

## 10. Casts Convention

Attribute casting uses `protected $casts` (property), not the `casts()` method, unless dynamic
casting is needed.

### Standard Casts

```php
protected $casts = [
    'start_date' => 'date',
    'end_date' => 'date',
    'email_verified_at' => 'datetime',
    'locked_at' => 'datetime',
    'password' => 'hashed',
    'setup_required' => 'boolean',
    'is_active' => 'boolean',
    'phases' => 'json',
    'required_document_ids' => 'json',
    'grading_weights' => 'json',
    'proposed_company_details' => 'json',
];
```

### Enum Casts

Backed enum casting uses the enum class FQCN as the cast target. The column stores the enum's
`value` (lowercase string), and Eloquent hydrates it back into the enum instance:

```php
protected $casts = [
    'status' => InternshipStatus::class,   // Backed enum
    'status' => AccountStatus::class,       // Backed enum
];
```

### Custom Casts

For complex transformation logic, create a dedicated cast class:

```php
// Settings/Models/Setting.php
protected $casts = [
    'value' => SettingValueCast::class,     // Custom caster
];
```

### Method-Based Casts (Dynamic)

Use the `casts()` method only when cast configuration is dynamic:

```php
// User/Models/User.php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'locked_at' => 'datetime',
        'password' => 'hashed',
        'setup_required' => 'boolean',
        'status' => AccountStatus::class,
        'is_active' => 'boolean',
    ];
}
```

Prefer the `$casts` property for static configurations. Use the `casts()` method only when the
return value depends on runtime conditions.

---

## 11. Media Library Integration

File uploads use [spatie/laravel-medialibrary](https://spatie.be/docs/laravel-medialibrary). Models
implement `HasMedia` and use the `InteractsWithMedia` trait.

### Avatar (Single File)

```php
// User/Models/User.php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class User extends BaseAuthenticatable implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->format('webp')
            ->nonQueued();
    }
}
```

### Named Collection via Enum

When a model has multiple named media collections, use an enum to keep collection names consistent:

```php
// Settings/Models/Setting.php
use App\Settings\Enums\MediaCollection;

class Setting extends BaseModel implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollection::LOGO->value)->singleFile();
        $this->addMediaCollection(MediaCollection::FAVICON->value)->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(200)->format('webp');
    }
}
```

### Models Using Media Library

| Model                | Collections              | Conversions |
| -------------------- | ------------------------ | ----------- |
| `User`               | `avatar` (single)        | `thumb`     |
| `Setting`            | `logo`, `favicon`        | `thumb`     |
| `Partnership`        | (agreement docs)         | —           |
| `RegistrationDocument` | (uploaded documents)   | —           |
| `Submission`         | (submission files)       | —           |
| `Document`           | (generic documents)      | —           |
| `Logbook`            | (logbook attachments)    | —           |

---

## 12. Factory Convention

Every model has a corresponding factory in `database/factories/`. Factories use Laravel's native
`HasFactory` trait and a `newFactory()` method for IDE/Stan support.

### Factory Registration

```php
use Database\Factories\InternshipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Internship extends BaseModel
{
    use HasFactory;

    protected static function newFactory(): InternshipFactory
    {
        return InternshipFactory::new();
    }
}
```

### Factory Definition

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
            fn (array $attrs) => [
                'status' => InternshipStatus::PUBLISHED->value,
            ],
        );
    }
}
```

### Naming Convention

| File                                  | Model              |
| ------------------------------------- | ------------------ |
| `database/factories/UserFactory.php`  | `User`             |
| `database/factories/InternshipFactory.php` | `Internship` |
| `database/factories/SettingFactory.php`    | `Setting`    |
| `database/factories/RegistrationFactory.php` | `Registration` |

Factory states use fluent methods (`published()`, `withPlacement()`, `completed()`). States never
duplicate the full definition — they only override the relevant attributes.

---

## 13. Testing Models

### What to Test

- **Do not** test Eloquent relationships directly — the framework is trusted.
- **Do not** test query scopes in isolation — test them through Actions or Livewire components.
- **Do** test model-specific business methods (e.g., `User::initials()`, `User::setStatus()`,
  `getActiveRegistration()`).
- **Do** test custom casts (e.g., `SettingValueCast`).

### Pattern

Model unit tests use `LazilyRefreshDatabase` and factories to create test records:

```php
describe('User model', function () {
    it('generates initials from full name', function () {
        $user = User::factory()->create(['name' => 'John Doe']);

        expect($user->initials())->toBe('JD');
    });

    it('prevents superadmin deletion', function () {
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');

        expect(fn () => $admin->delete())
            ->toThrow(RuntimeException::class, 'Super administrator accounts cannot be deleted.');
    });
});
```

### What NOT to Test

```php
// ❌ Don't test framework features
it('has many registrations', function () {
    $user = User::factory()->has(Registration::factory())->create();
    expect($user->registrations)->toHaveCount(1);
});

// ❌ Don't test scopes directly (test through higher-level actions)
it('scopes active users', function () {
    User::factory()->create(['is_active' => true]);
    expect(User::active()->count())->toBe(1);
});
```

These are covered implicitly by Action and Livewire feature tests.
