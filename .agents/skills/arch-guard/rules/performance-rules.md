# Performance Rule Checks — P1-P5 Query & Data Anti-Patterns

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

Performance rules (P1-P5) catch the query and data-shape anti-patterns that make a self-hosted PKL
platform crawl under real workloads. They are the lowest priority in the rule reference hierarchy but
the most frequently cited by reviewers inspecting Blade loops and Read Actions.

---

## Performance Rules Table

| ID | Rule | Detection |
|----|------|-----------|
| **P1** | No N+1 queries: eager load with `with()` | Missing `with()` on relationship access |
| **P2** | Use `select()` to limit columns | `Model::all()` without column selection |
| **P3** | Use `chunk()`/`cursor()` for large datasets | `->get()` on potentially large collections |
| **P4** | Cache expensive queries | Repeated expensive queries without caching |
| **P5** | Use `exists()` instead of `count() > 0` | `count() > 0` pattern |

## Intent

Each rule exists to keep the 1+N query count, memory footprint, and latency bounded as the dataset
grows — a single school's intern records multiply into thousands of evaluations and logbook entries.

## Per-Rule Rationale & How to Apply

### P1 — No N+1 queries

**Why it exists:** Accessing a relationship in a loop triggers one query per iteration. A page
listing 100 enrollments each fetching its student + placement fires `1 + 2×100` queries.

**How to apply:** Eager load anywhere a relationship is read in a loop:

```php
$enrollments = Enrollment::with('student.user', 'placement')->get();
```

**Failure mode if ignored:** A dashboard renders with hundreds of hidden queries; each page load
hammers the DB queue and response time triples as the cohort grows.

### P2 — Select only needed columns

**Why it exists:** `Model::all()` (and `->get()` without a select) hydrates every column, including
unused text/blob content, inflating memory and transfer for every row.

**How to apply:**

```php
$names = Student::select('id', 'name')->get();
```

**Failure mode if ignored:** A label dropdown loads full `students` rows (including photo media and
biography fields) hundreds of times per request.

### P3 — Chunk/cursor large datasets

**Why it exists:** `->get()` on a large result materializes the whole collection in memory;
exporting 10,000 intern records at once can exhaust PHP's memory limit.

**How to apply:**

```php
// Batch-processing pattern
Student::where(...)->chunkById(500, function ($students) {
    // process batch
});

// Streaming pattern for export
Student::cursor()->each(fn ($student) => ...);
```

**Failure mode if ignored:** The CSV export utility (CSV is explicitly in-scope for this project)
materializes the full table and crashes under its own weight.

### P4 — Cache expensive queries

**Why it exists:** Repeated, computation-heavy or hot reads (dashboard stats, sidebar counters) hit
the DB every request when the answer changes rarely.

**How to apply:**

```php
Cache::remember(config('cache-keys.reports.metrics'), 3600, fn () => $this->query());
```

(Keys registered in `config/cache-keys.php` — C4, see `invariant-enforcement.md`.)

**Failure mode if ignored:** Every dashboard render re-aggregates the same totals; the report module
becomes the shared bottleneck for all teachers.

### P5 — `exists()` instead of `count() > 0`

**Why it exists:** `count()` must fetch and count rows; `exists()` short-circuits at the first
matching row — a cheaper plan for a yes/no question.

**How to apply:**

```php
if (Enrollment::where('student_id', $id)->exists()) {
    // ...
}
```

**Failure mode if ignored:** Presence checks on a large table scan whole indexes for a boolean answer
that could stop at the first row.

---

## Severity Guidance

- **P1 (N+1)** — **MEDIUM** (maintainability/performance) but promote to **HIGH** when the loop
  count is unbounded or user-triggered repeatedly.
- **P2/P3** — **LOW** to **MEDIUM**, escalate when the dataset is large or the call is on a hot path.
- **P4/P5** — **LOW** (optimization nits) unless the micro-fix is on a confirmed bottleneck.

Performance findings are rarely critical; they become critical only when they turn into timeouts in
production (then also check `scan_dead_code.py`/monitoring for the root cause).

## Verification

```bash
python3 scripts/scan_violations.py            # includes P1-P5 alongside C1-C8/D1-D6
python3 scripts/scan_violations.py --module {Name}
```

**Interpretation guidance:** the scanner detects *potential* N+1/`->get()` sites; confirm the loop
context and data size before filing. Pair with `docs/architecture/cache-pattern.md` and
`docs/infrastructure/database.md` for the cache-key registration and query-tuning references.