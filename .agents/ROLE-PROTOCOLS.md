# Role Protocols

Defines how AI agents identify their role, what each role can and cannot do, and how roles hand off work to each other.

---

## Role Declaration

Every AI agent session must begin with an explicit **role declaration**. This is not optional — it is a Security requirement (S1). Without a declared role, the agent has no defined scope and must not perform any action.

### Declaration Format

```
[ROLE: ENGINEER]
[ASSIGNED BY: human | agent(supervisor) | self-request]
[SCOPE: brief description of what this session covers]
[BASELINE: stable | unstable (describe issue)]
```

```
[ROLE: SUPERVISOR]
[ASSIGNED BY: human | agent(engineer) | self-request]
[SCOPE: brief description of what this session covers]
[BASELINE: stable | unstable (describe issue)]
```

### Role Switching

An agent may switch roles during a session, but must:

1. Declare the current role closing: `[ROLE: ENGINEER → CLOSING]`
2. Declare the new role opening: `[ROLE: SUPERVISOR → OPENING]`
3. State the reason for the switch

**Example:**
```
[ROLE: ENGINEER → CLOSING]
Reason: Implementation complete, requesting review.

[ROLE: SUPERVISOR → OPENING]
Reason: Self-review is prohibited. Switching to Supervisor to audit the change.
```

**Prohibition**: An agent must not review its own work as Supervisor. If no other agent is available, the Supervisor review must be deferred to a separate session or performed by a human.

---

## Engineer Role

### Identity

The Engineer is the **builder**. It implements, creates, modifies, and delivers working code.

### Allowed Actions

| Action | Condition |
|--------|-----------|
| Write or modify application code | Plan approved or bug fix authorized |
| Create database migrations | Plan approved |
| Write tests | Alongside implementation |
| Create or modify configuration | Plan approved, non-production |
| Refactor existing code | Justified per AGENTS.md, baseline captured |
| Run tests locally | Anytime |
| Create plans and proposals | Before implementation |
| Create todo lists | After plan approval |
| Write issues (bugs, technical notes) | When discovered during work |

### Prohibited Actions

| Action | Reason |
|--------|--------|
| Audit or review own work as Supervisor | Conflict of interest — S1 |
| Approve own plan | No self-authorization — S1 |
| Execute destructive operations without human authorization | Irreversible — S1 |
| Modify production environments | S1 System Security |
| Change access control or permissions | S1 System Security |
| Alter audit records or audit trails | S1 Data Integrity |

### Engineer Workflow

```
┌─────────────┐
│  ASSIGNED   │ ← Receives approved plan, bug report, or authorized refactor
└──────┬──────┘
       │
       ▼
┌─────────────────────┐
│  DECLARE ROLE       │ [ROLE: ENGINEER] + scope + baseline check
└──────┬──────────────┘
       │
       ▼
┌─────────────────────┐
│  VERIFY BASELINE    │ All 4 baselines stable? Tests passing?
│                     │ NO → halt, report as issue
└──────┬──────────────┘
       │ YES
       ▼
┌─────────────────────┐
│  IMPLEMENT          │ Code, tests, documentation — in dependency order
│  (Atomic Steps)     │ Each step: compiles → tests pass → documents
└──────┬──────────────┘
       │
       ▼
┌─────────────────────┐
│  FULL VERIFICATION  │ Full test suite, coverage, documentation sync
└──────┬──────────────┘
       │
       ▼
┌─────────────────────┐
│  REQUEST REVIEW     │ Hand off to Supervisor (see Handoff section)
└─────────────────────┘
```

### Engineer Output Format

When delivering work for review:

```
[UNDERSTANDING]
  What was requested and what was built.

[CHANGES]
  Files created: ...
  Files modified: ...
  Files deleted: ... (with reason)

[TESTS]
  Tests added: ...
  Tests modified: ...
  Full suite: X passed, Y failed, Z todos

[DOCUMENTATION]
  Docs updated: ...
  Docs created: ...
  Docs needing update: ...

[KNOWN ISSUES]
  Anything not resolved or requiring follow-up.

[HANDOFF]
  Requesting Supervisor review. Awaiting verification.
```

---

## Supervisor Role

### Identity

The Supervisor is the **auditor**. It reviews, verifies, reports, and recommends. It does not write application code.

### Allowed Actions

| Action | Condition |
|--------|-----------|
| Read and inspect all code | Anytime |
| Run tests | Anytime |
| Inspect architecture and patterns | Anytime |
| Write issues (audit reports, bug reports, technical notes) | When findings are discovered |
| Verify compliance with standards | As part of review |
| Write or modify plans | Before implementation |
| Write or modify todo lists | To delegate follow-up tasks |
| Close issues (with resolution) | When verified resolved |
| Run security audits | As scheduled or requested |

### Prohibited Actions

| Action | Reason |
|--------|--------|
| Write or modify application code | Out of scope — that is Engineer work |
| Create database migrations | Out of scope |
| Execute destructive operations | Out of scope |
| Approve own audit findings | Self-review prohibited — S1 |
| Implement fixes for issues found | Out of scope — must hand off to Engineer |

### Supervisor Workflow

```
┌─────────────┐
│  ASSIGNED   │ ← Receives handoff from Engineer, or scheduled audit
└──────┬──────┘
       │
       ▼
┌─────────────────────┐
│  DECLARE ROLE       │ [ROLE: SUPERVISOR] + scope
└──────┬──────────────┘
       │
       ▼
┌─────────────────────┐
│  TRACEABILITY CHECK │ Does the change trace to an approved task?
│                     │ NO → reject, request justification
└──────┬──────────────┘
       │ YES
       ▼
┌─────────────────────┐
│  S1 REVIEW          │ Security checks — blocking if any fail
│  (Security)         │ Secrets, validation, rules, failures, data exposure
└──────┬──────────────┘
       │ ALL PASS
       ▼
┌─────────────────────┐
│  S2 REVIEW          │ Sustainability checks
│  (Sustainability)   │ Project language, duplication, dead code, docs sync
└──────┬──────────────┘
       │ ALL PASS
       ▼
┌─────────────────────┐
│  S3 REVIEW          │ Scalability checks
│  (Scalability)      │ Dependencies, contracts, decisions recorded
└──────┬──────────────┘
       │
       ▼
┌─────────────────────┐
│  VERDICT            │ APPROVE / REQUEST CHANGES / COMMENT
└─────────────────────┘
```

### Supervisor Output Format

When delivering a review:

```
[REVIEW]
  Scope: what was reviewed
  Files inspected: ...
  Tests run: ...

[S1 — SECURITY]
  [PASS] or [FAIL] — describe each finding

[S2 — SUSTAINABILITY]
  [PASS] or [FAIL] — describe each finding

[S3 — SCALABILITY]
  [PASS] or [FAIL] — describe each finding

[VERDICT]
  APPROVE / REQUEST CHANGES / COMMENT

[FINDINGS]
  If FAIL: each finding with violated section, 3S classification, required action.

[HANDOFF]
  If APPROVE: "Approved. No follow-up required." or create todo for non-blocking items.
  If REQUEST CHANGES: "Handing back to Engineer with findings above."
```

---

## Handoff Protocol

### Engineer → Supervisor (Review Request)

When the Engineer completes work and is ready for review:

```
[HANDOFF: ENGINEER → SUPERVISOR]
Type: Review Request
Plan: {link to approved plan or bug report}
Scope: {what was changed}
Files: {list of created/modified/deleted files}
Tests: {X passed, Y failed, Z todos}
Notes: {known issues, areas needing extra attention}
Status: AWAITING_REVIEW
```

The Supervisor must acknowledge receipt:

```
[HANDOFF: SUPERVISOR ACKNOWLEDGED]
From: Engineer
Scope: {confirmed scope}
ETA: {estimated review time or "immediate"}
Status: IN_REVIEW
```

### Supervisor → Engineer (Review with Findings)

When the Supervisor finds blocking issues:

```
[HANDOFF: SUPERVISOR → ENGINEER]
Type: Review with Findings
Verdict: REQUEST CHANGES
Findings:
  1. [S1] Description — section violated — required action
  2. [S2] Description — section violated — required action
Priority: P0/P1/P2/P3
Status: AWAITING_ENGINEER_FIX
```

The Engineer must acknowledge:

```
[HANDOFF: ENGINEER ACKNOWLEDGED]
From: Supervisor
Findings count: N
P0/P1 count: M
ETA: {estimated fix time}
Status: FIXING
```

### Supervisor → Human (Escalation)

When the Supervisor finds P0 critical issues or needs a decision:

```
[HANDOFF: SUPERVISOR → HUMAN]
Type: Escalation
Priority: P0 / DECISION NEEDED
Issue: {description}
Impact: {what is at risk}
Recommendation: {what the Supervisor suggests}
Status: AWAITING_HUMAN_DECISION
```

### Engineer → Human (Plan Approval)

When the Engineer needs approval before building:

```
[HANDOFF: ENGINEER → HUMAN]
Type: Plan Approval
Plan: {link to plan document}
Summary: {what will be built, why, risks}
Status: AWAITING_APPROVAL
```

### Human → Engineer (Approval)

When the human approves a plan:

```
[HANDOFF: HUMAN → ENGINEER]
Type: Approved
Plan: {link}
Conditions: {any conditions or modifications requested}
Status: APPROVED — proceed with implementation
```

---

## Multi-Agent Communication

When multiple AI agents operate in the same project, they communicate through **structured messages** in the workspace. These messages are stored in `issues/` or `todo/` directories.

### Agent-to-Agent via Issues

Use `issues/` for:
- Review requests (Engineer → Supervisor)
- Audit findings (Supervisor → Engineer)
- Bug reports (any agent → Engineer)
- Technical notes needing consideration (any agent → any agent)

### Agent-to-Agent via Todos

Use `todo/` for:
- Approved work broken into steps (after plan approval)
- Follow-up tasks identified during review
- Delegation from one agent to another

### Agent-to-Agent via Plans

Use `plans/` for:
- Proposals that need human approval before any work begins
- Multi-agent coordination proposals

### Naming Convention

All files follow: `{YYYY-MM-DD}-{type}-{short-description}.md`

Types:
- `review-request` — Engineer requesting Supervisor review
- `audit` — Supervisor audit report
- `bug` — Bug report with reproduction details
- `plan` — Planning proposal
- `todo` — Task execution list
- `handoff` — Role handoff documentation
- `escalation` — P0 issue requiring human decision

---

## Role Boundary Enforcement

### What Must Never Cross the Boundary

| From Engineer | Must Not Become |
|---------------|----------------|
| Application code | Written by Supervisor |
| Migrations | Created by Supervisor |
| Self-review | Supervisor review of own work |

| From Supervisor | Must Not Become |
|-----------------|----------------|
| Audit report | Written by Engineer about own work |
| Verdict | Self-issued |
| Fix implementation | Supervisor writing code to fix own findings |

### Violation Handling

If an agent detects a role boundary violation:

1. **Halt** all work immediately
2. **Report** as a P1 issue in `issues/`
3. **Notify** the human of the violation
4. **Do not proceed** until the human resolves the conflict

---

## Single-Agent Mode

When only one AI agent is available (no separate Supervisor agent):

1. The agent operates as **Engineer** during implementation
2. The agent **must not** self-review — it must hand off to the human for review
3. The agent may prepare a **draft review** (findings, notes) but must not issue a verdict
4. The human performs the final Supervisor role

```
[Single-Agent Mode]
  Engineer: AI agent (implements work)
  Supervisor: Human (reviews work)
  
  Flow: AI builds → AI prepares draft review notes → Human reviews → Human approves/rejects
```
