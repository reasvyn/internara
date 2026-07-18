# Rules: Authentication & Authorization

> OWASP: A07 (Identification and Authentication Failures), A01 (Broken Access Control)
> CWE: CWE-287 (Improper Authentication), CWE-862 (Missing Authorization)
> Applicability: All applications with user accounts

## Overview

Authentication verifies identity. Authorization verifies permission. Both must be enforced
at every layer — middleware, controller, and where needed, at the service/action level.

## Authentication Checks

### 1. Login Security

```php
// Check: Rate limiting on login
Route::post('/login', [LoginController::class, 'login'])
    ->middleware('throttle:5,1'); // 5 attempts per minute

// Check: Account lockout after failed attempts
// Check: Credentials validated server-side
// Check: Session regenerated after successful login
```

```php
// BAD — no session regeneration
Auth::login($user);
return redirect('/dashboard');

// GOOD — session regeneration
Auth::login($user);
$request->session()->regenerate();
return redirect()->intended('/dashboard');
```

### 2. Password Policy

```php
// Check minimum requirements:
// - At least 8 characters
// - Mixed case
// - Numbers
// - Special characters (optional but recommended)

// GOOD — Laravel Password rules
use Illuminate\Validation\Rules\Password;

'password' => ['required', Password::min(8)->mixedCase()->numbers()->symbols()]
```

### 3. Password Storage

```php
// BAD — plaintext
$user->password = $request->password;

// GOOD — hashed
$user->password = Hash::make($request->password);

// Check: bcrypt or argon2id used (Laravel default is bcrypt)
```

### 4. Password Reset Flow

```php
// Check:
// - Rate limiting on reset requests
// - Token expires after reasonable time (1 hour)
// - Token is single-use
// - Old tokens invalidated on new request
// - Success/error messages don't reveal if email exists
```

### 5. Multi-Factor Authentication (if applicable)

```php
// Check:
// - MFA available for sensitive operations
// - Recovery codes provided
// - MFA bypass prevented
```

## Authorization Checks

### 1. Route-Level Authorization

```php
// Check: Every route has appropriate middleware
Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class);
    Route::post('/admin/settings', [SettingsController::class, 'update'])
        ->middleware('can:manage,settings');
});

// BAD — no middleware
Route::post('/admin/delete-user/{user}', [AdminController::class, 'deleteUser']);

// GOOD — auth + policy
Route::post('/admin/delete-user/{user}', [AdminController::class, 'deleteUser'])
    ->middleware('auth', 'can:delete,user');
```

### 2. Controller-Level Authorization

```php
// BAD — no authorization in controller
public function destroy(User $user)
{
    $user->delete();
}

// GOOD — policy check
public function destroy(User $user)
{
    $this->authorize('delete', $user);
    $user->delete();
}
```

### 3. Policy Coverage

```php
// Check: Every model with mutations has a Policy
// Check: Policy methods cover all actions
// Check: Policy logic is correct (not just role-based but context-aware)

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can list
    }
    
    public function view(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $user->can('admin');
    }
    
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
    
    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $user->can('admin');
    }
}
```

### 4. IDOR Prevention

```php
// BAD — no ownership check
public function show(Order $order)
{
    return view('orders.show', compact('order'));
    // Any authenticated user can view any order by ID
}

// GOOD — ownership check via policy
public function show(Order $order)
{
    $this->authorize('view', $order); // Policy checks ownership
    return view('orders.show', compact('order'));
}

// GOOD — scope query to user
public function index()
{
    $orders = auth()->user()->orders()->paginate();
    return view('orders.index', compact('orders'));
}
```

### 5. Middleware Ordering

```php
// BAD — wrong order
Route::middleware(['can:admin', 'auth'])->group(function () {
    // If not authenticated, `can:admin` may fail unexpectedly
});

// GOOD — auth first, then authorization
Route::middleware(['auth', 'can:admin'])->group(function () {
    // Guaranteed authenticated before authorization check
});
```

### 6. Role-Based Access Control

```php
// Check:
// - Roles are defined and documented
// - Role checks use the framework's RBAC system
// - No hardcoded role strings in controllers
// - Role hierarchy is correct (admin > teacher > student)
// - Roles can be changed without code modification
```

## Authorization Patterns in Livewire

```php
// BAD — no authorization
public function delete(Post $post): void
{
    $post->delete();
}

// GOOD — policy check
public function delete(Post $post): void
{
    $this->authorize('delete', $post);
    $post->delete();
}

// GOOD — inline check (less preferred but acceptable)
public function delete(Post $post): void
{
    if (auth()->id() !== $post->user_id) {
        abort(403);
    }
    $post->delete();
}
```

## Detection

```bash
# Find routes without auth middleware
grep -rn "Route::" routes/ | grep -v "middleware"

# Find controllers without authorization
grep -rn "function destroy\|function update\|function store" app/ | grep -v "authorize\|policy"

# Find $this->authorize usage
grep -rn "\$this->authorize" app/

# Find Policy files
find app/ -name "*Policy.php"

# Find models without policies
find app/ -name "*.php" -path "*/Models/*" | while read model; do
    name=$(basename "$model" .php)
    find app/ -name "${name}Policy.php" | grep -q . || echo "MISSING: $name"
done
```

## Severity Classification

| Finding | Severity |
|---------|----------|
| No auth middleware on protected route | Critical |
| No authorization check on mutation | High |
| IDOR — can access other users' data | Critical |
| Password stored in plaintext | Critical |
| No rate limiting on login | High |
| Session not regenerated after login | Medium |
| No password policy | Medium |
| Policy exists but logic is wrong | High |
| Role check hardcoded in controller | Medium |
