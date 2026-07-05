# Repository Pattern — Why Internara Doesn't Use It

> **Last updated:** 2026-06-10 **Changes:** initial metadata — no content changes

## Description

Explanation of why Internara does not use the Repository pattern — direct Eloquent usage through
Models and Read Actions instead.

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

---

## 2. Eloquent as the Repository

Eloquent _is_ the Repository. The `Model` class provides:

| Capability                          | Role                   |
| ----------------------------------- | ---------------------- |
| `find()`, `findOrFail()`            | Single-record lookup   |
| `where()`, `orWhere()`, `whereIn()` | Filtering              |
| `first()`, `firstOrFail()`          | Conditional retrieval  |
| `get()`, `paginate()`, `cursor()`   | Collection retrieval   |
| `with()`, `load()`                  | Eager loading          |
| `create()`, `update()`, `delete()`  | Persistence operations |
| `exists()`, `doesntExist()`         | Existence checks       |

---

## 3. Simple Query Pattern (inline in Livewire)

**Simple queries** — single-table lookups, straightforward `where` clauses, relationship eager
loading — are written **directly in the Livewire component**.

**Rule of thumb:** If the query fits in a single fluent chain with no business logic interleaved,
keep it inline.

### Authorization

Even simple queries must pass through authorization (Layer 4 — Presentation/UI) via
`Gate::authorize()` or `$this->authorize()` in Livewire.

---

## 4. Complex Query Pattern (Read Actions)

**Complex queries** — aggregations, cross-module data assembly, multi-step filtering with business
rules — are extracted into **Read Actions** (Layer 3 — Business/Domain Ops).

Read Actions extend **BaseReadAction**. They MUST NOT mutate state, call `transaction()`, or call
`log()`.

**Naming:** `Read{Entity}Action`.

---

## 5. Query Scopes on Models

Reusable query fragments belong as **local scopes** on the Model. Usage is identical to a repository
method but lives on the model itself.

Scopes should be **named descriptively** and avoid business-logic conditionals. When a scope would
need parameters that encode domain rules, it is time for a Read Action.

**Relationship methods** serve a similar role — they provide discoverability and type safety for
child-record lookups, equivalent to what a dedicated `findByX()` repository method would offer.
Frequently chained filters should be expressed as local scopes on the related model rather than as
repository methods on the parent.

---

## 6. When to Extract a Read Action

Extract a Read Action when the query crosses any of these thresholds:

| Threshold                     | Description                                                               |
| ----------------------------- | ------------------------------------------------------------------------- |
| **Repeated in 2+ locations**  | Same filter + aggregation used in multiple places                         |
| **Business logic in queries** | Multi-condition rules that encode domain policy                           |
| **Cross-module queries**      | Joining data from disparate modules                                       |
| **Complex aggregation**       | Multi-step calculations with conditional sums, rate computation           |
| **Caching requirement**       | Query results that should be cached with a specific invalidation strategy |

### Decision Table

| Query Type                    | Where It Lives       |
| ----------------------------- | -------------------- |
| `Model::find($id)`            | Inline in Livewire   |
| `Model::where()->get()`       | Inline in Livewire   |
| Relationship chain            | Inline in Livewire   |
| Repeated filter               | Local scope on Model |
| Non-trivial aggregation       | Read Action          |
| Cross-module data assembly    | Read Action          |
| Dashboard data (many metrics) | Read Action          |

---

## 7. Migration Note

If the codebase ever develops a genuine need for a Repository layer — a second read store or a
caching proxy that cannot be handled at the Query Builder level — the migration path is well
understood: introduce a Repository contract, implement it with Eloquent, and bind via the container.
Existing Read Actions can be promoted to Repository implementations without rewriting their callers.

This is not done today because Eloquent already satisfies all query use cases, the Action Triad
already separates read concerns from writes, and the architecture prefers concrete dependencies over
abstracted ones when there is exactly one implementation and no planned alternative.
