# AI Agents Workspace

This directory is the dedicated workspace for AI agents operating within the Internara project. All coordination, planning, tracking, and governance artifacts for AI-assisted work reside here.

---

## Directory Structure

```
.agents/
├── README.md                  ← This file: workspace governance and agent protocols
├── KEY_FEATURES_CHECKLIST.md  ← Single Source of Truth (SSoT) for feature evolution tracking
├── settings.json              ← MCP server configuration for Laravel Boost
├── plans/                     ← Detailed planning proposals requiring human approval
├── issues/                    ← Audit reports, complaints, and technical notes (GitHub Issues-style)
└── todo/                      ← Comprehensive task step lists (post-approval execution plans)
```

---

## Agent Roles and Scope Separation

There are **two primary role scopes** for AI agents. Agents must strictly adhere to their assigned scope:

| Role | Responsibility | Allowed Actions | Prohibited Actions |
|------|---------------|-----------------|-------------------|
| **Supervisor** | Auditing, reviewing, verifying, reporting | Read code, run tests, inspect architecture, write issues/audit reports, verify compliance with standards | Write or modify application code, create migrations, change configurations, execute destructive operations |
| **Engineer** | Implementation, refactoring, bug fixing, feature development | Write code, create migrations, modify configurations (with approval), run tests, fix bugs | Audit own work as supervisor, approve own plans, execute destructive operations without human authorization |

**Rule**: An agent acting as Supervisor must never perform Engineering tasks, and vice versa. If an agent needs to switch roles, it must explicitly declare the role change and the reason for it.

---

## Sub-Directories

### `plans/` — Planning Proposals

Detailed planning documents that require **human approval** before any implementation begins.

- A plan is a **formal proposal**, not a todo list. It describes what will be built, why, how, and what the risks are.
- Plans follow the **3S Doctrine** (Secure, Sustain, Scalable) from `AGENTS.md` and must include:
  - Requirement summary
  - Project impact assessment (which areas of the system are affected)
  - Security and data assessment
  - Implementation approach with alternatives considered
  - Known risks and tradeoffs
- Plans are converted into `todo/` entries **only after** human approval.
- Naming convention: `{YYYY-MM-DD}-{short-description}.md`

### `todo/` — Task Execution Lists

Comprehensive, step-by-step task lists derived from approved plans.

- Each todo item must have:
  - A clear, actionable description
  - An acceptance criterion (how to verify completion)
  - A 3S classification (which dimension it serves)
- Todo items are executed in dependency order.
- Completed items are marked and archived within the same file with a completion note.
- Naming convention: `{YYYY-MM-DD}-{short-description}.md`

### `issues/` — Technical Reports and Notes

Functions like GitHub Issues but for internal agent-human communication.

- Used for:
  - Audit findings and compliance reports
  - Technical complaints and concerns
  - Bug reports with reproduction details
  - Architecture or design notes that need consideration
  - Follow-up tasks identified during reviews
- Each issue must have:
  - A clear title and description
  - Severity/priority classification (P0–P3)
  - Affected area(s) of the system
  - Recommended action or resolution
- Closed issues remain in the directory with a `[CLOSED]` prefix and resolution summary.
- Naming convention: `{YYYY-MM-DD}-{short-description}.md`

---

## Key Features Checklist (SSoT)

`KEY_FEATURES_CHECKLIST.md` is the **Single Source of Truth** for tracking the evolution of the project based on feature-based requirements.

### Status Markers

| Marker | Meaning |
|--------|---------|
| `[v]` | Completed — feature is fully implemented and working |
| `[*]` | In-progress — partially implemented and actively being developed |
| `[+]` | Needs Improvement — exists but needs enhancement or refactoring |
| `[?]` | Needs Review — needs code review or verification |
| `[!]` | Needs Attention — has critical issues or blockers |
| `[-]` | No action needed — deprecated or not applicable |
| `[x]` | Deprecated (EOL) — end-of-life and removed |

### Three-Column Implementation Marker

| Column | Meaning |
|--------|---------|
| `[1]` | **IMPLEMENTED** — code is written and deployed |
| `[2]` | **SECURED/TESTED** — has passing tests and security checks |
| `[3]` | **DOCUMENTED** — has documentation in `/docs` or inline (PHPDoc) |

A feature is considered **fully complete** only when all three columns are marked `[v]`.

### Feature Priority Decisions

| Tag | Meaning |
|-----|---------|
| `[MUST HAVE]` | Critical for MVP — non-negotiable for production |
| `[SHOULD HAVE]` | Important — next priority after MVP |
| `[COULD HAVE]` | Nice-to-have — scheduled post-MVP |
| `[WON'T HAVE]` | Explicitly out of scope for current roadmap |

---

## Operating Principles

### 1. Sync or Sink
Documentation must stay synchronized with code. A discrepancy between what the code does and what the documentation says is a governance failure. Both must be corrected before any task is considered complete.

### 2. Zero Invention
Agents must not invent, assume, or fabricate:
- API contracts, interfaces, or function signatures not present in the provided context
- Data structures, schemas, or storage models not shown to the agent
- Project rules not stated in the requirements or confirmed by the human
- External system behaviors not documented or evidenced in context

When any of the above are required and unavailable: **halt and request the missing information**.

### 3. Minimal Footprint
Make the smallest change that satisfies the requirement. Unsolicited additions — unrequested features, unauthorized refactors, unjustified abstractions, speculative configurations — introduce unreviewed code into the system.

### 4. Fail Fast, Ask Early
Uncertainty surfaced early is less costly than confident output that is wrong. Present ambiguity, state assumptions, and ask for clarification before proceeding.

### 5. Destructive Operations Require Authorization
Any operation that is irreversible — deletion, truncation, overwrite, force-push, production deployment — requires **explicit human authorization** before execution. Agents must present a description or preview and explicitly wait for confirmation.

---

## Project Context for Agents

### What Is Internara?
Internara is a modern **Internship Management System** built on Laravel 12 with an Action-Oriented MVC architecture. It manages the relationship between **Schools**, **Students**, **Teachers**, and **Companies (Mentors)**.

### Core Technology Stack
| Layer | Technology |
|-------|-----------|
| PHP | 8.4 |
| Framework | Laravel 12.58 |
| UI Engine | Livewire 3 + Volt |
| UI Components | Mary UI + Tailwind 4 + DaisyUI 5 |
| Database | SQLite (dev), MySQL/PostgreSQL (prod) |
| Testing | Pest PHP 4.6 |
| Code Quality | Laravel Pint, Prettier, PHPStan Level 8 |
| Security Scan | Trivy |
| Monitoring | Laravel Pulse |

### Architecture Summary
- **Action Layer** (`app/Actions/`) — Stateless use cases, one action = one use case, grouped by business domain (19 domains)
- **Rich Models** (`app/Models/`) — Business rules centralized, all use UUID primary keys via `HasUuid` trait (29 models)
- **Thin Controllers** (`app/Http/Controllers/`) — Handle request/response only, delegate to Actions
- **Livewire Components** (`app/Livewire/`) — Stateful UI components for web
- **Events/Listeners** — Optional, for multiple side effects (notifications, audit, emails)
- **Repositories** — Optional, for complex queries only
- **Services** — Infrastructure concerns only (PDF generation, GeoLocation, Setup)

### RBAC System (spatie/laravel-permission)
| Role | Scope |
|------|-------|
| Super Admin | Full system access, infrastructure management |
| Admin | School-level management, department control, teacher oversight |
| Teacher | Classroom management, student monitoring, journal verification |
| Mentor | Company-side oversight, attendance verification, assessment |
| Student | Daily journals, clock-in/out, personal profile management |

### Configuration: Three-Tier System
1. **`config()`** — Static infrastructure (database, cache, mail, packages)
2. **`setting()`** — Dynamic database settings (cached forever, user-configurable)
3. **`AppInfo`** — Immutable app identity from `app_info.json` (SSoT)

### Database Standards
- All primary keys are UUIDs — no auto-incrementing IDs
- All models use `strict_types=1`
- Foreign key constraints enabled
- Compound indexes on high-growth tables
- Soft deletes where applicable

### Quality Baselines
- **53 architectural tests** (11 files) enforcing layer separation and coding standards
- **12 quality tests** for code stability, performance, and security
- **143+ feature tests**, 5 unit tests
- **Minimum 80% code coverage** requirement
- CI/CD pipeline: Pint → PHPStan → Arch Tests → Pest → Trivy

### Composer Scripts for Quality
```bash
composer quality          # Quick check: lint + static analysis + arch tests
composer quality:full     # Full check: format + strict analysis + coverage
composer test:coverage    # Run tests with 80% coverage requirement
composer test:arch        # Run only architectural tests
composer test:feature     # Run only feature tests
composer test:unit        # Run only unit tests
composer analyse          # PHPStan level 8
composer analyse:strict   # PHPStan max level
```

---

## Essential Documentation References

All agents must be familiar with these documents before performing any work:

| Document | Path | Purpose |
|----------|------|---------|
| Agent Operating Standard | `AGENTS.md` | The 3S Doctrine, workflows, coding standards, behavioral constraints |
| Architecture | `docs/architecture.md` | Layered architecture, Action patterns, anti-patterns |
| Database | `docs/database.md` | Migrations, models, factories, database standards |
| Engineering Standards | `docs/standards.md` | Model, Action, Controller, Livewire, Repository standards |
| Infrastructure | `docs/infrastructure.md` | Tech stack, dependencies, CI/CD, quality tooling |
| RBAC | `docs/rbac.md` | Roles, permissions, account lifecycle |
| Configuration | `docs/configuration.md` | Three-tier config system, AppInfo, Settings |
| Testing | `docs/testing.md` | Test strategy, categories, composer scripts |
| Audits | `docs/audits.md` | Forensic logging, LogAuditAction system |
| Notifications | `docs/notification.md` | In-app notifications, email, real-time |
| Logging | `docs/logging.md` | Standard logging, Laravel Pulse |
| Cache | `docs/cache.md` | Cache drivers, patterns, invalidation |
| Session | `docs/session.md` | Session management, security |
| Filesystem | `docs/filesystem.md` | Storage disks, Spatie Media Library |
| Installation | `docs/installation.md` | System setup, CLI commands |
| Known Issues | `docs/known-issues.md` | Active problems, technical debt, blockers |

---

## Communication Protocol

### When to Ask
- Requirement is ambiguous, incomplete, or contradictory
- Context needed to proceed safely is missing
- Two stated requirements conflict
- A proposed approach requires a decision the human has not authorized
- Multiple valid approaches exist and the choice has significant consequences

### When to Report
- Security vulnerability discovered (P0 — escalate immediately)
- Bug found during audit or review
- Documentation discrepancy found (Sync or Sink violation)
- Test failure that cannot be resolved
- Architectural decision needed

### Output Structure
For complex or consequential tasks, agents must follow this structure:

```
[UNDERSTANDING]   What the agent understood the request to be
[APPROACH]        What approach was chosen and why
[OUTPUT]          The code, configuration, or artifact
[VERIFICATION]    How the output can be confirmed as correct
```

This may be abbreviated for simple, low-risk tasks but must never be omitted entirely.

---

## MCP Configuration

Laravel Boost MCP server is configured in `settings.json` for enhanced tool access:
```json
{
    "mcpServers": {
        "laravel-boost": {
            "command": "php",
            "args": ["artisan", "boost:mcp"]
        }
    }
}
```

This provides agents with access to application context, database schema, routes, logs, and documentation search.

---

## Quick Reference for New Agents

1. **Read `AGENTS.md` first** — it defines how you must think, decide, and act.
2. **Check `KEY_FEATURES_CHECKLIST.md`** — understand what exists, what's in progress, and what needs attention.
3. **Read relevant `docs/` files** — do not proceed without understanding the standards for the area you're working in.
4. **Identify your role** — are you a Supervisor or an Engineer? Stay in scope.
5. **Use `plans/` for proposals** — before writing code, propose and get approval.
6. **Use `todo/` for execution** — after approval, break work into verified steps.
7. **Use `issues/` for reports** — audit findings, bugs, technical notes.
8. **Never skip tests** — every change must be verified. Every bug fix needs a regression test.
9. **Never skip documentation** — code and docs must stay synchronized.
10. **Ask when uncertain** — early acknowledgment of uncertainty is valued over confident but wrong output.
