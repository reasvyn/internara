# HTTP Client

## What It Enforces

Every HTTP request sets explicit timeouts. Retries use exponential backoff. Errors are handled
explicitly with `throw()` or conditional checks. `Http::pool()` enables concurrent requests. Tests
use `Http::fake()` with `preventStrayRequests()`.

## Why It Matters

Default timeout (30 seconds) is too long for most API calls — a slow external service could tie up
workers for 30 seconds before failing. Explicit timeouts (5s with 3s connect timeout) fail fast.
Retries with backoff handle transient failures without hammering the service. Pooling reduces
sequential wait time for independent requests.

## When It Applies

Every HTTP client request should:

- Set `timeout()` and `connectTimeout()` explicitly
- Define service-specific macros in AppServiceProvider for base URL, auth, and defaults
- Use `retry()` with backoff array for transient failures
- Handle errors with `throw()` or explicit `successful()/failed()` checks
- Use `Http::pool()` for concurrent independent requests

Testing: always use `Http::preventStrayRequests()` to catch un-faked HTTP calls, then `Http::fake()`
with response fixtures. Assert specific requests were sent.

Exceptions: Internal service calls on the same network may use longer timeouts.
