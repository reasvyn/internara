# Job & Queue Infrastructure — Async Processing

> **Spec ID:** 8FVZA
> **Status:** Full
> **Owner:** Core
> **Depends on:** SE5Q9, NUCY3

## Description

Defines the asynchronous processing infrastructure: queued jobs, retry strategy, failed job
handling, queue driver configuration, and the line between queued work and synchronous execution.
Covers the baseline jobs and the conventions every new job follows.

The [backup-system](HBXCI-backup-system.md) spec leans on this infrastructure for failure
notifications; the [architecture](D2FT3-architecture.md) spec fixes the Action Triad this
infrastructure defers to. Rationale for the driver tiers lives in the
[performance-optimization ADR](../adr/adr-performance-optimization.md) and the
[self-hosted single-tenant ADR](../adr/adr-self-hosted-single-tenant.md).

---

## 1. Problem Statements

### PS-1 — Long-Running Operations Block HTTP

Operations like batch certificate issuance, document PDF generation, and bulk account archival
can take minutes. Running them synchronously would timeout HTTP requests and degrade the
experience for everyone sharing the request cycle.
**→ Requirement:** FR-QUEUE-001 (queued execution), FR-QUEUE-008 (sync default with a swap path).

### PS-2 — No Retry Strategy for Failed Work

Without a defined retry policy, transient failures (database locks, mail server timeouts) cause
permanent data loss. Jobs need automatic retry with backoff, and a durable record when retries
run out.
**→ Requirement:** FR-QUEUE-002/003 (attempts and backoff), FR-QUEUE-006 (failed_jobs record).

### PS-3 — Inconsistent Job Conventions

Each job independently implements retry logic, timeout handling, and error reporting. Without
standardized conventions, new jobs omit critical resilience patterns and fail in new ways.
**→ Requirement:** FR-QUEUE-001–007 (job contract), UC-QUEUE-002 (creation workflow).

---

## 2. Goals & Non-Goals

### Goals

- **Long-running operations execute asynchronously via queued jobs** — batch issuance, PDF generation, archival. *Why:* HTTP workers stay free during minute-long work.
- **Consistent retry strategy on every job** — 3 attempts with exponential backoff. *Why:* transient failures self-heal instead of becoming data loss.
- **Failed jobs are durable and visible** — recorded in `failed_jobs` with full context. *Why:* the night-shift failure is diagnosable the next morning.
- **Minimal job payloads** — models referenced by ID, never serialized whole. *Why:* stale-object bugs disappear and payloads stay small.
- **Driver configurable per environment** — sync on shared hosting, Redis on VPS, zero code change. *Why:* the same binary serves a 400-student school and an 1,800-student one.

### Non-Goals

- **Job orchestration pipelines**. *Why:* Horizon-style DAGs are operational weight a single-tenant school never exercises; one status view covers MVP.
- **Real-time job progress feedback to users**. *Why:* completion surfaces through notifications and re-rendered state, not a progress socket.
- **Job priority queues beyond Laravel's built-in levels**. *Why:* volume never justifies custom priority topology at school scale.
- **Cross-server job distribution**. *Why:* single-server deployment by product definition.
- **Job scheduling (cron-based)**. *Why:* scheduling belongs to Artisan commands and the scheduler, not to job classes.

---

## 3. User Stories / Use Cases

Use Cases are **optional** to test, like Design Decisions (§7). These three workflows are
verifiable at this spec's level, so `Layer`/`Status` are filled.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-QUEUE-001 | Admin triggers batch certificate issuance; certificates issue asynchronously with failures logged for retry | P0 | F | Full |
| UC-QUEUE-002 | Developer creates a new queued job following the job contract; the job executes asynchronously with retry capability | P0 | A | Full |
| UC-QUEUE-003 | Developer investigates a failed job via the failed_jobs record and re-attempts it after fixing the cause | P1 | F | Full |

### 3.1 Operations & Development

#### UC-QUEUE-001 — Admin Triggers Batch Certificate Issuance

The night before certificate printing day, an admin at a 600-student school clicks "Issue
Certificates" for the graduating batch and goes home. Under the old synchronous flow that click
held an HTTP worker for eleven minutes and timed out halfway, leaving half the batch issued with
no record of which half. In the queued flow the click dispatches one job per batch and returns
immediately; each job generates certificates student by student, and any student whose record
trips a transient lock is retried automatically and, failing that, lands in `failed_jobs` with
the student's ID attached. The admin returns to a complete batch plus a short, named failure
list instead of a half-finished mystery.

#### UC-QUEUE-002 — Developer Creates a New Queued Job

Earlier revisions of this spec described the job contract across scattered paragraphs and two
developers implemented it two different ways — one serialized whole models, the other called
`app()->make()` inside the constructor. The contract now reads as a single checklist enforced by
the class-contract scan: implement `ShouldQueue`, set three attempts with the fixed backoff,
inject dependencies through the constructor, and carry only UUID strings in the payload. A job
written this way behaves identically on sync and Redis drivers, which is exactly what lets a
school grow from shared hosting to a VPS without touching job code.

#### UC-QUEUE-003 — Developer Investigates a Failed Job

Morning after a rough night: the queue dashboard shows one failed entry. The developer opens the
`failed_jobs` row and finds the exception class, message, and the UUID payload that reproduces
the exact input — no log-file archaeology, because the `failed()` callback wrote the full
context when retries ran out. The fix is a one-line guard in the handling Action; the developer
re-runs that single job with `php artisan queue:retry` and watches it succeed. The failed row is
the whole incident record: what ran, what it carried, why it died, and the retry that closed it.

---

## 4. Functional Requirements

A Functional Requirement is a verifiable behavior the system must support. `Priority` ranks
criticality on a P0–P3 scale. `Layer` declares the test layer that will verify it.
`Status` tracks implementation of this specific requirement.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-QUEUE-001 | All queued jobs implement the `ShouldQueue` interface | P0 | A | Full |
| FR-QUEUE-002 | All jobs set `tries = 3` | P0 | A | Full |
| FR-QUEUE-003 | All jobs set `backoff = [2, 10, 30]` seconds between attempts | P0 | A | Full |
| FR-QUEUE-004 | Job constructors use dependency injection; `app()->make()` inside jobs is forbidden | P0 | A | Full |
| FR-QUEUE-005 | Job payloads reference models by UUID string, never serialized model objects | P0 | A | Full |
| FR-QUEUE-006 | Failed jobs are recorded in the `failed_jobs` table automatically after retries run out | P0 | F | Full |
| FR-QUEUE-007 | Jobs never dispatch events or write to the activity log; the triggering Action owns side effects and the `failed()` callback owns failure logging | P1 | A | Full |
| FR-QUEUE-008 | Queue runs sync by default and switches to Redis through `QUEUE_CONNECTION` with zero code change | P0 | F | Full |
| FR-QUEUE-009 | Jobs are dispatched after the triggering transaction commits; queued fan-out is discarded on rollback | P0 | F | Full |
| FR-QUEUE-010 | Every job implements `failed()` reporting through SmartLogger with PII masking | P1 | F | Full |
| FR-QUEUE-011 | Queue health is observable through a single status view plus `queue:monitor` | P1 | F | Full |
| FR-QUEUE-012 | Baseline jobs run through this contract: batch certificate issuance, document generation, account archival | P0 | F | Full |

### 4.1 Job Contract

#### FR-QUEUE-001 — Every job is a queued job

A class that does background work without `ShouldQueue` silently runs inline wherever it is
dispatched, which means enrollment-week behavior differs between the developer's sync laptop and
the school's Redis VPS. The interface is the one-line guarantee that dispatch always means
enqueue, and the contract scan proves every job class under the jobs directories carries it.

#### FR-QUEUE-002 — Three attempts, no more, no fewer

One attempt turns every mail-server hiccup into a support ticket; unlimited attempts turn a
poison payload into an infinite loop that eats the worker all morning. Three attempts is the
settled middle the whole fleet shares, so an operator reading any job class already knows its
retry budget without opening the file.

#### FR-QUEUE-003 — Backoff buys the transient time to pass

Two seconds absorbs the database lock that clears on its own, ten seconds absorbs the mail
server restarting, thirty seconds absorbs the network blip during a storm. The curve is fixed
fleet-wide rather than tuned per job because per-job tuning was tried and produced eleven
different curves nobody could reason about during an incident.

#### FR-QUEUE-004 — Dependencies arrive through the constructor

A job that resolves services from the container hides its needs from tests and from readers —
the failure then surfaces at 2 a.m. inside `handle()` instead of at dispatch time. Constructor
injection makes the needs visible in the signature, mockable in tests, and identical across
drivers, which matters because sync and Redis serialize the job differently.

#### FR-QUEUE-005 — Payloads carry IDs, not objects

A serialized model is a photograph: by execution time the student may have been re-placed, the
slot count may have changed, and the job acts on a ghost. A UUID string forces the job to
re-fetch current state at execution, so the work always applies to the world as it is. The one
extra query per job is invisible at school-scale volumes.

#### FR-QUEUE-006 — The failed_jobs table is the morgue with labels

When retries are exhausted the job must not evaporate — it lands in `failed_jobs` with its
payload, exception, and failed timestamp intact. That row is what UC-QUEUE-003 investigates and
what `queue:retry` replays, so without it every permanent failure would require reproducing the
original request from scratch.

#### FR-QUEUE-007 — Jobs stay mechanically silent

The triggering Action already wrote the audit entry and fired the domain event before dispatch;
a job that logs again doubles the trail and a job that emits events replays side effects the
Action already accounted for. Jobs do the mechanical work — render the PDF, insert the rows —
and only their `failed()` callback may speak, exclusively about the failure.

### 4.2 Drivers & Dispatch

#### FR-QUEUE-008 — Sync today, Redis tomorrow, same code

A school starts on shared hosting where no daemon can run, so `QUEUE_CONNECTION=sync` executes
jobs inline and every feature works with zero infrastructure. When sustained load crosses the
tier boundary the operator sets `QUEUE_CONNECTION=redis`, starts one worker, and nothing else
changes — no job edit, no dispatch-site edit — because every queue interaction already goes
through the framework drivers. The swap is what makes the performance tiers configuration
rather than rewrites.

#### FR-QUEUE-009 — Nothing enqueues from a transaction that might roll back

Consider a placement Action that dispatches a notification job mid-transaction and then fails
its final invariant: the database rolls back but the already-dispatched job still sends "you
are placed" to the student. Dispatch-after-commit closes that hole — the job only becomes
visible to workers once the transaction lands, and a rolled-back Action leaves no queued
ghost behind. Queued listeners inherit the same semantics: rollback discards them.

### 4.3 Observability & Baseline

#### FR-QUEUE-010 — Every failure writes its own incident report

The `failed()` callback routes the exception, the payload IDs, and the attempt history through
SmartLogger's system channel with PII masking applied before anything reaches a sink, so an
over-eager payload dump cannot leak a student's phone number into a log file. Operators read
one structured entry per failure instead of grepping worker output, and the entry is what the
morning investigation in UC-QUEUE-003 starts from.

#### FR-QUEUE-011 — One status view, not a supervision suite

An operator needs exactly one answer — is the queue moving or stuck — plus the framework's
`queue:monitor` for the numbers behind it. Anything beyond that single view (multi-queue
topology boards, per-worker supervision UIs) is post-MVP depth: real schools diagnose from the
failed_jobs list and the monitor output, and every extra dashboard is surface nobody opens.

#### FR-QUEUE-012 — The baseline fleet proves the contract

Three jobs carry production load today — batch certificate issuance, async document generation,
and bulk account archival — and each conforms to FR-QUEUE-001–007 rather than grandfathering
old habits. They are the living examples new jobs copy: when a developer asks how a job should
look, the answer is one of these three files, not a paragraph of prose.

---

## 5. Non-Functional Requirements

A Non-Functional Requirement is a measurable constraint on the system. `Target` is the concrete
number/SLO. `Priority` and `Status` behave as in §4.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-QUEUE-001 | Failed job retry follows exponential backoff | 2 s, 10 s, 30 s | P0 | F | Full |
| NFR-QUEUE-002 | Queue driver is selectable through the environment with sync as default | sync default; Redis via env only | P0 | F | Full |
| NFR-QUEUE-003 | Every job failure is logged with full exception context and no secrets | 100% of failures logged; 0 secrets in logs | P0 | F | Full |
| NFR-QUEUE-004 | Queue health is observable without extra infrastructure | one status view | P1 | F | Full |

### 5.1 Retry & Drivers

#### NFR-QUEUE-001 — The backoff curve holds under load

During enrollment week a burst of placement jobs collides on the same rows and the first wave
fails on locks; the fixed 2/10/30 curve spaces the retries so the second wave lands after the
locks clear instead of stampeding them again. A feature test drives a lock-contention scenario
and asserts the retry timestamps follow the curve rather than firing immediately.

#### NFR-QUEUE-002 — The swap is env-only, provably

The test boots the suite with `QUEUE_CONNECTION=sync` and again with the database driver and
asserts identical outcomes for the same dispatches — same rows, same notifications, same failed
rows. That equivalence is the whole tier promise: growth never requires a code branch on driver.

### 5.2 Observability & Safety

#### NFR-QUEUE-003 — No failure is silent and no log leaks

A job that dies without a structured entry is invisible until a student complains; a job that
logs its raw payload leaks PII into plaintext. The requirement pairs both halves: the failed
entry always exists, and a scan over failure-log fixtures asserts masked placeholders where
emails, phones, and tokens used to be.

#### NFR-QUEUE-004 — Stuck queues are visible within a glance

The status view answers depth, oldest-waiting age, and failed count on one screen, because the
failure mode it guards is the quiet one — a dead worker on a VPS where jobs pile up for days
while the UI looks healthy. One view checked each morning beats a supervision suite nobody
opens.

---

## 6. API / Data Contracts

### 6.1 Job Class Pattern

```php
class BatchIssueCertificatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [2, 10, 30];

    public function __construct(
        private readonly string $internshipId,
    ) {}

    public function handle(
        IssueCertificateAction $issueCertificate,
    ): void {
        // Process each student
    }

    public function failed(Throwable $exception): void
    {
        // SmartLogger system-channel entry with PII masking (FR-QUEUE-010)
    }
}
```

### 6.2 Queue Configuration

| Setting | Shared Hosting (Tier 1) | VPS (Tier 2) |
|---------|------------------------|--------------|
| `QUEUE_CONNECTION` | `sync` | `redis` |
| Worker | none (inline) | 1+ via `queue:work` |
| Retry delay | 2s, 10s, 30s | Same |
| Max attempts | 3 | 3 |

### 6.3 Baseline Jobs

| Job | Module | Purpose |
|-----|--------|---------|
| `BatchIssueCertificatesJob` | Certification | Batch certificate issuance |
| `GenerateDocumentJob` | Document | Async PDF generation |
| `ArchiveStudentAccountsJob` | User | Batch account archival |

Announcement broadcasting uses per-recipient queued notifications rather than a dedicated job,
and logbook report compilation is a synchronous Read Action — neither takes a queue slot.

---

## 7. Design Decisions

Design Decisions are **optional** to test, like Use Cases (§3). `Layer` / `Status` stay `—`.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-QUEUE-001 | Sync queue by default; Redis as an env-only Tier-2 swap | P0 | — | — |
| DD-QUEUE-002 | Jobs reference models by UUID string, not serialized objects | P0 | — | — |
| DD-QUEUE-003 | Jobs never dispatch events or write activity entries | P1 | — | — |
| DD-QUEUE-004 | Queue-vs-inline line: minute-scale or fan-out work queues; sub-second single writes stay inline | P1 | — | — |

### 7.1 Drivers & Payloads

#### DD-QUEUE-001 — Sync Default, Redis on Demand

A self-hosted school on shared hosting cannot run a daemon, so the default must work with
nothing but MySQL. Sync executes jobs inline — slower responses during batch work, but zero
infrastructure. Redis becomes correct the moment a worker can run, and because the swap is
env-only the decision stays with the deployer instead of leaking into job code.

#### DD-QUEUE-002 — UUID References Over Serialized Models

Serialized models freeze state at dispatch time and resurrect it at execution time, acting on
a world that may have moved on. UUID strings cost one re-fetch per job and guarantee current
state — the cheaper correctness at volumes where one query per job is noise.

### 7.2 Silence & Scope

#### DD-QUEUE-003 — Mechanical Silence Inside Jobs

Audit entries and domain events belong to the Action that decided the work, which runs inside
the transaction and the authorization context. A job repeating them doubles the trail and can
replay side effects; the job's only voice is its failure report. The boundary keeps the audit
log a record of decisions, not of machinery.

#### DD-QUEUE-004 — Where the Queue Line Sits

Not everything slow-looking deserves a job: a single certificate PDF for one student renders in
a second and stays inline, while the 600-student batch queues. The line is fan-out and
minute-scale work on one side, sub-second single writes on the other. Work that sits near the
line gets a job only when a school measures the pain — premature queuing trades a simple
request cycle for retry, failure, and observability machinery nobody needed.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Job success rate (first attempt) | ≥ 95% | Dispatch vs success counts over a placement period |
| Job success rate (after retries) | ≥ 99% | Success including retried attempts |
| Failed jobs with structured entries | 100% | `failed_jobs` rows vs SmartLogger failure entries |
| Secrets in failure logs | 0 | PII-mask scan over failure-log fixtures |
| Average job execution time | < 30s | Worker timing over a placement period |
| Failed jobs needing manual intervention | < 1% | Retry-resolved vs operator-retried counts |

---

## 9. Roadmap

### Prerequisites

This spec builds on the base-class and event-system contracts:

| Spec | What It Provides |
|------|-----------------|
| [base-classes.md](SE5Q9-base-classes.md) (SE5Q9) | `BaseProcessAction` for job orchestration, `SendsNotifications` contract |
| [event-system.md](NUCY3-event-system.md) (NUCY3) | `BaseEvent` contract and dispatch-after-commit semantics |

### Build Guide

With this spec the system processes async work via Laravel queues with uniform retry, durable
failures, and an env-only driver swap. Queued notifications and background file processing build
on this infrastructure. It sits in the Maintenance phase because queue infrastructure powers
backup operations, account archival, notification delivery, and system cleanup.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [backup-system.md](HBXCI-backup-system.md) (HBXCI) | Failure notifications dispatch through queues |
| 2 | [system-maintenance.md](E1MSJ-system-maintenance.md) (E1MSJ) | Archival and cleanup tasks dispatch via queue |
| 3 | [notification-infrastructure.md](TXR2H-notification-infrastructure.md) (TXR2H) | `ShouldQueue` notifications dispatch via queue |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| R-1 | If a VPS worker process dies silently, queued jobs pile up until the morning status-view check; mitigation is the single status view (FR-QUEUE-011) plus process supervision in the deployment guide | Open | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all feature specs grouped in 12 phases
- [Architecture](D2FT3-architecture.md) — Action Triad the jobs defer to
- [Backup system](HBXCI-backup-system.md) — failure notifications over queues
- [ADR: Performance optimization](../adr/adr-performance-optimization.md) — tier doctrine and swap path
- [ADR: Self-hosted single-tenant](../adr/adr-self-hosted-single-tenant.md) — why sync is the default
- [ADR: Eloquent observers](../adr/adr-eloquent-observers.md) — dispatch timing and discard-on-rollback
