---
name: laravel-best-practices
description: SDLC Phase: IMPLEMENTATION (Cross-cutting). Context-aware Laravel guidance that overrides default conventions where they conflict with the Module-first Action-based MVC architecture. Referenced by all implementation skills.
upstream:
  - feature-building
  - code-refactoring
  - livewire-development
downstream:
  - all implementation skills
---

# Laravel Best Practices Skill

## When to Activate

Apply this skill whenever writing, reviewing, or refactoring any Laravel PHP code — controllers, models, migrations, form requests, policies, jobs, queries, routes, Blade views. Overrides default Laravel conventions where they conflict with the Action-based MVC architecture.

## SDLC Context

| Role | Skill |
|------|-------|
| **Upstream (input)** | Called by any implementation/design skill |
| **This skill** | **IMPLEMENTATION (Cross-cutting Laravel guidance)** |
| **Downstream (output)** | Feeds into all implementation work |
| **Phase** | [Planning] → [Analysis] → [Design] → Implementation → [Testing] → [Maintenance] |

## Key References

- **Architecture**: `docs/architecture.md` — 12-layer architecture, 4-layer data flow with DTO boundaries, circular dep. prevention
- **Conventions**: `docs/conventions.md` — coding conventions with examples
- **Base classes**: `app/Core/` — Actions, Entities, Policies, Livewire, Models, Http, Data
- **Action pattern**: `docs/architecture/action-pattern.md` — DTO input, ActionResponse return, Entity delegation
- **Data pattern**: `docs/architecture/data-pattern.md` — DTO lifecycle, immutability, boundary rules
- **Entity pattern**: `docs/architecture/entity-pattern.md` — purity rules, zero Action/Service imports
- **Model pattern**: `docs/architecture/model-pattern.md`
- **Testing pattern**: `docs/architecture/testing-pattern.md`
- **Event pattern**: `docs/architecture/event-pattern.md`

## Module-First Organization

All code lives under `app/{Module}/` instead of the default flat structure:

| Laravel Default | Internara Convention |
|-----------------|---------------------|
| `app/Models/` | `app/{Module}/{SubModule}/Models/` |
| `app/Http/Controllers/` | `app/{Module}/Http/Controllers/` or submodule |
| `app/Policies/` | `app/{Module}/{SubModule}/Policies/` |
| Route files | `routes/web/{module}.php` |
| Views | `resources/views/{module}/` |

## Mandatory Base Classes

| Layer | Base Class | Provides |
|-------|-----------|----------|
| Model | `BaseModel` | UUID PK via `HasUuids`, non-incrementing |
| Auth Model | `Authenticatable` + `HasUuids` | User model only |
| Command/Process Action | `BaseAction` | `transaction()`, `log()`, `HandlesActionErrors` |
| Read Action | None | Plain class with constructor injection |
| Entity | `BaseEntity` (abstract readonly) | `fromModel(Model): static` contract |
| Policy | `BasePolicy` | `AuthorizesRoles`, `AuthorizesOwnership` traits |
| Livewire CRUD | `BaseRecordManager` | Search, filter, sort, pagination, bulk actions |
| Form Request | `BaseFormRequest` | Consistent `ValidationFailedException` |
| DTO | `BaseData` (final readonly) | `toArray()`, `fromArray()`, `from()` |
| Event | `BaseEvent` | `Dispatchable`, `eventName()`, `toPayload()` |
| Enum | Implements `LabelEnum` | `label(): string` |
| State enum | Implements `StatusEnum` | `canTransitionTo()`, `isTerminal()` |
| Exception | `AppException` or `ModuleException` | `HasExceptionContext` trait |

## Action-Based MVC & 4-Layer Data Flow

```
UI (Livewire/Controller/Console) → DTO → ACTION → Entity checks → Model → DB
                                            ↓
                                     ActionResponse
                                            ↓
                                     UI (flash/redirect)
```

- Controllers and Livewire are thin — handle UI state, build DTOs, delegate to Actions
- One Action = one business operation = one `execute()` method
- **Command/Process Actions SHOULD accept `BaseData` DTO for 3+ params** — typed scalars OK for simple. Never raw `array`.
- **Command/Process Actions SHOULD return `ActionResponse`** for structured feedback. Simple create/update may return Model directly.
- Actions validate input, delegate rule checks to Entities, persist in transactions, return result
- Actions must NOT contain inline `canX()` checks — those belong in Entities
- Livewire may access Entity methods for READ-ONLY UI checks. WRITE decisions must go through Actions.
- DTOs must NOT import Models, Actions, Entities, or Livewire — only scalars, enums, Carbon

## UUID Primary Keys

- Every Model extends `BaseModel` which applies `HasUuids`
- Foreign keys use `foreignUuid()->constrained()` in migrations
- `User` model is the sole exception — extends `Authenticatable` directly, applies `HasUuids` manually

## Enum Conventions

- All enums are `string`-backed. All implement `LabelEnum` (`label(): string`)
- State machine enums additionally implement `StatusEnum` with `canTransitionTo()`, `isTerminal()`
- Cases: `UPPER_SNAKE` with lowercase backing value
- Model defaults use `Enum::CASE->value` — never hardcoded strings

## Code Standards

- `declare(strict_types=1)` on every PHP file (except migrations and config)
- Constructor property promotion with `protected readonly`
- Explicit return types on every method
- `__()` for all user-facing strings — never hardcoded
- Array validation rules (not pipe syntax)
- `#[Fillable]` attribute on Models (not `$fillable` property)

## Model Patterns

Define scopes as `scope{Name}()` returning `Builder`:
```php
public function scopeActive(Builder $query): Builder { return $query->where('status', StatusEnum::ACTIVE->value); }
public function scopeRecent(Builder $query, ?int $days = 30): Builder { return $query->where('created_at', '>=', now()->subDays($days)); }
public function scopeOrdered(Builder $query, string $column = 'created_at', string $dir = 'desc'): Builder { return $query->orderBy($column, $dir); }
```

Relationship methods typed with singular/plural return types:
```php
public function user(): BelongsTo { return $this->belongsTo(User::class); }
public function profile(): HasOne { return $this->hasOne(Profile::class); }
public function comments(): HasMany { return $this->hasMany(Comment::class)->chaperone(); }
public function roles(): BelongsToMany { return $this->belongsToMany(Role::class)->withTimestamps(); }
```

Accessors/mutators: `get{Name}Attribute`/`set{Name}Attribute`. Casts: `protected function casts(): array`.
```php
protected function casts(): array {
    return ['email_verified_at' => 'datetime', 'is_active' => 'boolean', 'metadata' => 'array', 'status' => StatusEnum::class];
}
public function getFullNameAttribute(): string { return "{$this->first_name} {$this->last_name}"; }
public function setEmailAttribute(string $value): void { $this->attributes['email'] = strtolower($value); }
```

Factory definitions use `fake()` and enum values:
```php
public function definition(): array {
    return ['name' => fake()->name(), 'email' => fake()->unique()->safeEmail(), 'status' => InternshipStatus::DRAFT->value];
}
public function published(): static { return $this->state(fn () => ['status' => InternshipStatus::PUBLISHED->value]); }
```

## Query Patterns

Use `when()` for conditional clauses:
```php
User::query()
    ->when($request->search, fn (Builder $q, string $s) => $q->where('name', 'like', "%{$s}%"))
    ->when($request->role, fn (Builder $q, string $r) => $q->whereHas('roles', fn ($q) => $q->where('name', $r)))
    ->when($request->active, fn (Builder $q) => $q->where('is_active', true))
    ->get();
```

Use `whereHas()`/`whereDoesntHave()` for relationship existence:
```php
Post::whereHas('comments', fn (Builder $q) => $q->where('approved', true))->get();
User::whereDoesntHave('registrations')->get();
User::has('comments', '>=', 5)->get();
```

Use `withCount()`/`withExists()` to avoid N+1:
```php
$users = User::withCount('posts')->withExists('registrations')->get();
// $user->posts_count, $user->registrations_exists
```

Subquery selects:
```php
User::addSelect(['last_post_title' => Post::select('title')->whereColumn('user_id', 'users.id')->latest()->limit(1)])->get();
```

Use `latest()`/`oldest()` over manual `orderBy`. Use `chunk()`/`lazy()` for batch:
```php
User::where('is_active', true)->chunk(200, function (Collection $users) { foreach ($users as $user) { /* process */ } });
foreach (User::lazy(500) as $user) { /* process */ }
```

## Validation Patterns

Array syntax over pipe — all rules are arrays:
```php
public function rules(): array {
    return [
        'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user->id)],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'string', Rule::in(RoleEnum::values())],
        'birth_date' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
    ];
}
```

Custom rule objects (implements `ValidationRule`):
```php
class CurrentPasswordRule implements ValidationRule {
    public function validate(string $attribute, mixed $value, Closure $fail): void {
        if (! Hash::check($value, auth()->user()->password)) $fail(__('validation.current_password'));
    }
}
```

`Validator::sometimes()` for conditional rules:
```php
$validator->sometimes('score', ['required', 'integer', 'min:0', 'max:100'], fn (Input $i) => $i->status === StatusEnum::COMPLETED->value);
```

Nested array validation:
```php
'items' => ['required', 'array', 'min:1'],
'items.*.id' => ['required', 'uuid', 'exists:products,id'],
'items.*.quantity' => ['required', 'integer', 'min:1'],
```

Date rules use `before:`/`after:` with field references:
```php
'start_date' => ['required', 'date', 'after:today'],
'end_date' => ['required', 'date', 'after:start_date'],
```

## Migration Patterns

3-step table modification — add → check → drop:
```php
Schema::table('users', fn (Blueprint $t) => $t->string('phone')->nullable()->after('email'));
if (Schema::hasColumn('users', 'old_field')) Schema::table('users', fn (Blueprint $t) => $t->dropColumn('old_field'));
Schema::table('users', fn (Blueprint $t) => $t->dropIndex(['user_id', 'date']));
```

Indexing — every `WHERE`/`ORDER BY`/`JOIN` column needs an index:
```php
Schema::create('attendances', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
    $table->date('date');
    $table->time('clock_in');
    $table->time('clock_out')->nullable();
    $table->foreignUuid('verified_by')->nullable()->constrained('users')->onDelete('set null');
    $table->index(['user_id', 'date']); // attendances_user_id_date_index
    $table->index('status');
    $table->timestamps();
});
```

Composite index naming: `{table}_{col1}_{col2}_index`. Foreign key delete behavior:
```php
$table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();     // parent required
$table->foreignUuid('verified_by')->nullable()->constrained('users')->onDelete('set null'); // optional
$table->foreignUuid('owner_id')->constrained('users')->onDelete('restrict');  // prevent delete
```

## Service Container Patterns

`singleton()` vs `bind()`:
```php
$this->app->bind(ReportGeneratorInterface::class, PdfReportGenerator::class);          // new instance each time
$this->app->singleton(SmartLogger::class, fn ($app) => new SmartLogger(...));            // same instance
$this->app->singleton(SettingsStore::class, DatabaseSettingsStore::class);
```

Contextual binding:
```php
$this->app->when(ProcessRegistrationAction::class)->needs(NotifierInterface::class)->give(EmailNotifier::class);
$this->app->when(ProcessApprovalAction::class)->needs(NotifierInterface::class)->give(SmsNotifier::class);
```

Deferred providers — only loaded when binding is needed:
```php
class ReverbServiceProvider extends ServiceProvider {
    public array $bindings = [ReverbClient::class => ReverbClient::class];
    public function provides(): array { return [ReverbClient::class]; }
}
```

Never use `app()->make()` or `resolve()` in application code — use constructor/method injection. Exceptions: only in service provider `register()`/`boot()` and factories.

## Blade Patterns

`@stack`/`@push`/`@prepend` for section injection:
```blade
@push('styles') <link href="{{ asset('css/page.css') }}" rel="stylesheet"> @endpush
@prepend('styles') {{-- renders before other pushes --}} @endprepend
```

`@include` vs `@component` vs `@each`:
```blade
@include('shared._error-summary', ['errors' => $errors])
@component('shared.components.alert', ['type' => 'success']) @slot('title') Success @endslot Body @endcomponent
@each('user._user-row', $users, 'user', 'shared._empty')
```

`@props` and `@aware` for anonymous components:
```blade
@props(['type' => 'info', 'dismissible' => false])
@aware(['theme' => 'default'])
<div {{ $attributes->merge(['class' => "alert alert-{$type}"]) }}>{{ $slot }}</div>
```

`@error` for validation feedback:
```blade
<input name="email" class="@error('email') is-invalid @enderror">
@error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
```

## Route Patterns

Route model binding — type-hint the model:
```php
Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
public function show(User $user): Response { return view('users.show', compact('user')); }
// Custom binding key: Route::get('/users/{user:email}', ...)
```

`route()` over `url()` — safe when URL structure changes:
```blade
<a href="{{ route('users.show', $user) }}">View</a>
<a href="{{ route('admin.users.edit', ['user' => $user, 'page' => 2]) }}">Edit</a>
```

Named routes with `{prefix}.{resource}.{action}`:
```php
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class)->names('users');
    // admin.users.index, admin.users.show, etc.
});
```

Throttle middleware:
```php
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1')->name('login');
```

## Mail Notification Patterns

`MailMessage` for simple, `Markdown` for rich:
```php
public function toMail(object $notifiable): MailMessage {
    return (new MailMessage)->subject(__('notifications.subject'))->greeting(__('notifications.greeting'))
        ->line(__('notifications.body'))->action(__('notifications.action'), url('/dashboard'));
}
```

`ShouldQueue` for I/O-heavy notifications:
```php
use Illuminate\Contracts\Queue\ShouldQueue;
class InternshipCreatedNotification extends Notification implements ShouldQueue {
    public $queue = 'notifications';
    public $delay = 10;
}
```

`via()` defines channels conditionally:
```php
public function via(object $notifiable): array {
    $channels = ['database'];
    if ($notifiable->prefers_mail) $channels[] = 'mail';
    return $channels;
}
```

`toDatabase()` returns structured array:
```php
public function toDatabase(object $notifiable): array {
    return ['report_id' => $this->report->id, 'message' => __('notifications.report.body'), 'url' => route('reports.show', $this->report)];
}
```

## Security Best Practices

`Hash::make()` for passwords:
```php
User::create(['password' => Hash::make($data->password)]);
```

Signed routes for public links:
```php
$url = URL::temporarySignedRoute('certificates.verify', now()->addHours(48), ['certificate' => $id]);
public function verify(Request $request): Response {
    abort_unless($request->hasValidSignature(), 403, __('certificate.invalid_link'));
}
```

`Str::random()` for tokens (never `uniqid()`/`rand()`):
```php
$token = Str::random(64);
```

Email validation:
```php
if (! filter_var($email, FILTER_VALIDATE_EMAIL)) throw ValidationFailedException::forField('email', __('validation.email'));
```

Additional rules:
```php
$this->authorize('update', $report);                                // server-side authorization
User::whereRaw('email = ?', [$input])->get();                       // ✅ parameterized
User::whereRaw("email = '$input'")->get();                          // ❌ injection vector
{{ $user->bio }}                                                     // ✅ escaped
{!! $user->bio !!}                                                   // ❌ only with sanitizer justification
```

## Artisan Command Patterns

Interactive prompts:
```php
$name = $this->ask(__('commands.user.name_prompt'));
if (! $this->confirm(__('commands.user.confirm_create'), true)) { $this->info(__('commands.cancelled')); return Command::SUCCESS; }
$role = $this->choice(__('commands.user.role_prompt'), ['admin', 'teacher'], 0);
$department = $this->anticipate(__('commands.department_prompt'), Department::pluck('name')->toArray());
```

Progress bars and table output:
```php
$bar = $this->output->createProgressBar($users->count()); $bar->start();
foreach ($users as $u) { $this->process($u); $bar->advance(); } $bar->finish(); $this->newLine();

// Shorthand: $this->withProgressBar(User::all(), fn (User $u) => $this->notify($u)); $this->newLine();

$this->table(
    ['Check', 'Status'],
    [['Database', DB::connection()->getDatabaseName()], ['Cache', Cache::getDefaultDriver()]]
);
```

## Common Pitfalls

| Pitfall | Why It's Wrong | Correct Approach |
|---------|---------------|------------------|
| `Model::create($request->all())` | Mass assignment | `Model::create($request->only(['name', 'email']))` |
| `where('role', 'admin')->get()` | Hardcoded string | `where('role', Role::ADMIN->value)->get()` |
| `$collection->filter()` on large set | Loads all rows, filters in PHP | `Model::where(...)->get()` |
| N+1: relations in loop without `with()` | Query per iteration | Add `->with('relation')` on query |
| `Cache::put('my-key', $val, 3600)` | Inline key, no registry | Register key in `config/cache-keys.php` |
| `new Service()` in Livewire | Bypasses DI | Constructor/method injection |
| `dd()`/`dump()` left in code | Breaks production | Remove all debug calls before commit |
| `Schema::drop('table')` without check | Crashes if missing | Wrap in `Schema::hasTable()` check |
| `Arr::get()` for nested access | No typing, masked missing keys | Use typed DTOs (`BaseData`) |
| `Notification::send($users, ...)` | Blocks response | Add `ShouldQueue` + `$delay` |
| `whereRaw("col = '$input'")` | SQL injection | `whereRaw('col = ?', [$input])` |
| `app()->make(Class)` in application code | Service locator pattern | Inject via constructor |

## Verification

- [ ] Module-first structure (check sibling files)?
- [ ] Business logic in Actions, not Models/Livewire?
- [ ] Business rules delegated to Entities?
- [ ] UUID primary keys via BaseModel?
- [ ] `declare(strict_types=1)` present?
- [ ] All user-facing strings via `__()`?
- [ ] `#[Fillable]` attribute (not `$fillable`/`$guarded`)?
- [ ] No debug calls (`dd`/`dump`/`ray`/`var_dump`/`print_r`/`die`)?
- [ ] Array validation syntax (not pipe)?
- [ ] `foreignUuid()->constrained()` with explicit `onDelete()`/`onUpdate()`?
- [ ] Scopes as `scope{Name}()` returning `Builder`?
- [ ] Relationship methods with correct return type?
- [ ] Casts in `protected function casts(): array`?
- [ ] `when()` for conditional query clauses?
- [ ] `whereHas()`/`whereDoesntHave()` for relationship existence?
- [ ] Eager loading with `->with()` for all relations in loops?
- [ ] `chunk()` or `lazy()` for large datasets?
- [ ] Constructor/method injection — no `app()->make()`/`resolve()`?
- [ ] Route names follow `{prefix}.{resource}.{action}`?
- [ ] `route()` helper over `url()` for named routes?
- [ ] Notifications implement `ShouldQueue` for I/O-heavy channels?
- [ ] Signed routes for public links, `Hash::make()` for passwords?
- [ ] `Str::random()` for tokens (not `uniqid()`/`rand()`)?
- [ ] Artisan commands use `ask()`/`confirm()`/`choice()`?
- [ ] Cache keys registered in `config/cache-keys.php`?
- [ ] No raw SQL without parameterized binding?
- [ ] `php artisan test --compact` passes?
- [ ] `vendor/bin/pint --dirty --format agent` clean?
- [ ] `vendor/bin/phpstan analyse --no-progress` passes?
- [ ] Relevant docs updated (documentation-first)?
