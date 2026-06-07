# Task Scheduling

## What It Enforces

Scheduled tasks use `withoutOverlapping()` on variable-duration operations. `onOneServer()` prevents
duplicate execution on multi-server deployments. `runInBackground()` enables concurrent task
execution. `environments()` restricts tasks to specific environments. Schedule groups share common
configuration.

## Why It Matters

`withoutOverlapping()` prevents a long-running task from starting a second instance while the first
is still running — common for data imports, report generation, and batch processing. `onOneServer()`
ensures that on a multi-server deployment, only one server executes the task (requires shared
cache). `runInBackground()` allows tasks scheduled at the same time to run concurrently rather than
sequentially.

## When It Applies

- Variable-duration tasks: always use `withoutOverlapping()`
- Multi-server deployments: use `onOneServer()` (requires shared Redis/Memcached/database cache)
- Concurrent tasks at the same schedule time: use `runInBackground()`
- Environment-specific tasks: use `environments(['production'])`
- Shared configuration: use schedule groups to avoid repeating options
- Debugging: use `sendOutputTo()` to capture command output
- Conditional execution: combine with `Cache::has()` and `when()` to gate on conditions

Exceptions: Short, idempotent tasks that run on a single server may not need these protections.
