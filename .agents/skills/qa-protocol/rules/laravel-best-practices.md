# Rules: Laravel Best Practices

> Source: https://laravel.com/docs/master, Laravel Community Standards
> Version: Laravel 10.x / 11.x / 12.x / 13.x
> Applicability: Laravel applications

## Overview

Industry-accepted best practices for Laravel development. These are framework-specific
patterns that go beyond PSR standards.

## 1. Controllers Should Be Thin

Controllers should only handle HTTP concerns — request parsing, calling services, and
returning responses. No business logic.

```php
// BAD — business logic in controller
class UserController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([...]);
        
        // Business logic in controller
        if (User::where('email', $validated['email'])->exists()) {
            throw new \Exception('Email taken');
        }
        
        $user = User::create($validated);
        
        // More business logic
        Mail::to($user)->send(new WelcomeMail($user));
        
        return redirect()->route('users.index');
    }
}

// GOOD — thin controller
class UserController extends Controller
{
    public function store(CreateUserRequest $request, CreateUserAction $action): RedirectResponse
    {
        $action->execute($request->validated());
        
        return redirect()->route('users.index')
            ->with('success', __('User created.'));
    }
}
```

## 2. Use Form Requests for Complex Validation

```php
// GOOD — dedicated FormRequest
class CreateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }
    
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }
}
```

**When to use FormRequest vs inline validation:**
- FormRequest: Complex rules, custom error messages, authorization logic
- Inline `$request->validate()`: Simple, 1-2 rules, no auth logic

## 3. Use Eloquent Over Raw Queries

```php
// BAD — raw query builder
$users = DB::table('users')
    ->join('posts', 'users.id', '=', 'posts.user_id')
    ->select('users.*', DB::raw('COUNT(posts.id) as post_count'))
    ->groupBy('users.id')
    ->get();

// GOOD — Eloquent with eager loading
$users = User::withCount('posts')->get();

// GOOD — Eloquent scope for complex queries
class User extends Model
{
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
```

## 4. Use Form Requests, Not `$request->all()`

```php
// BAD — mass assignment risk
User::create($request->all());

// GOOD — validated data only
User::create($request->validated());

// GOOD — explicit array
User::create($request->only(['name', 'email']));
```

## 5. Use Events for Cross-Module Side Effects

```php
// BAD — tight coupling
class CreateOrderAction
{
    public function execute(OrderData $data): Order
    {
        $order = Order::create($data->toArray());
        
        // Tight coupling to Inventory module
        Inventory::decrement('stock', $data->quantity);
        
        // Tight coupling to Notification module
        Mail::to($order->customer)->send(new OrderConfirmation($order));
        
        return $order;
    }
}

// GOOD — events for side effects
class CreateOrderAction
{
    public function execute(OrderData $data): Order
    {
        $order = Order::create($data->toArray());
        
        OrderCreated::dispatch($order);
        
        return $order;
    }
}

// Listeners handle side effects independently
class DecrementInventory implements ShouldQueue
{
    public function handle(OrderCreated $event): void
    {
        Inventory::decrement('stock', $event->order->quantity);
    }
}
```

## 6. Use Service Classes for Infrastructure

```php
// BAD — infrastructure logic in model
class User extends Model
{
    public function generateReport(): string
    {
        $pdf = PDF::loadView('users.report', ['user' => $this]);
        Storage::put("reports/{$this->id}.pdf", $pdf->output());
        return Storage::url("reports/{$this->id}.pdf");
    }
}

// GOOD — service for infrastructure
class UserReportService
{
    public function generateForUser(User $user): string
    {
        $pdf = PDF::loadView('users.report', ['user' => $user]);
        $path = "reports/{$user->id}.pdf";
        Storage::put($path, $pdf->output());
        return Storage::url($path);
    }
}
```

## 7. Use Route Model Binding

```php
// BAD — manual lookup
Route::get('/users/{id}', function ($id) {
    $user = User::findOrFail($id);
    return view('users.show', compact('user'));
});

// GOOD — route model binding
Route::get('/users/{user}', function (User $user) {
    return view('users.show', compact('user'));
});
```

## 8. Use API Resources for JSON Responses

```php
// BAD — raw array
return response()->json([
    'id' => $user->id,
    'name' => $user->name,
    'email' => $user->email,
]);

// GOOD — API Resource
return new UserResource($user);

// GOOD — Collection Resource
return UserResource::collection($users);
```

## 9. Use Queues for Heavy Operations

```php
// BAD — synchronous heavy operation
class ExportController extends Controller
{
    public function export()
    {
        $pdf = PDF::loadView('export.all-data'); // Blocks for seconds/minutes
        return $pdf->download('export.pdf');
    }
}

// GOOD — queue the job, return job ID
class ExportController extends Controller
{
    public function export()
    {
        $job = GenerateExport::dispatch(auth()->user());
        
        return response()->json([
            'job_id' => $job->getJobId(),
            'message' => __('Export is being generated. You will be notified when ready.'),
        ]);
    }
}
```

## 10. Use Caching Appropriately

```php
// BAD — caching in controller/view
$user = Cache::remember("user_{$id}", 3600, function () use ($id) {
    return User::with('posts')->find($id);
});

// GOOD — caching in service/action layer
class ReadUserProfileAction
{
    public function execute(string $userId): User
    {
        return Cache::remember(
            "user_profile_{$userId}",
            3600,
            fn () => User::with('posts')->findOrFail($userId)
        );
    }
}
```

## 11. No Eloquent in Blade

```blade
{* BAD — database query in view *}
@foreach (User::where('active', true)->get() as $user)
    {{ $user->name }}
@endforeach

{* GOOD — pass data from controller/component *}
@foreach ($activeUsers as $user)
    {{ $user->name }}
@endforeach
```

## 12. Use Laravel Collections Over Raw Arrays

```php
// BAD — manual array processing
$results = [];
foreach ($users as $user) {
    if ($user->active) {
        $results[] = [
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}

// GOOD — collection chain
$results = collect($users)
    ->filter(fn ($user) => $user->active)
    ->map(fn ($user) => ['name' => $user->name, 'email' => $user->email])
    ->values();
```

## Anti-Patterns Summary

| Anti-Pattern | Better Approach |
|-------------|----------------|
| Business logic in controllers | Service/Action classes |
| `Model::create($request->all())` | `Model::create($request->validated())` |
| `DB::raw()` with variables | Eloquent/Query Builder with bindings |
| Eloquent queries in Blade | Pass data from controller |
| Synchronous heavy work | Queue jobs |
| No error handling | try/catch + user-friendly messages |
| `dd()`, `dump()` in code | Log channels or remove |
| Global functions | Service classes with DI |
| Facade abuse in tests | Interface-based mocking |
| No type hints | Strict types + property types |
