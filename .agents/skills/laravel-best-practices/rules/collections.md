# Collections

## What It Enforces

Higher-order messages for simple operations (`$users->each->markAsVip()`). `lazy()` over `cursor()`
when eager loading is needed. `lazyById()` when updating records during iteration. `toQuery()` for
bulk operations on collections. `#[CollectedBy]` for custom collection classes.

## Why It Matters

Higher-order messages reduce boilerplate — `$users->each->markAsVip()` is shorter and clearer than
`$users->each(fn ($u) => $u->markAsVip())`. `cursor()` doesn't support eager loading, so `lazy()` is
needed when accessing relationships during iteration. `lazyById()` uses indexed ordered-by-ID
pagination which doesn't skip or repeat rows when records are modified during iteration.

## When It Applies

- Simple iteration: higher-order messages (`each->`, `map->`, `filter->`)
- Large read-only datasets with eager loading: `lazy()` over `cursor()`
- Large datasets being modified during iteration: `lazyById()`
- Bulk updates/deletes from a collection: `$collection->toQuery()->update([...])`
- Custom collection methods: `#[CollectedBy(CustomCollection::class)]` attribute
- Splitting collections: `[$active, $inactive] = $collection->partition(fn ($item) => ...)`

Use `map->` for property access: `$users->map->name`. Use `pluck()` for extracting single columns.

Exceptions: For very small collections (handful of items), the overhead of eager loading or chunking
is unnecessary.
