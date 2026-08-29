# Rules: Error Handling

> ISO 25010 Mapping: Reliability (Fault Tolerance, Recoverability)
> Applicability: All applications

## Overview

Proper error handling ensures the application degrades gracefully, doesn't leak sensitive
information, and provides actionable feedback to users and operators.

## Core Principles

1. **Never show stack traces to users** — Always render user-friendly error pages
2. **Never silently swallow errors** — Log them, even if you don't show them
3. **Fail fast** — Validate inputs early, throw early
4. **Use specific exceptions** — Not generic `\Exception`
5. **Don't use exceptions for flow control** — Use them for exceptional cases only

## What to Check

### 1. No Silent Error Swallowing

```php
// BAD — silent catch
try {
    $result = $service->process($data);
} catch (\Exception $e) {
    // Nothing — error silently lost
}

// BAD — empty catch
try {
    $result = $service->process($data);
} catch (\Exception $e) {
    // TODO: handle this
}

// GOOD — at minimum, log it
try {
    $result = $service->process($data);
} catch (\ServiceException $e) {
    Log::error('Service processing failed', [
        'error' => $e->getMessage(),
        'data' => $data,
    ]);
    throw new UserFacingException(__('Processing failed. Please try again.'));
}
```

### 2. No Debug Output in Production

```php
// BAD — debug output
dd($data);
dump($data);
ray($data);
var_dump($data);
print_r($data);
die('debug');
exit('here');

// GOOD — use logging
Log::debug('Debug info', ['data' => $data]);
```

**Detection:**
```bash
grep -rn "dd(\|dump(\|ray(\|var_dump(\|print_r(\|die(\|exit(" app/ --include="*.php"
```

### 3. Consistent Exception Hierarchy

```php
// GOOD — domain-specific exceptions
class InsufficientBalanceException extends \RuntimeException { }
class InvalidOrderStatusException extends \RuntimeException { }
class QuotaExceededException extends \RuntimeException { }

// BAD — generic exceptions for everything
throw new \Exception('something went wrong');
throw new \RuntimeException('invalid');
```

### 4. User-Friendly Error Messages

```php
// BAD — technical error shown to user
throw new \Exception("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'john@email.com' for key 'users_email_unique'");

// GOOD — user-friendly message
throw new ValidationException('This email is already registered.');

// GOOD — translated message
throw new ValidationException(__('auth.email_taken'));
```

### 5. Database Transactions for Multi-Step Mutations

```php
// BAD — partial failure leaves inconsistent state
$order = Order::create($orderData);
$inventory->decrement('stock', $qty); // If this fails, order exists but stock not decremented
$payment->charge($amount); // If this fails, order exists, stock decremented, but no payment

// GOOD — transaction ensures atomicity
DB::transaction(function () use ($orderData, $inventory, $amount) {
    $order = Order::create($orderData);
    $inventory->decrement('stock', $qty);
    Payment::charge($amount, $order);
});
```

### 6. Graceful Degradation

```php
// BAD — hard failure for non-critical feature
$analytics = Analytics::getStats(); // If analytics service is down, entire page crashes
return view('dashboard', compact('analytics'));

// GOOD — graceful degradation
try {
    $analytics = Analytics::getStats();
} catch (\Exception $e) {
    Log::warning('Analytics unavailable', ['error' => $e->getMessage()]);
    $analytics = null; // Page still renders, just without analytics
}
return view('dashboard', compact('analytics'));
```

### 7. Exception in View Rendering

```blade
{* BAD — exception in view crashes entire page *}
{{ $user->profile->settings->theme }}

{* GOOD — null-safe operator *}
{{ $user->profile?->settings?->theme ?? 'default' }}
```

## Laravel Error Pages

```php
// resources/views/errors/404.blade.php
// Should be styled, informative, and NOT leak stack traces

// Check: Does APP_DEBUG=false show a clean error page?
// Check: Are error pages styled consistently with the app?
```

## Error Handling Checklist

| Check | Severity if Missing |
|-------|---------------------|
| `dd()`/`dump()` in production code | High |
| Empty catch blocks | Medium |
| Generic `catch (\Exception $e)` | Low |
| Stack traces shown to users | High |
| Technical error messages to users | Medium |
| Missing DB transactions on multi-step mutations | High |
| No error logging | Medium |
| Missing try/catch on external service calls | Medium |
| No user-friendly error pages | Medium |
| Missing null-safety in views | Low |
