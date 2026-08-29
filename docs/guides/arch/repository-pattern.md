# Repository Pattern — Why Internara Doesn't Use It

## Description

Explanation of why Internara does not use the Repository pattern — direct Eloquent usage through
Models and Read Actions instead. Grounded in **Repository Pattern** (PoEAA), **Data Mapper** vs
**Active Record**, and **Query Object** — all mapped to Internara's Eloquent-first architecture.

---

## Non-Negotiable

Hard rules. Violations are architecture violations.

1. **No Repository layer.** This is an explicit architectural decision. Eloquent IS the Repository. Adding a Repository abstraction between Livewire/Controllers and Eloquent introduces accidental complexity without payoff.

2. **Simple queries inline in Livewire.** Single-table lookups, straightforward `where` clauses, relationship eager loading — written directly in the Livewire component's `render()` method. Keep it simple.

3. **Complex queries in Read Actions.** Aggregations, cross-module data assembly, multi-step filtering with business rules — extracted into Read Actions. Read Actions extend `BaseReadAction` and MUST NOT mutate state.

4. **Reusable scopes on Models.** Reusable query fragments belong as local scopes on the Model. This is equivalent to repository methods but lives on the model itself.

5. **No repository interfaces or bindings.** If you find yourself creating a repository interface, an Eloquent implementation, and a container binding — stop. You are adding abstraction for a single implementation with no planned alternative.

---

## How to Apply

### 1. Repository Pattern (PoEAA)

Martin Fowler's Repository pattern mediates between domain and data mapping, acting like an in-memory collection of domain objects. In traditional DDD, repositories hide persistence details behind interfaces. In Internara, Eloquent already provides this abstraction — `Model::find()`, `Model::where()`, `Model::with()` are the repository methods.

**Reference:** [PoEAA — Repository](https://martinfowler.com/eaaCatalog/repository.html)

### 2. Active Record vs Data Mapper

| Pattern | Implementation | Pros | Cons |
|---------|---------------|------|------|
| **Active Record** | Eloquent Model | Simple, Laravel-native,Convention over Configuration | Tightly couples domain to persistence |
| **Data Mapper** | Separate mapper class | Clean domain, persistence-agnostic | Extra layer, more complex |

Internara uses Active Record (Eloquent) because:
- Single ORM (Eloquent) with one query builder
- No second persistence mechanism to abstract from
- Laravel's testing tools (`DatabaseMigrations`, `DatabaseTransactions`, factories) replace repository mocking
- Model scopes already provide query reuse

### 3. Query Object Pattern

For complex queries that exceed simple scopes, use Read Actions instead of Query Objects. Read Actions encapsulate multi-step queries with business logic, caching, and cross-module assembly. This is simpler than Query Objects and integrates with the Action Triad.

### 4. When to Extract a Read Action

| Threshold | Description |
|-----------|-------------|
| Repeated in 2+ locations | Same filter + aggregation used in multiple places |
| Business logic in queries | Multi-condition rules that encode domain policy |
| Cross-module queries | Joining data from disparate modules |
| Complex aggregation | Multi-step calculations with conditional sums |
| Caching requirement | Query results that should be cached with invalidation |

---

## Anti-Patterns

| You see... | It should be... | Violation |
|-----------|----------------|-----------|
| Creating `UserRepositoryInterface` + `EloquentUserRepository` | Use `User` Model directly or Read Action | Unnecessary abstraction |
| Repository with single `find()` method | Use `Model::find()` directly | Wrapper for no value |
| Repository with business logic | Extract to Entity + Action | Business logic in persistence layer |
| Repository called from Livewire for simple queries | Write query inline in component | Over-engineering simple lookups |
| Repository interface + container binding for one implementation | Use concrete class directly | Abstract for single implementation |
| Repository that wraps `DB::table()` | Use Eloquent directly | Bypassing ORM for no reason |
| Repository test mocking Eloquent | Use model factories + `DatabaseTransactions` | Fighting the framework |
| Repository accumulating methods over time | Split into Read Actions (one per query) | God Object — too many methods |

---

## Quick References

- `docs/conventions.md` §Query Patterns — inline vs Read Action
- `docs/guides/arch/action-pattern.md` — Read Actions for complex queries
- `docs/guides/arch/model-pattern.md` — Model scopes and relationships
- [PoEAA — Repository](https://martinfowler.com/eaaCatalog/repository.html) — Repository pattern
- [PoEAA — Active Record](https://martinfowler.com/eaaCatalog/activeRecord.html) — Active Record pattern
- [PoEAA — Data Mapper](https://martinfowler.com/eaaCatalog/dataMapper.html) — Data Mapper pattern
- [Laravel — Eloquent](https://laravel.com/docs/eloquent) — Eloquent ORM
- [Query Object Pattern](https://martinfowler.com/eaaCatalog/queryObject.html) — complex query encapsulation
- [DDD — Repository](https://martinfowler.com/eaaCatalog/repository.html) — DDD Repository concept
