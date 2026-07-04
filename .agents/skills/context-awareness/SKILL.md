# Context Awareness — Internara Project Compass

> **Last updated:** 2026-07-03
> **Changes:** comprehensive rewrite covering all 19 modules, infrastructure, and cross-cutting concerns across the entire project

This skill is your universal orientation for Internara. It covers what exists,
how things connect, and WHERE to look for details. It does NOT duplicate content
from `docs/` — it tells you which doc to read for each topic.

---

## 1. What Is Internara

**Self-hosted, single-tenant PKL management system** for Indonesian SMA/SMK.
Manages the complete industrial fieldwork lifecycle: enrollment, placement,
attendance, logbooks, assessments, grade cards, and certificates.

**3S Governing Doctrine:** Secure (PII isolation, layered auth) | Sustain (19 modules, colocation) | Scalable (single-tenant, CQRS-inspired)

**Deployment:** Tier 1 (shared hosting, SQLite, sync queue) — Tier 2 (VPS, Redis, Supervisor) — Tier 3 (HA, cluster, S3). Same codebase, different config.

**Bilingual:** English codebase, Indonesian UI (`lang/id/`), local terms: NISN, NPSN, PKL, DUDI.

---

## 2. Architecture Orientation

Read `docs/architecture.md` for the full spec. Key things to know:

**4 layers, strictly downward:** UI (Livewire/Blade) → Business (Actions) → Data (Models/Entities) → Framework (Core)

**Action Triad:** Command (mutation, transaction+log) | Read (complex query, no DB writes) | Process (orchestration, composes other Actions). Contracts in `app/Core/Actions/`

**DTO boundaries:** UI→Business via `BaseData` | Business→UI via `ActionResponse`. Never raw arrays for 3+ params.

**File structure:** `app/{Module}/{SubModule}/{Layer}/{Class}.php` — vertical slices. Tests mirror: `tests/{Feature,Unit}/{Module}/{SubModule}/{Name}Test.php`

**Routes:** 17 files in `routes/web/{module}.php`, required by master `routes/web.php`. Livewire auto-discovered from `app/*/Livewire/`.

---

## 3. Complete Module Map

**Foundation:** Core → Setup → Settings
**Identity:** Auth → User → SysAdmin
**Institution:** Academics → Partners → Program
**Enrollment:** Enrollment
**Operations:** Journals → Guidance → Incident
**Assessment:** Assessment → Assignment → Evaluation
**Output:** Reports → Certification
**Cross-cutting:** Document

Read each module's `.md` for business rules, `-reference.md` for technical structure.

### Key Business Rules Per Module

| Module | Key Invariant | Read Docs For |
|--------|--------------|---------------|
| Core | Base classes, exceptions, SmartLogger, contracts | `docs/modules/core.md` |
| Auth | 5 flat roles, super admin bypass via `Gate::before` | `docs/modules/auth.md`, `docs/foundation/rbac.md` |
| User | 8-state account machine, PII-isolated profiles | `docs/modules/user.md` |
| SysAdmin | User CRUD, announcements lifecycle, audit | `docs/modules/sysadmin.md` |
| Setup | Single execution, 6-step wizard, recovery key | `docs/modules/setup.md` |
| Settings | Three-tier config, brand colors, feature flags | `docs/modules/settings.md` |
| Academics | Single-active academic year, guarded dept deletion | `docs/modules/academics.md` |
| Partners | Partnership lifecycle EXPIRED/TERMINATED terminal | `docs/modules/partners.md` |
| Program | DRAFT→PUBLISHED→ACTIVE→COMPLETED→CANCELLED | `docs/modules/program.md` |
| Enrollment | Atomic quota enforcement, guest→student pipeline | `docs/modules/enrollment.md` |
| Journals | One entry/day, DRAFT→SUBMITTED→VERIFIED, GPS clock-in | `docs/modules/journals.md` |
| Guidance | Dual-mentor model, private supervision logs | `docs/modules/guidance.md` |
| Assessment | JSON rubrics, finalization immutability, cross-role proxy | `docs/modules/assessment.md` |
| Assignment | DRAFT→PUBLISHED→CLOSED, late flagging, version history | `docs/modules/assignment.md` |
| Evaluation | Google Forms-like, polymorphic targeting, auto-scoring | `docs/modules/evaluation.md` |
| Incident | REPORTED→INVESTIGATING→RESOLVED→CLOSED, CRITICAL escalation | `docs/modules/incident.md` |
| Reports | Grade card only, DRAFT→FINALIZED, weight-based scoring | `docs/modules/reports.md` |
| Certification | QR crypto verification, revocation terminal, batch issuance | `docs/modules/certification.md` |
| Document | Unified table, Blade+DomPDF rendering, template versioning | `docs/modules/document.md` |

---

## 4. Cross-Cutting Patterns

These patterns span multiple modules. Read the referenced docs for details.

### RBAC & Authorization (`docs/foundation/rbac.md`)
- 5 flat roles (no inheritance). Super admin bypasses ALL checks via `Gate::before`
- 2 functional roles (mentor/mentee) resolved at runtime, never stored in DB
- 3-level auth: routes (CheckRoleMiddleware), Livewire (inline authorize), Policies (BasePolicy)

### State Machines (`docs/architecture/enum-pattern.md`)
Many modules use `StatusEnum` for lifecycle management. Common across modules:
- Linear: DRAFT→SUBMITTED→VERIFIED/FINALIZED (Logbook, Submission, Report)
- Multi-path: AccountStatus (8 states), CertificateStatus (issued/revoked)
- Terminal states return `[]` from `validTransitions()`

### SmartLogger (`docs/architecture/logging-pattern.md`)
Dual-channel: system log (files) + activity log (DB). PII masking automatic
via `->withPiiMasking()`. Actions call `$this->log()` which auto-configures.

### File Uploads (`docs/infrastructure/media-library.md`)
ALL uploads through Spatie Media Library (never `Storage::put()`) with
named collections and image conversions. 10MB default max.

### Cache (`docs/infrastructure/cache.md`)
Centralized key registry in `config/cache-keys.php` — NEVER inline strings.
Invalidation is event-driven. Settings cached forever, dashboard stats 5 min.

### Queue (`docs/infrastructure/queue.md`)
Dual pipeline: `default` (emails, notifications, media) + `documents` (PDF).
Tier 1 = sync, Tier 2+ = Redis via Supervisor.

### Notifications (`docs/infrastructure/notification.md`)
Multi-channel: CustomDatabaseChannel (in-app) + Mail + future Webhook.
Notifications sent from Actions or listeners — never from Livewire.
Mail notifications implement `ShouldQueue`.

### Cross-Role Proxy (`docs/adr/adr-cross-role-proxy.md`)
Teachers proxy for inactive supervisors (after 48h). Logged with proxy_role.
Admin proxies for any role. Relevant in Assessment, Journals, Reports.

### Three Recovery Mechanisms (`docs/foundation/account-recovery.md`)
1. Password reset (email token, self-service) — 3/3600s rate limit
2. Recovery slip (admin generates 10 codes, offline) — 3/300s rate limit
3. Super admin recovery (CLI, 64-char key, emergency only)

---

## 5. Decision Framework (Metacognitive Loop)

Every task: **CONSTRUCT → EVALUATE → VERIFY → DECIDE**

### CONSTRUCT — Before Writing
1. Read business context: `docs/modules/{module}.md`
2. Read technical structure: `docs/modules/{module}-reference.md`
3. Read pattern: `docs/architecture/{pattern}-pattern.md`
4. Read actual code: similar features in the module
5. Verify everything — paths, signatures, column names, counts

### EVALUATE — After Building
- Matches requirements? Follows 4-layer direction? Correct base class?
- No `Model::create/update/delete` in Livewire? No `app()->make`?
- Has test? Passes?

### VERIFY — Before Done
```bash
php artisan test --compact --filter={TestName}
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --no-progress
grep -rn 'dd(\|dump(\|ray(' app/ --include='*.php'
```

### DECIDE
Accept | Revise | Split | Escalate

---

## 6. Critical Rules (NEVER Violate)

| Rule | Reference |
|------|-----------|
| No `Model::create/update/delete` in Livewire | `docs/architecture.md` §R3 |
| No `event()` inside Action — use `dispatchEvent()` | `docs/architecture/action-pattern.md` |
| No `app()->make()` in app code — use injection | `docs/conventions.md` §8 |
| Business violations → `RejectedException`, not `RuntimeException` | `docs/architecture/exception-pattern.md` |
| No raw SQL without parameterized binding | `docs/conventions.md` §3.2 |
| All cache keys in `config/cache-keys.php` | `docs/architecture/cache-pattern.md` |
| All user-facing strings use `__()` — both EN and ID | `docs/conventions.md` §3 |
| Report = grade card only, no thesis content | `AGENTS.md` |
| No `dd/dump/ray` in committed code | `AGENTS.md` |

---

## 7. Source of Truth Hierarchy

```
1. Code (always wins)
2. Tests (expected behavior)
3. Migrations (actual schema)
4. Config files (actual configuration)
5. AGENTS.md (project invariants)
6. docs/architecture.md (patterns)
7. docs/conventions.md (standards)
8. docs/modules/*.md (business context)
9. Skill files (advisory, may be stale)
```

**When in doubt, `find`, `grep`, or `ls` — don't guess.**

---

## 8. Where to Find Everything

| You need | Go to |
|----------|-------|
| Module business rules | `docs/modules/{module}.md` |
| Module API reference | `docs/modules/{module}-reference.md` |
| Implementation pattern | `docs/architecture/{pattern}-pattern.md` |
| DB schema | migrations + `docs/infrastructure/database.md` |
| ERD | `docs/foundation/erd.md` |
| RBAC | `docs/foundation/rbac.md` + `config/permission.php` |
| Deployment | `docs/infrastructure/deployment.md` |
| Testing conventions | `docs/architecture/testing-pattern.md` |
| Coding standards | `docs/conventions.md` |
| ADRs | `docs/adr/adr-index.md` |
| All features | `docs/key-features.md` |

```bash
# Find files
find app/{Module} -path '*/Actions/*.php'
find tests -path '*{Module}*{TestName}*'
grep -rn 'Route::' routes/web/{module}.php
grep -rn 'use.*{Class}' app/
```

---

## 9. Communication Rules

- **English only** in code, comments, commits, docs, Issues
- User writes Indonesian → you reply English
- Exception: `lang/id/` translation files
- Every `__('key')` must exist in BOTH `lang/en/` and `lang/id/`
- Key convention: `{module}.{sub_noun}.{descriptive_key}`
