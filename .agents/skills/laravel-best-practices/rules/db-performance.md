# Database Performance

## What It Enforces

Always eager load relationships to prevent N+1. Select only needed columns. Chunk large datasets instead of loading all records. Add indexes for WHERE, ORDER BY, JOIN, and GROUP BY columns. Use `withCount()` instead of loading entire collections just to count. Never execute queries in Blade views.

## Why It Matters

N+1 is the most common performance issue in Laravel applications — one query for the parent records plus N queries for each child's relationship. Eager loading collapses this to 2 queries. Column restriction reduces data transfer. Chunking prevents memory exhaustion on large datasets. Indexes make queries fast at scale.

## When It Applies

Every query should be examined for:
- Eager loading: `with('relationship')` for all accessed relationships
- Column selection: `select('id', 'name')` and `with('rel: id,foreign_key,name')` instead of `SELECT *`
- Counting: `withCount('relation')` instead of loading the collection and calling `->count()`
- Large datasets: `chunk(200)` or `chunkById(200)` for batch processing; `cursor()` for read-only iteration; `lazy()` when eager loading is needed
- Indexes: add in migrations for columns used in WHERE, ORDER BY, JOIN, GROUP BY
- Subqueries: prefer `whereIn` + subquery over `whereHas` for better performance on correlated queries
- Blade: no Eloquent calls in templates

N+1 detection: enable `Model::preventLazyLoading(! app()->isProduction())` in AppServiceProvider to catch lazy loading during development.

Exceptions: Small, static datasets can skip eager loading. Admin reporting pages may use `cursor()` for memory efficiency on large exports.
