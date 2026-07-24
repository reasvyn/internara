# Job & Queue Infrastructure — Async Processing

> **Last updated:** 2026-07-23 **Changes:** feat — initial job/queue infrastructure specification

## Description

Defines the asynchronous processing infrastructure: queued jobs, retry strategy, failed job
handling, queue driver configuration, and when to use jobs versus synchronous execution. Covers
the 5 existing jobs and the conventions for creating new ones.

---

## 1. Problem Statements

### PS-1 — Long-Running Operations Block HTTP

Operations like batch certificate issuance, document PDF generation, and bulk account archival
can take minutes. Running them synchronously would timeout HTTP requests and degrade user
experience.

### PS-2 — No Retry Strategy for Failed Work

Without a defined retry policy, transient failures (database locks, mail server timeouts) cause
permanent data loss. Jobs need automatic retry with backoff.

### PS-3 — Inconsistent Job Conventions

Each job independently implements retry logic, timeout handling, and error reporting. Without
standardized conventions, new jobs may omit critical resilience patterns.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Long-running operations execute asynchronously via queued jobs |
| G2  | All jobs implement consistent retry strategy (3 attempts, exponential backoff) |
| G3  | Failed jobs are logged and visible in `failed_jobs` table |
| G4  | Job payload is minimal — reference models by ID, not serialize full objects |
| G5  | Queue driver is configurable per environment |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Job orchestration pipelines (e.g., Laravel Horizon) |
| NG2  | Real-time job progress feedback to users |
| NG3  | Job priority queues beyond Laravel's built-in levels |
| NG4  | Cross-server job distribution (multi-server setup) |
| NG5  | Job scheduling (cron-based) — handled by Artisan commands |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Triggers Batch Certificate Issuance

**Actor:** Admin
**Preconditions:** Internship program completed, assessments finalized
**Flow:**
1. Admin clicks "Issue Certificates" for batch of students
2. `BatchIssueCertificatesJob` dispatched to queue
3. Job processes each student: generates certificate, stores in DB, notifies
4. Admin sees "Processing" status, receives notification when complete
**Postconditions:** All certificates issued, failed ones logged for retry

### UC-2 — Developer Creates New Queued Job

**Actor:** Developer
**Preconditions:** New long-running operation identified
**Flow:**
1. Create job class implementing `ShouldQueue`
2. Set `tries = 3`, `backoff = [2, 10, 30]`
3. Use constructor injection for dependencies
4. Reference models by UUID (not serialized)
5. Register dispatch site in the relevant Action
**Postconditions:** Job executes asynchronously with retry capability

### UC-3 — Developer Investigates Failed Job

**Actor:** Developer
**Preconditions:** Job failed after all retry attempts
**Flow:**
1. Query `failed_jobs` table for failed job entry
2. Inspect `exception` column for stack trace
3. Fix root cause
4. Run `php artisan queue:retry {id}` to re-attempt
**Postconditions:** Job re-executed successfully

---

## 4. Functional Requirements

| ID     | Requirement |
| ------ | ----------- |
| FR-JOB1 | All queued jobs MUST implement `ShouldQueue` interface |
| FR-JOB2 | All jobs MUST set `tries = 3` |
| FR-JOB3 | All jobs MUST set `backoff = [2, 10, 30]` (seconds) |
| FR-JOB4 | Job constructors MUST use dependency injection (no `app()->make()`) |
| FR-JOB5 | Job payloads MUST reference models by UUID, not serialize full model objects |
| FR-JOB6 | Failed jobs MUST be recorded in `failed_jobs` table automatically |
| FR-JOB7 | Jobs MUST NOT dispatch events or log to activity log (keep side effects in the triggering Action) |
| FR-JOB8 | Batch operations MUST provide progress tracking via model status or cache |

---

## 5. Non-Functional Requirements

| ID      | Requirement |
| ------- | ----------- |
| NFR-JOB1 | Job execution timeout MUST be < 60 seconds per attempt |
| NFR-JOB2 | Failed job retry MUST use exponential backoff (2s, 10s, 30s) |
| NFR-JOB3 | Queue driver MUST be configurable via `QUEUE_CONNECTION` env variable |
| NFR-JOB4 | Job failure MUST be logged with full exception context |
| NFR-JOB5 | Queue worker MUST be monitorable via `php artisan queue:work --status` |

---

## 6. API / Data Contracts

### Job Class Pattern

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
        // Log failure, notify admin
    }
}
```

### Queue Configuration

| Setting | Default | Production |
|---------|---------|------------|
| `QUEUE_CONNECTION` | `database` | `redis` |
| Worker concurrency | 1 | 4+ |
| Retry delay | 2s, 10s, 30s | Same |
| Max attempts | 3 | 3 |

### Existing Jobs

| Job | Module | Purpose |
|-----|--------|---------|
| `BatchIssueCertificatesJob` | Certification | Batch certificate issuance |
| `GenerateDocumentJob` | Document | Async PDF generation |
| `CompileLogbookReportJob` | Journals | Logbook report compilation |
| `SendAnnouncementJob` | SysAdmin | Broadcast announcements |
| `ArchiveStudentAccountsJob` | User | Batch account archival |

---

## 7. Design Decisions

### DD-1 — Database Queue as Default

**Decision:** Default queue driver is `database` (not Redis or SQS).

**Rationale:** Self-hosted single-tenant application must work without external services.
Database queue requires only SQLite/MySQL/PostgreSQL which is already present.

**Trade-off:** Database queue is slower than Redis under high load. For single-tenant usage
with low job volume, this is acceptable. Production deployments can switch to Redis via env.

### DD-2 — Minimal Job Payloads

**Decision:** Jobs reference models by UUID string, not serialized model objects.

**Rationale:** Serialized models can become stale if the model changes between dispatch and
execution. UUID references ensure the job always fetches current data.

**Trade-off:** Adds one DB query at job start. For low-volume jobs, this is negligible.

### DD-3 — No Events Inside Jobs

**Decision:** Jobs MUST NOT dispatch events or log to activity log.

**Rationale:** The triggering Action already logs the initial event. Jobs handle mechanical
processing (PDF generation, batch inserts). Adding events inside jobs would create duplicate
audit entries.

**Trade-off:** Job completion is not logged in activity log. If needed, use `failed()` callback
for failure logging only.

---

## 8. Success Metrics

| Metric | Target |
| ------ | ------ |
| Job success rate (first attempt) | ≥ 95% |
| Job success rate (after retries) | ≥ 99% |
| Average job execution time | < 30s |
| Failed jobs requiring manual intervention | < 1% |
| Queue worker uptime | ≥ 99.9% |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [core-foundation.md](core-foundation.md) | `BaseProcessAction` for job orchestration, `SendsNotifications` interface |

### Build Guide
After implementing this spec, the system has async job processing via Laravel queues, batch tracking, failed job management, and notification dispatch. Queued notifications (like OTP emails) and background file processing depend on this infrastructure. The next step is to build the installation system, which provisions the database and generates the setup token.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [installation.md](installation.md) | Uses `config:cache`, `route:cache`, `event:cache` from queue warm-up; `RecoveryOtpNotification` uses `ShouldQueue` |

---

## Quick References

- `app/Jobs/` — All queued jobs (5 files)
- `config/queue.php` — Queue driver configuration
- `docs/architecture/event-pattern.md` — Event dispatch (for comparison)
- `docs/specs/event-system.md` — Event system specification
