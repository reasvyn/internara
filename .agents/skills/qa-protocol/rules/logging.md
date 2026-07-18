# Rules: Logging

> ISO 25010 Mapping: Security (Accountability), Reliability
> CWE: CWE-778 (Insufficient Logging), CWE-532 (Info Exposure Through Log Files)
> Applicability: All applications

## Overview

Proper logging supports debugging, auditing, and security monitoring while protecting
user privacy. Logs must never contain sensitive data.

## Core Principles

1. **Log security-relevant events** — auth failures, access denials, mutations
2. **Never log sensitive data** — passwords, tokens, PII, credit cards
3. **Use structured logging** — consistent format, parseable
4. **Use appropriate log levels** — not everything is an error
5. **Protect against log injection** — sanitize user input before logging

## What to Check

### 1. PII in Logs

```php
// BAD — PII in logs
Log::info("User login: email={$user->email}");
Log::info("Password reset request for {$email}");
Log::info("API call", ['token' => $request->bearerToken()]);
Log::debug("Request data", ['password' => $request->password]);
Log::info("Payment processed", ['card' => $request->card_number]);

// GOOD — sanitized
Log::info("User login", ['user_id' => $user->id]);
Log::info("Password reset requested", ['user_id' => $user->id]);
Log::info("API call", ['user_id' => auth()->id()]);
Log::debug("Request received", ['endpoint' => $request->path()]);
Log::info("Payment processed", ['order_id' => $order->id, 'amount' => $amount]);
```

**Detection:**
```bash
# Search for PII patterns in log calls
grep -rn "Log::.*email\|Log::.*password\|Log::.*token\|Log::.*phone" app/
```

### 2. Sensitive Data Masking

```php
// GOOD — explicit masking
class SmartLogger
{
    public static function withPiiMasking(array $data): array
    {
        return collect($data)->mapWithKeys(function ($value, $key) {
            if (in_array($key, ['email', 'password', 'token', 'phone', 'ssn'])) {
                return [$key => '***MASKED***'];
            }
            return [$key => $value];
        })->toArray();
    }
}
```

### 3. Appropriate Log Levels

| Level | When to Use | Example |
|-------|-------------|---------|
| **Emergency** | System is unusable | Database server down |
| **Alert** | Immediate action needed | Security breach detected |
| **Critical** | Critical conditions | Payment processing failed |
| **Error** | Error conditions | Exception caught, operation failed |
| **Warning** | Warning conditions | Deprecated feature used, rate limit approaching |
| **Notice** | Normal but significant | User account locked, new admin created |
| **Info** | Informational | Request processed, job completed |
| **Debug** | Detailed debug info | Variable values, query logs |

```php
// BAD — everything logged at error level
Log::error("User {$userId} viewed dashboard");
Log::error("Cache hit for key {$key}");

// GOOD — appropriate levels
Log::info("User viewed dashboard", ['user_id' => $userId]);
Log::debug("Cache hit", ['key' => $key]);
Log::error("Payment failed", ['order_id' => $orderId, 'error' => $e->getMessage()]);
```

### 4. Log Injection Prevention

```php
// BAD — user input in log without sanitization
Log::info("User comment: " . $request->input('comment'));
// Comment could contain: "admin_login\ncritical: system compromised"

// GOOD — user input as structured data
Log::info("User comment posted", [
    'user_id' => auth()->id(),
    'comment_length' => strlen($request->input('comment')),
]);
// Or sanitize: str_replace(["\n", "\r"], '', $comment)
```

### 5. Security Event Logging

Events that MUST be logged:

| Event | Log Level | Data to Include |
|-------|-----------|-----------------|
| Successful login | Notice | user_id, IP, user_agent |
| Failed login | Warning | email/IP attempted, reason |
| Password change | Notice | user_id |
| Password reset request | Notice | user_id |
| Account lockout | Alert | user_id, reason |
| Unauthorized access attempt | Warning | user_id, resource, IP |
| Admin action | Notice | admin_id, action, target |
| Data export | Notice | user_id, scope |
| Role/permission change | Notice | admin_id, target_user, old_role, new_role |

### 6. Log Channel Configuration

```php
// config/logging.php — check channels are properly configured
'channels' => [
    'stack' => ['driver' => 'log'],  // Development only
    'daily' => ['driver' => 'daily', 'days' => 30],  // Production
    'stderr' => ['driver' => 'errorlog'],  // Docker/CLI
],
```

**Checks:**
- [ ] Not using `stack` channel in production (writes to single file)
- [ ] Log rotation configured (daily, max 30 days)
- [ ] Separate channels for different concerns (security, audit, application)
- [ ] Log directory not web-accessible

## Detection Scripts

```bash
# Find all Log:: calls with potential PII
grep -rn "Log::" app/ --include="*.php" | grep -i "email\|password\|token\|phone\|address\|ssn\|credit"

# Find all dd/dump/ray calls
grep -rn "dd(\|dump(\|ray(" app/ --include="*.php"

# Find Log::error without context
grep -rn "Log::error(" app/ --include="*.php" | grep -v "\["
```

## Severity Classification

| Finding | Severity |
|---------|----------|
| Passwords/tokens in logs | Critical |
| PII (email, name) in logs | High |
| No security event logging | High |
| Everything logged at same level | Low |
| Log injection possible | Medium |
| No log rotation | Medium |
| Logs web-accessible | High |
| Debug logs in production | Low |
