# Repository Pattern: Why Internara Doesn't Use It

> **Status:** Living document
> **Last updated:** 2026-06-10

---

## 1. Why No Repository Layer

Internara does **not** implement a Repository abstraction layer. This is an explicit architectural
decision, not an omission.

The Repository pattern became popular in PHP for three reasons that no longer apply:

1. **ORM abstraction** — Repositories hid the underlying storage so you could swap databases. Modern
   Laravel uses one ORM (Eloquent) with a single query builder. If you need raw SQL, Eloquent gives
   you `DB::raw()` and `toSql()`. There is no second persistence mechanism to abstract _from_.
2. **Testability** — Repositories allowed mocking database access in unit tests. Laravel provides
   `DatabaseMigrations`, `DatabaseTransactions`, model factories, and `Http::fake()` — test doubles
   for the entire persistence layer without writing custom repository stubs.
3. **Query reuse** — Repositories collected named queries in one place. Eloquent already does this
   with model scopes, local scopes, and relationship methods.

Adding a Repository layer between Livewire/Controllers and Eloquent would introduce accidental
complexity — every query needs a corresponding repository method, every repository needs an
interface, every interface needs a binding, and the payoff (swappable storage) is a non-goal for
this project.

See `docs/adr/adr-action-pattern-over-services.md` for the architectural decision context.

---

## 2. Eloquent as the Repository

Eloquent _is_ the Repository. The `Model` class provides:

| Capability | Role |
|---|---|
| `find()`, `findOrFail()` | Single-record lookup |
| `where()`, `orWhere()`, `whereIn()` | Filtering |
| `first()`, `firstOrFail()` | Conditional retrieval |
| `get()`, `paginate()`, `cursor()` | Collection retrieval |
| `with()`, `load()` | Eager loading |
| `create()`, `update()`, `delete()` | Persistence operations |
| `exists()`, `doesntExist()` | Existence checks |

Every Eloquent model extends `BaseModel` (`app/Core/Models/BaseModel.php`), which applies
**UUID v7** primary keys (`HasUuids`), sets `$incrementing = false`, and configures
`$keyType = 'string'`. See `docs/adr/adr-uuid-primary-keys.md` for the UUID rationale.

---

## 3. Simple Query Pattern (inline in Livewire)

**Simple queries** — single-table lookups, straightforward `where` clauses, relationship eager
loading — are written **directly in the Livewire component**:

```php
class UserManager extends Component
{
    public function mount(): void
    {
        $this->user = User::findOrFail($this->userId);           // simple lookup
        $this->users = User::where('is_active', true)->get();    // simple filter
        $this->registrations = $this->user->registrations()      // relationship
            ->with('internship')
            ->get();
    }
}
```

**Rule of thumb:** If the query fits in a single fluent chain with no business logic
interleaved, keep it inline.

### Authorization

Even simple queries must pass through authorization (Layer 8):

```php
$user = User::findOrFail($id);
Gate::authorize('view', $user);   // or $this->authorize() in Livewire
```

---

## 4. Complex Query Pattern (Read Actions)

**Complex queries** — aggregations, cross-module data assembly, multi-step filtering with business
rules — are extracted into **Read Actions** (Layer 7).

```php
class InternshipDashboardReader
{
    public function __construct(protected readonly Internship $model) {}

    public function activeInternships(): Collection
    {
        return $this->model
            ->whereIn('status', [InternshipStatus::PUBLISHED->value, InternshipStatus::ACTIVE->value])
            ->with('department', 'mentors.user')
            ->get();
    }

    public function registrationStats(): array
    {
        return [
            'total' => Registration::count(),
            'pending' => Registration::where('status', RegistrationStatus::PENDING->value)->count(),
            'approved' => Registration::where('status', RegistrationStatus::APPROVED->value)->count(),
        ];
    }
}
```

Read Actions are **plain classes** (no base class required). They MUST NOT mutate state, call
`transaction()`, or call `log()`.

**Naming:** `{Context}Reader`, `Get{Dashboard}Data`, `{Entity}Query`.

---

## 5. Query Scopes on Models

Reusable query fragments belong as **local scopes** on the Model:

```php
class Internship extends BaseModel
{
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            InternshipStatus::PUBLISHED->value,
            InternshipStatus::ACTIVE->value,
        ]);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('mentors', fn($q) => $q->where('user_id', $user->id));
    }
}
```

Usage is identical to a repository method but lives on the model itself:

```php
$internships = Internship::active()->forUser($user)->get();
```

Scopes should be **named descriptively** (`active`, `forUser`, `completedInYear`) and avoid
business-logic conditionals. When a scope would need parameters that encode domain rules, it is
time for a Read Action.

---

## 6. Relationship Methods as Repositories

Eloquent relationship methods serve as implicit repository methods. They provide the same
discoverability as a `findByUser()` repository method but with IDE autocompletion and type safety:

```php
// Relationship on User model
public function registrations(): HasMany
{
    return $this->hasMany(Registration::class);
}
```

Used inline:

```php
$activeRegistrations = $user->registrations()
    ->where('status', RegistrationStatus::ACTIVE->value)
    ->with('internship', 'mentor')
    ->get();
```

For frequently chained filters, add a local scope to the related model rather than a repository
method on the parent:

```php
// ✅ Eloquent scope
$user->registrations()->active()->get();

// ❌ No repository needed
RegistrationRepository::findActiveByUser($user);
```

---

## 7. When to Extract a Read Action

Extract a Read Action when the query crosses any of these thresholds:

| Threshold | Example |
|---|---|
| **Repeated in 2+ locations** | Same `whereHas` + aggregation used in a dashboard and an export |
| **Business logic in queries** | "Show only internships where the mentor has < 5 active students and the student's academic year matches the internship phase" |
| **Cross-module queries** | Joining data from Enrollment, Assessment, and Program modules |
| **Complex aggregation** | Multi-step calculations with conditional sums, rate computation |
| **Caching requirement** | Query results that should be cached with a specific invalidation strategy |

### Decision Table

| Query Type | Where It Lives | Example |
|---|---|---|
| `Model::find($id)` | Inline in Livewire | `User::findOrFail($this->userId)` |
| `Model::where()->get()` | Inline in Livewire | `Internship::active()->get()` |
| Relationship chain | Inline in Livewire | `$user->registrations()->with('internship')->get()` |
| Repeated filter | Local scope on Model | `Internship::active()->forUser($user)` |
| Non-trivial aggregation | Read Action | `InternshipDashboardReader::registrationStats()` |
| Cross-module data assembly | Read Action | `StudentProgressReader::fullReport($student)` |
| Dashboard data (many metrics) | Read Action | `GetAdminDashboardData::execute()` |

---

## 8. What a Repository Migration Would Look Like (if ever needed)

If the codebase ever develops a genuine need for a Repository layer — for example, a second
read store (Elasticsearch, read replica), or a caching proxy that cannot be handled at the Query
Builder level — the migration path is:

### Phase 1 — Repository Contract

```php
interface InternshipRepository
{
    public function findById(string $id): ?Internship;
    public function findActive(): Collection;
    public function findWithFilters(array $filters): LengthAwarePaginator;
}
```

### Phase 2 — Eloquent Implementation

```php
class EloquentInternshipRepository implements InternshipRepository
{
    public function __construct(protected readonly Internship $model) {}

    public function findById(string $id): ?Internship
    {
        return $this->model->find($id);
    }

    public function findActive(): Collection
    {
        return $this->model->active()->get();
    }

    public function findWithFilters(array $filters): LengthAwarePaginator
    {
        return $this->model
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['department_id'] ?? null, fn($q, $d) => $q->where('department_id', $d))
            ->paginate();
    }
}
```

### Phase 3 — Bind and Inject

```php
// ServiceProvider
$this->app->bind(InternshipRepository::class, EloquentInternshipRepository::class);

// In a Read Action
class InternshipDashboardReader
{
    public function __construct(protected readonly InternshipRepository $internships) {}
}
```

### Why This Is Not Done Today

- Eloquent already satisfies all query use cases.
- The Action Triad (Command/Read/Process) already separates read concerns from writes.
- A Repository layer adds indirection without benefit — switching from Eloquent to a different
  ORM is not a goal, and testing reads is already straightforward with model factories.
- The existing Read Actions can be promoted to Repository implementations if the need arises,
  without rewriting the callers.

The architecture prefers concrete dependencies over abstracted ones when there is exactly one
implementation and no planned alternative. Read Actions are injected directly with their Eloquent
model dependency:

```php
class InternshipDashboardReader
{
    public function __construct(protected readonly Internship $model) {}
}
```

This is explicit, testable, and requires zero container configuration. If the one-implementation
assumption ever changes, the migration path above is well understood and mechanically simple.

---

## References

- `docs/adr/adr-action-pattern-over-services.md` — Action Triad decision including query patterns
- `docs/architecture.md` — Data Flow section (simple vs complex queries)
- `docs/architecture.md#action-triad-command-read-process` — Read Action contract
- `docs/conventions.md#5-models` — Model conventions, scopes, relationships
- `app/Core/Models/BaseModel.php` — Base model with UUID primary keys
