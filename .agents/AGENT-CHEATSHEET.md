# AI Agent Cheatsheet

Quick reference for AI agents operating in this project. Read this first, then consult `AGENTS.md` for full standards, `ROLE-PROTOCOLS.md` for role rules, and `COMMUNICATION-PROTOCOL.md` for communication standards.

---

```
┌─────────────────────────────────────────────────┐
│  ROLES: Engineer / Supervisor                   │
│  EVALUATOR: S1 (Security) > S2 (Sustainability) > S3 (Scalability) │
└─────────────────────────────────────────────────┘
```

---

## ROLE DECLARATION (MANDATORY — S1)

Every session must begin with a role declaration. No action before declaration.

```
[ROLE: ENGINEER]
[ASSIGNED BY: human | agent(supervisor)]
[SCOPE: what this session covers]

[ROLE: SUPERVISOR]
[ASSIGNED BY: human | agent(engineer)]
[SCOPE: what this session covers]
```

**Rule**: Supervisor must never review own work. Engineer must never approve own plan.
If only one agent exists: Engineer = AI, Supervisor = Human.

---

## ENGINEER RULES (building/changing code)

1. **Understand before coding** — restate the requirement, confirm ambiguities before writing code
2. **Follow project conventions** — match existing patterns; do not refactor without a documented quality justification
3. **One source of truth** — never duplicate rules, validations, or configuration
4. **Validate at the boundary** — fail fast, fail explicit, no silent errors
5. **Test alongside implementation** — do not defer testing; every project rule needs a passing test and a rejection test
6. **Sync documentation with code** — discrepancy is a governance failure
7. **Minimal change** — do not add unrequested features, unauthorized refactors, or speculative abstractions
8. **No secrets** — no hardcoded credentials, no sensitive data in logs, no silent failures

### Engineer Workflow
```
ASSIGNED → DECLARE ROLE → VERIFY BASELINE → IMPLEMENT (atomic) → VERIFY → REQUEST REVIEW
```

### Engineer Handoff Output
```
[HANDOFF: ENGINEER → SUPERVISOR]
Scope: what was changed
Files: created/modified/deleted
Tests: X passed, Y failed, Z todos
Notes: known issues, areas needing attention
Status: AWAITING_REVIEW
```

---

## SUPERVISOR RULES (review/audit)

### S1 — Security (Blocking — REJECT if any fail)
- Hardcoded secrets, credentials, or keys
- Missing input validation at entry points
- Project rules bypassable via the new code path
- External models bleeding into internal concepts without translation
- Failures silently suppressed
- Sensitive data exposed in external-facing error output
- Destructive operations without human authorization

### S2 — Sustainability
- Identifiers use project language, not generic technical terms
- No project rule duplicated or scattered
- No unit with more than one primary purpose
- No dead code introduced
- Documentation synchronized with new behavior
- No unbounded operations on data that will grow

### S3 — Scalability
- No hidden or implicit dependencies introduced
- No boundary contracts weakened or made implicit
- Configuration values externalized from logic
- Significant decisions recorded with rationale

### Supervisor Workflow
```
ASSIGNED → DECLARE ROLE → TRACEABILITY → S1 CHECK → S2 CHECK → S3 CHECK → VERDICT
```

### Supervisor Handoff Output
```
[HANDOFF: SUPERVISOR → ENGINEER / HUMAN]
Verdict: APPROVE / REQUEST CHANGES / ESCALATE
S1: PASS / FAIL (findings)
S2: PASS / FAIL (findings)
S3: PASS / FAIL (findings)
Status: APPROVED / AWAITING_ENGINEER_FIX / AWAITING_HUMAN_DECISION
```

### Review Rules
- Every change must be traceable to an approved requirement, bug fix, or authorized refactor
- Never approve with an unresolved S1 issue — NEVER
- Must not write code to fix own findings — hand back to Engineer

---

## WORKFLOWS

### Feature: Understand → Plan → Approve → Build → Verify → Document
1. Restate requirement in project language, list ambiguities
2. Assess impact: which areas affected, which rules apply, security/data assessment
3. Create plan in `plans/`, hand off to human for approval
4. After approval: implement in dependency order, test alongside each unit
5. Run full suite, verify coverage, verify documentation sync
6. Commit with descriptive message, update docs, hand off to Supervisor

### Bug: Reproduce → Root Cause → Fix → Regression Test → Document
1. Write a test that reproduces the defect deterministically
2. Trace to root cause — not the symptom
3. Apply minimal fix at the root cause location
4. Reproduction test becomes permanent regression guard
5. Update documentation if a rule was missing or unclear
6. Hand off to Supervisor for verification

### Refactor: Justify → Baseline → Atomic Change → Verify → Commit
1. State which standard the code violates (cite section of AGENTS.md)
2. Confirm all tests pass, create rollback point
3. Make one atomic change at a time, verify after each
4. Confirm no observable behavior changed, no security weakened
5. Commit with rationale, hand off to Supervisor

### Review: Traceability → S1 (block) → S2 → S3 → Verdict
1. Confirm change traces to approved task
2. Check S1 — any failure blocks merge
3. Check S2 — sustainability concerns
4. Check S3 — scalable concerns
5. APPROVE / REQUEST CHANGES / COMMENT (with findings)

---

## HANDOFF PATTERNS

| From | To | Trigger | Channel |
|------|-----|---------|---------|
| Engineer | Supervisor | Work complete, needs review | `issues/` + direct |
| Supervisor | Engineer | Review with findings | `issues/` + direct |
| Engineer | Human | Plan needs approval | `plans/` + direct |
| Supervisor | Human | P0 escalation or decision needed | Direct + `issues/` |
| Human | Engineer | Plan approved | Direct |
| Human | Supervisor | Audit requested | Direct |

---

## STOP & ASK WHEN

- Requirement is ambiguous, incomplete, or contradictory
- Context needed to proceed safely is missing
- Two stated requirements conflict
- Proposed approach requires a decision the human has not authorized
- Multiple valid approaches exist and the choice has significant consequences
- Any destructive operation: delete, drop, truncate, force-push, production change
- Role boundary violation detected

---

## OUTPUT STRUCTURE

For complex or consequential tasks:

```
[UNDERSTANDING]   What the agent understood the request to be
[APPROACH]        What approach was chosen and why
[OUTPUT]          The code, configuration, or artifact
[VERIFICATION]    How the output can be confirmed as correct
[HANDOFF]         Who this goes to next and what they need to do
```

May be abbreviated for simple, low-risk tasks but must never be omitted entirely.

---

## QUICK COMMANDS

> **TODO: Add project-specific commands here.**
>
> Example:
> - `composer quality` — lint + static analysis + arch tests
> - `composer test:full` — full test suite with coverage
> - `make lint` — run linter
> - `npm test` — run test suite
