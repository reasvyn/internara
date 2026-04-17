# Performance & Optimization: Systemic Tuning Standards

This document formalizes the **Performance Optimization** protocols for the Internara project,
standardized according to **ISO/IEC 25010** (Efficiency) and **ISO/IEC 12207** (Maintenance
Process). It covers both architectural patterns and concrete implementations applied to the codebase.

> **Governance Mandate:** Performance optimization must not compromise the **Strict Isolation**
> invariants of the modular monolith.

---

## 1. Modular Hydration Tuning

### 1.1 Autoloading & Discovery Caching

- **Module Status Cache**: The state of enabled/disabled modules is cached in
  `modules_statuses.json` to prevent recurring filesystem I/O.
- **Service Binding Cache**: The `BindServiceProvider` results should be cached in production
  environments to reduce discovery latency.

### 1.2 Resource Pre-Loading

Use the **Asset** support class in the `Shared` module to orchestrate the loading of modular CSS/JS
bundles. This ensures that assets are delivered to the browser only when the module is active.

---

## 2. Database Indexes

Index strategy for this codebase — all indexes are defined directly in the module migration files,
not in separate optimization migrations.

### 2.1 Guiding Rules

- **SQLite does not auto-index FK columns.** Every `foreignUuid('column_id')->constrained(...)` that
  will appear in a `WHERE` or `JOIN` must also call `.index()` in the same chain.
- **Composite indexes** outperform multiple single-column indexes for queries that filter or sort on
  two columns simultaneously.
- **Search columns** (`name`, `title`) should have a single-column index. Note that `LIKE '%x%'`
  (leading wildcard) cannot use B-tree indexes; it only benefits prefix searches `LIKE 'x%'`.

### 2.2 Applied Indexes

| Table | Column(s) | Type | Reason |
| :--- | :--- | :--- | :--- |
| `internship_companies` | `name` | Single | Search bar in CompanyManager |
| `internship_placements` | `company_id`, `internship_id` | Single (FK) | FK columns lacked explicit indexes in SQLite |
| `internship_registrations` | `internship_id`, `placement_id` | Single (FK) | Same reason |
| `internship_registrations` | `(student_id, academic_year)` | Composite | Filter: per-student, per-year listing |
| `internship_registrations` | `(internship_id, created_at)` | Composite | Sort: recent registrations per program |
| `journal_entries` | `(student_id, date)` | Composite | Student timeline view |
| `journal_entries` | `(registration_id, date)` | Composite | Supervisor date-range report |
| `statuses` | `(model_type, model_id, created_at)` | Composite | `latestStatus()` — filters by polymorphic key then sorts by time |
| `users` | `name` | Single | Search bar in all user-manager components |

### 2.3 Adding Indexes to Existing Tables

Since the project is pre-production, indexes are added directly in the original migration file:

```php
// In the existing create_xxx_table migration:
$table->foreignUuid('internship_id')->index()->constrained('internships')->cascadeOnDelete();
$table->index(['student_id', 'academic_year']); // composite at end of closure
```

---

## 3. Persistence & Query Optimization

### 3.1 Eager Loading (N+1 Prevention)

Use `with()` to pre-load relationships in a single extra query instead of one query per row:

```php
// ❌ N+1: 1 query for registrations + N queries for students
$registrations = Registration::paginate(20);
foreach ($registrations as $r) {
    echo $r->student->name; // triggers a new query per record
}

// ✅ Eager load: 2 queries total
$registrations = Registration::with(['student:id,name', 'placement.company:id,name'])
    ->paginate(20);
```

Within `RecordManager`, override `records()` to specify eager loads:

```php
#[Computed]
public function records(): LengthAwarePaginator
{
    return $this->service
        ->query(['search' => $this->search, 'sort_by' => $this->sortBy['column'] ?? 'created_at'])
        ->with(['student:id,name', 'internship:id,title', 'placement.company:id,name'])
        ->paginate($this->perPage);
}
```

### 3.2 Memory-Efficient Bulk Operations

For high-volume exports or batch processing (1000+ rows), use `cursor()` to stream records
one-by-one without loading the entire result set into memory:

```php
// ✅ Memory-safe for large exports
foreach ($this->service->query($filters)->cursor() as $record) {
    $writer->addRow([$record->name, $record->created_at]);
}

// ❌ Loads everything into RAM
$records = $this->service->query($filters)->get();
```

### 3.3 Read-Through Caching (EloquentQuery)

`EloquentQuery` exposes a `remember()` helper for caching query results. Use it in service methods
for data that changes infrequently (settings, academic years, department lists):

```php
public function getActiveAcademicYears(): Collection
{
    return $this->remember('academic-years:active', 600, fn ($svc) =>
        $svc->query(['is_active' => true])->get(['id', 'year'])
    );
}
```

### 3.4 Dropdown Caching in Livewire (Shared Cache)

Livewire's `#[Computed]` memoizes per-request. For dropdown data shared across many concurrent
users, add a shared `Cache::remember()` layer so all users benefit from a single DB read:

```php
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;

/** Cache TTL for dropdown lists that rarely change (5 minutes). */
private const DROPDOWN_TTL = 300;

#[Computed]
public function internships(): \Illuminate\Support\Collection
{
    return Cache::remember(
        'dropdown:internships',
        self::DROPDOWN_TTL,
        fn () => app(InternshipService::class)->all(['id', 'title'])
    );
}
```

**Applied in:** `RegistrationManager` (internships, placements, students, teachers, mentors) and
`PlacementManager` (companies, internships, mentors).

**Cache invalidation:** Dropdown caches use a short TTL (5 min) rather than event-based
invalidation, making them safe without additional listener complexity.

---

## 4. Presentation Layer (Livewire)

### 4.1 Component Deferral

For heavy UI blocks (complex analytics, multi-module widgets), use Livewire's **Lazy Loading** to
prioritize the initial page render before loading secondary panels.

### 4.2 Selective State Synchronization

Minimize Livewire request payload:
- Use `wire:model.live.debounce.500ms` on search inputs to avoid a request on every keystroke.
- Keep public component properties limited to essential UI state.
- Move read-only data into `#[Computed]` properties instead of public properties that get serialized
  into every request.

### 4.3 Modern Computed Properties

Always use `#[Computed]` (Livewire 3) rather than the legacy `getXxxProperty()` pattern:

```php
// ✅ Livewire 3 — memoized within the request lifecycle
#[Computed]
public function summary(): array { ... }

// ❌ Legacy Livewire 2 — still works but bypasses Livewire 3 memoization
public function getSummaryProperty(): array { ... }
```

---

## 5. Production Environment

### 5.1 Cache & Queue Driver

The default `database` driver is adequate for development. For production with concurrent users,
switch to Redis in `.env`:

```dotenv
CACHE_STORE=redis
QUEUE_CONNECTION=redis
```

Redis is already configured in `config/database.php`. Only the `.env` values need changing.

### 5.2 Framework Caching

```bash
php artisan optimize        # config + routes + views + events
php artisan view:cache      # pre-compile Blade templates
```

### 5.3 OPcache & JIT

Enable in `php.ini` for production:

```ini
opcache.enable=1
opcache.jit_buffer_size=256M
opcache.jit=tracing
```

---

## 6. Verification Gate

- Load-test high-frequency endpoints (registration listing, dashboard) against 1500+ concurrent requests.
- Every performance refactor must pass the full suite: `composer test`.
- Monitor query counts with `DB::enableQueryLog()` in development to catch N+1 regressions.

---

_Proactive performance optimization ensures that Internara remains a responsive, scalable, and
resilient system for large-scale institutional deployments._

