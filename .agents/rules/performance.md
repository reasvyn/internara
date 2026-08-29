# Rules: Performance & Efficiency

> ISO 25010 Mapping: Performance Efficiency
> Applicability: All web applications

## Overview

Performance evaluation covers database efficiency, caching, resource management, and
frontend performance. This is NOT about benchmarks — it's about identifying anti-patterns.

## 1. Database Performance

### N+1 Query Detection

The most common performance issue in Laravel applications.

```php
// BAD — N+1: 1 query for users + N queries for posts
$users = User::all();
foreach ($users as $user) {
    echo $user->posts->count(); // Each access triggers a query
}

// GOOD — 2 queries total
$users = User::withCount('posts')->get();
// or
$users = User::with('posts')->get();
```

**Detection in Blade:**
```blade
{{-- N+1 risk: accessing relationship in loop --}}
@foreach ($orders as $order)
    {{ $order->customer->name }}  {{-- N+1 if customer not eager loaded --}}
    @foreach ($order->items as $item)
        {{ $item->product->name }}  {{-- N+1 if items/products not loaded --}}
    @endforeach
@endforeach
```

**Detection in Code:**
```bash
# Look for relationship access in loops
grep -rn "->each\|@foreach\|@forelse" resources/views/ app/
```

### Missing Eager Loading

```php
// BAD — lazy loading in loop
$posts = Post::all();
foreach ($posts as $post) {
    $post->author->name;  // Triggers query per post
}

// GOOD — eager loading
$posts = Post::with('author')->get();
```

## Unbounded Queries

```php
// BAD — no limit, could return millions
$users = User::where('active', true)->get();

// GOOD — pagination
$users = User::where('active', true)->paginate(50);

// GOOD — chunking for processing
User::where('active', true)->chunk(100, function ($users) {
    foreach ($users as $user) {
        // Process in chunks
    }
});
```

## Missing Indexes

```php
// Check: columns used in WHERE, JOIN, ORDER BY should be indexed
// BAD — no index on frequently queried column
Schema::table('orders', function (Blueprint $table) {
    $table->string('status');  // Used in WHERE but not indexed
});

// GOOD — index on frequently queried column
Schema::table('orders', function (Blueprint $table) {
    $table->string('status')->index();
});
```

**Detection:**
```bash
# Check migration files for missing indexes
grep -rn "->index\|->unique\|->primary" database/migrations/
```

## SELECT * Usage

```php
// BAD — fetches all columns
$users = DB::table('users')->get();
User::all();

// GOOD — select specific columns
$users = User::select('id', 'name', 'email')->get();
```

## N+1 in Collection Operations

```php
// BAD — N+1 in map
$users = User::all()->map(function ($user) {
    return [
        'name' => $user->name,
        'posts_count' => $user->posts->count(), // N+1
    ];
});

// GOOD — eager load before collection
$users = User::withCount('posts')->get()->map(function ($user) {
    return [
        'name' => $user->name,
        'posts_count' => $user->posts_count,
    ];
});
```

## Caching Opportunities

```php
// BAD — expensive query on every request
function getDashboardStats(): array
{
    return [
        'total_users' => User::count(),
        'active_users' => User::where('active', true)->count(),
        'total_orders' => Order::count(),
        'revenue' => Order::sum('total'),
    ];
}

// GOOD — cache with appropriate TTL
function getDashboardStats(): array
{
    return Cache::remember('dashboard_stats', 300, function () {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('active', true)->count(),
            'total_orders' => Order::count(),
            'revenue' => Order::sum('total'),
        ];
    });
}
```

## Memory Usage

```php
// BAD — loads all into memory
$allUsers = User::all();
$filtered = $allUsers->filter(fn ($u) => $u->active);

// GOOD — database-level filtering
$activeUsers = User::where('active', true)->get();
```

## Frontend Performance

### Bundle Size

```bash
# Check build output size
npm run build
ls -la public/build/assets/
```

### Render-Blocking Resources

```blade
{{-- BAD — render blocking --}}
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto">
<script src="https://example.com/script.js"></script>

{{-- GOOD — async/defer --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto">
<script src="https://example.com/script.js" defer></script>
```

## Performance Metrics

| Metric | Target | Warning |
|--------|--------|---------|
| Time to First Byte (TTFB) | < 200ms | > 500ms |
| Database queries per page | < 15 | > 30 |
| Page load time (LCP) | < 2.5s | > 4s |
| Total page weight | < 500KB | > 2MB |
| JavaScript bundle | < 200KB | > 500KB |
| N+1 queries | 0 | Any |

## Severity Classification

| Finding | Severity |
|---------|----------|
| N+1 on page with 100+ items | High |
| Unbounded query on large table | High |
| Missing index on FK used in JOIN | Medium |
| SELECT * on wide table (>20 cols) | Low |
| No caching on expensive aggregate | Medium |
| Bundle size > 1MB | Medium |
| Missing `defer` on non-critical JS | Low |
