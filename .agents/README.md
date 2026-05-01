# AI Agents Workspace

This directory is the dedicated workspace for AI agents operating within this project. All coordination, planning, tracking, and governance artifacts for AI-assisted work reside here.

---

## Directory Structure

```
.agents/
├── README.md                      ← This file: workspace governance and agent protocols
├── AGENT-CHEATSHEET.md            ← Quick reference: roles, rules, workflows (1-page)
├── ROLE-PROTOCOLS.md              ← Role declaration, scope boundaries, handoff mechanism
├── COMMUNICATION-PROTOCOL.md      ← Agent<->Agent and Agent<->Human communication standards
├── KEY_FEATURES_CHECKLIST.md      ← Single Source of Truth (SSoT) for feature evolution tracking
├── KEY_FEATURES_GUIDELINE.md      ← How to construct and maintain feature entries
├── settings.json                  ← MCP server configuration (tool-specific)
├── plans/                         ← Detailed planning proposals requiring human approval
├── issues/                        ← Audit reports, complaints, and technical notes (GitHub Issues-style)
└── todo/                          ← Comprehensive task step lists (post-approval execution plans)
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
- Plans follow the **3S Doctrine** (Security, Sustainability, Scalability) from `AGENTS.md` and must include:
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

`KEY_FEATURES_CHECKLIST.md` is the **Single Source of Truth** for tracking feature evolution.
`KEY_FEATURES_GUIDELINE.md` contains the full reference for constructing and maintaining feature entries — markers, formats, evolution rules, and anti-patterns.

**Key principle**: A feature entry is permanent. It evolves through status changes (`[v]` → `[+]` → `[*]` → `[R]` → `[v]`) — not by creating new entries.

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

## Role Protocols

`ROLE-PROTOCOLS.md` defines:
- **Role declaration** — every session must declare Engineer or Supervisor role before acting
- **Scope boundaries** — what each role can and cannot do
- **Handoff mechanism** — how work moves between Engineer, Supervisor, and Human
- **Multi-agent coordination** — how multiple agents work without conflict
- **Single-agent mode** — how one agent operates when no separate Supervisor is available

Key rule: An agent must never review its own work. If only one agent exists, the human performs the Supervisor role.

## Communication Protocol

`COMMUNICATION-PROTOCOL.md` defines:
- **Communication channels** — where different types of messages are stored (`plans/`, `issues/`, `todo/`)
- **Message formats** — structured templates for review requests, findings, escalations, questions
- **Acknowledgment protocol** — every message requiring action must be acknowledged
- **Agent<->Agent communication** — how engineers and supervisors exchange work
- **Agent<->Human communication** — how agents request approval, escalate, and report
- **Naming convention** — how files are named for traceability: `{YYYY-MM-DD}-{type}-{description}.md`

---

## Project Context for Agents

> **TODO: Fill this section with project-specific information.**
>
> Before an agent begins work, the following context must be provided:
>
> | Item | Description |
> |------|-------------|
> | What is this project? | Brief description of the system and its purpose |
> | Core technology stack | Language, framework, database, testing, code quality tools |
> | Architecture summary | High-level structure, key patterns, layer responsibilities |
> | RBAC / Access control | Roles, permissions, scope restrictions (if applicable) |
> | Configuration system | How config is managed (files, database, environment, etc.) |
> | Database standards | Primary key type, constraints, naming conventions, soft deletes |
> | Quality baselines | Test categories, coverage requirements, CI/CD pipeline, scripts |
> | Essential documentation | Links to architecture, standards, testing, and other reference docs |

---

## When to Ask

- Requirement is ambiguous, incomplete, or contradictory
- Context needed to proceed safely is missing
- Two stated requirements conflict
- A proposed approach requires a decision the human has not authorized
- Multiple valid approaches exist and the choice has significant consequences

## When to Report

- Security vulnerability discovered (P0 — escalate immediately)
- Bug found during audit or review
- Documentation discrepancy found (Sync or Sink violation)
- Test failure that cannot be resolved
- Architectural decision needed

## Output Structure

For complex or consequential tasks:

```
[UNDERSTANDING]   What the agent understood the request to be
[APPROACH]        What approach was chosen and why
[OUTPUT]          The code, configuration, or artifact
[VERIFICATION]    How the output can be confirmed as correct
```

May be abbreviated for simple, low-risk tasks but must never be omitted entirely.

---

## MCP Configuration

Tool-specific MCP server configuration goes here. Edit `settings.json` as needed.

```json
{
    "mcpServers": {
        "_comment": "Add your MCP server configurations here"
    }
}
```

---

## Quick Reference for New Agents

1. **Read `AGENTS.md` first** — it defines how you must think, decide, and act.
2. **Read `AGENT-CHEATSHEET.md`** — quick reference for roles, rules, and workflows.
3. **Read `ROLE-PROTOCOLS.md`** — declare your role, understand scope, learn handoff patterns.
4. **Read `COMMUNICATION-PROTOCOL.md`** — learn how to communicate with other agents and humans.
5. **Check `KEY_FEATURES_CHECKLIST.md`** — understand what exists, what's in progress, and what needs attention.
6. **Read `KEY_FEATURES_GUIDELINE.md`** — learn how to construct feature entries correctly.
7. **Read relevant project documentation** — do not proceed without understanding the standards for the area you're working in.
8. **Declare your role** — are you a Supervisor or an Engineer? Stay in scope.
8. **Use `plans/` for proposals** — before writing code, propose and get approval.
9. **Use `todo/` for execution** — after approval, break work into verified steps.
10. **Use `issues/` for reports** — audit findings, bugs, technical notes.
11. **Never skip tests** — every change must be verified. Every bug fix needs a regression test.
12. **Never skip documentation** — code and docs must stay synchronized.
13. **Ask when uncertain** — early acknowledgment of uncertainty is valued over confident but wrong output.
14. **Hand off when done** — Engineer hands off to Supervisor. Supervisor hands back with verdict or findings.
