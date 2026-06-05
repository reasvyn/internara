# Advanced Query Patterns

## What It Enforces

Subqueries with `addSelect()` fetch has-many values without eager loading. Dynamic relationships via virtual foreign keys use subqueries. Conditional submodules replace multiple count queries. `whereIn` + subquery outperforms `whereHas` for correlated queries. `whereExists()` for pure existence checks.

## Why It Matters

Eager loading an entire has-many relationship just to get one value (e.g., the last login date) is wasteful. A correlated subquery in `addSelect()` fetches exactly the needed value in a single query. Conditional submodules replace N count queries with one query using `CASE` expressions. `whereExists()` is cheaper than `whereHas()` when you only need to check existence (boolean) rather than load any data.

## When It Applies

- Last/has-many value: `addSelect` with correlated subquery
- Dynamic relationship: subquery to get FK + `belongsTo` on the virtual attribute
- Multiple counts: conditional submodules with `selectRaw('count(case when ...)')`
- Existence checks: `whereExists()` over `whereHas()` for boolean-only checks
- Has-many ordering: correlated subquery in `orderBy()` over joins (which duplicate rows)
- Complex queries: sometimes two simple queries with `whereIn` outperform one complex query

Use `toBase()` when hydrating models is unnecessary (you only want scalar values).

Compound index column order must match the `orderBy` column order for the index to be used.

Exceptions: For simple relationships with small datasets, the optimization may not be worth the complexity.
