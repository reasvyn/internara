# Queues & Jobs

## What It Enforces

Jobs set `retry_after` greater than `timeout` to prevent overlapping. Jobs use exponential backoff.
`ShouldBeUnique` prevents duplicate jobs. The `failed()` method handles failure cleanup. Rate
limiting middleware protects external APIs. Batch processing groups related jobs.

## Why It Matters

If `retry_after` is less than or equal to `timeout`, a worker still processing a job can have it
re-dispatched to another worker — causing duplicate work or race conditions. Exponential backoff
prevents hammering failing services. `ShouldBeUnique` prevents the same job being queued multiple
times (e.g., duplicate invoice generation). The `failed()` method ensures cleanup even when the job
exhausts retries.

## When It Applies

Every queued job should:

- Set `retry_after` > `timeout` in config
- Use exponential backoff with `$backoff` property
- Implement `ShouldBeUnique` or `ShouldBeUniqueUntilProcessing` for idempotent operations
- Implement `failed()` for cleanup on exhaustion
- Use `RateLimited` middleware when calling external APIs
- Use batching for related job groups

Notifications should implement `ShouldQueue` by default. Listeners that dispatch side effects should
also queue.

For `retryUntil()` (time-based expiration), set `$tries = 0` to disable the max tries limit.

Exceptions: Low-latency, critical jobs that must process immediately may skip queueing. These are
rare.
