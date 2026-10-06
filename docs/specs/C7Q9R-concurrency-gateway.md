# Concurrency Gate & Request Queue Middleware — Multi-Device Write Synchronization

> **Spec ID:** C7Q9R

## Description

Global concurrency gateway and request queuing middleware for Internara. Synchronizes concurrent
state-changing operations (CRUD actions, Livewire mutations) executed by a single user account
across multiple machines, browser sessions, or tabs. Prevents race conditions, lost updates, and
database lock contention through distributed atomic locking with blocking queue timeouts.

This spec integrates into the HTTP request pipeline defined in
[2CF4Y-middleware-pipeline](2CF4Y-middleware-pipeline.md) and respects base architecture invariants
in [architecture](../architecture.md).

---

## 1. Problem Statements

### PS-1 — Concurrent Multi-Device Mutation Race Conditions

When a single user account is authenticated simultaneously across multiple computers or tabs
(e.g., automated lab testing or multi-workstation usage), concurrent mutating requests (POST, PUT,
PATCH, DELETE) compete without synchronization. Requests read overlapping snapshots, calculate
mutations in parallel, and overwrite each other, causing write-skew, lost updates, and state
corruption.
**→ Requirement:** FR-CCG-002, FR-CCG-004.

### PS-2 — Database Engine Lock Contention & Deadlocks

In file-based databases like SQLite (local/dev/testing), simultaneous write transactions fail
abruptly with `database is locked`. In production RDBMS (MySQL), rapid overlapping mutations on the
same parent and child rows produce transaction deadlocks or lock wait timeouts.
**→ Requirement:** FR-CCG-002, FR-CCG-006, FR-CCG-007.

### PS-3 — Read Performance Degradation from Naive Locking

A naive lock that queues all incoming traffic degrades read performance across the entire system.
Browsing dashboard pages, viewing reports, and polling status do not modify application state and
must never be delayed by write queue locks.
**→ Requirement:** FR-CCG-001.

---

## 2. Goals & Non-Goals

### Goals

- **Serialize write mutations per authenticated user** — queue state-changing requests from the same user identity so mutations execute deterministically in arrival order. *Why:* prevents concurrent lost updates and multi-session race conditions.
- **Pass read operations through immediately** — GET, HEAD, and OPTIONS requests bypass the concurrency gate without lock acquisition. *Why:* preserves responsiveness for reading, reporting, and asset delivery.
- **Provide bounded wait queues with graceful backpressure** — requests wait for active locks up to a configurable timeout; requests that exceed the wait budget receive HTTP 429 with `Retry-After`. *Why:* prevents hung worker threads and server resource exhaustion.
- **Guarantee fail-safe lock release** — locks always release on completion or exception via closure/finally scopes. *Why:* prevents orphaned deadlocks if a downstream controller or action fails.
- **Support optional global write synchronization** — configurable global lock option for single-writer database engines like SQLite during high-concurrency test suites. *Why:* prevents SQLite `database is locked` errors during multi-client automated stress tests.

### Non-Goals

- **Distributed database consensus (Raft/Paxos)** — application-level mutex locking coordinates PHP workers; storage clustering is delegated to the database tier.
- **Long-lived optimistic UI locks** — locks exist strictly for the lifespan of an HTTP request/response cycle (milliseconds to seconds), not hours-long editing locks.

---

## 3. User Stories / Use Cases

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-CCG-001 | Single user performs simultaneous CRUD across 50 workstations without data collision | P0 | F | Full |
| UC-CCG-002 | Concurrent read requests bypass the queue without blocking | P0 | F | Full |
| UC-CCG-003 | Excessive queue wait returns a clean HTTP 429 with Retry-After header | P1 | F | Full |

### 3.1 Workflows

#### UC-CCG-001 — Simultaneous CRUD Across 50 Workstations

User A logs in on 50 lab computers at once and triggers create/update actions across multiple
dashboards simultaneously. `ConcurrencyGateMiddleware` detects mutating HTTP methods and acquires
the atomic lock keyed to User A's ID. Requests 2 through 50 enter the blocking queue. Request 1
executes its action, commits changes to the database, and releases the lock. Request 2 immediately
acquires the lock, reads the freshly committed database state, executes cleanly, and hands off to
Request 3. All 50 mutations complete in serialized sequence without race conditions, lost updates,
or database transaction deadlocks.

#### UC-CCG-002 — Concurrent Read Requests Bypass Gate

While a heavy write action is queued for a user, the user or peer clients load dashboard views,
read notifications, and fetch navigation data via HTTP GET. The gateway immediately passes all
safe HTTP methods through without acquiring locks, avoiding unnecessary UI latency.

#### UC-CCG-003 — Queue Timeout Backpressure

When request volume for a single user exceeds the maximum wait duration (e.g. 15s), the request
times out gracefully and returns HTTP 429 Too Many Requests with a `Retry-After: 1` header and
translatable message, protecting PHP-FPM / Octane workers from thread starvation.

---

## 4. Functional Requirements

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-CCG-001 | Safe HTTP methods (`GET`, `HEAD`, `OPTIONS`) MUST bypass the concurrency lock | P0 | F | Full |
| FR-CCG-002 | Mutating requests (`POST`, `PUT`, `PATCH`, `DELETE`) from authenticated users MUST acquire an atomic lock scoped to the user ID | P0 | F | Full |
| FR-CCG-003 | Mutating requests from unauthenticated guests MUST acquire an atomic lock scoped to session ID or client IP | P1 | F | Full |
| FR-CCG-004 | Mutating requests MUST wait in queue (`block()`) up to `concurrency.wait_timeout_seconds` | P0 | F | Full |
| FR-CCG-005 | When lock acquisition times out, the gateway MUST return HTTP 429 with `Retry-After` header | P1 | F | Full |
| FR-CCG-006 | The gateway MUST release locks in a `finally` block or closure scope on completion or error | P0 | F | Full |
| FR-CCG-007 | Concurrency settings MUST be configurable via `config/concurrency.php` (enabled, ttl, wait timeout, global write lock) | P0 | U | Full |
| FR-CCG-008 | The gateway MUST attach `X-Concurrency-Gate: acquired` or `bypassed` header to the response | P2 | F | Full |

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-CCG-001 | Atomic locks MUST use Laravel Cache lock driver | Cache::lock() | P0 | A | Full |
| NFR-CCG-002 | Safe read overhead MUST NOT exceed 1ms | <1ms overhead | P0 | F | Full |
| NFR-CCG-003 | Lock release failure rate MUST be 0% | 0 orphaned locks | P0 | F | Full |

---

## 6. API / Data Contracts

### 6.1 Configuration Contract (`config/concurrency.php`)

```php
return [
    'enabled' => env('CONCURRENCY_GATE_ENABLED', true),
    'lock_ttl_seconds' => (int) env('CONCURRENCY_LOCK_TTL', 30),
    'wait_timeout_seconds' => (int) env('CONCURRENCY_WAIT_TIMEOUT', 15),
    'global_write_lock' => (bool) env('CONCURRENCY_GLOBAL_WRITE_LOCK', false),
    'exempt_routes' => [
        'health',
        'up',
    ],
];
```

### 6.2 Cache Keys (`config/cache-keys.php`)

- `'concurrency_user_lock' => 'core.concurrency.user.'`
- `'concurrency_guest_lock' => 'core.concurrency.guest.'`
- `'concurrency_global_lock' => 'core.concurrency.global'`

---

## 7. Design Decisions

| ID | Decision | Priority |
|----|----------|----------|
| DD-CCG-001 | Mutex locking by user ID rather than IP address | P0 |

*Rationale:* 50 workstations in a school computer lab share the same public NAT IP address.
Locking by IP would inappropriately serialize mutations across different distinct students.
Locking by authenticated user ID serializes multi-session usage of the *same* account without
interfering with independent students.

| ID | Decision | Priority |
|----|----------|----------|
| DD-CCG-002 | Read-bypass architecture | P0 |

*Rationale:* 90%+ of dashboard web traffic is read-only. Bypassing GET/HEAD/OPTIONS ensures
zero queuing latency for navigating pages or rendering assets.

---

## 8. Success Metrics

- 0% lost updates or write skew during 50-client simultaneous CRUD tests on a single account.
- 0% SQLite `database is locked` errors during stress test suites.
- 100% of locks released cleanly even when controllers throw unexpected exceptions.

---

## 9. Dependencies & Traceability

- **Parent Spec:** [2CF4Y-middleware-pipeline](2CF4Y-middleware-pipeline.md)
- **Architecture:** [architecture](../architecture.md)
- **Cache Pattern:** [cache-pattern](../guides/arch/cache-pattern.md)
