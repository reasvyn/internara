# Communication Protocol

Defines how AI agents communicate with each other and with humans. Every communication must be structured, traceable, and stored in the workspace.

---

## Communication Principles

### 1. Explicit Over Implicit
No communication through implied context. Every message must state its purpose, sender, receiver, and expected response clearly.

### 2. Traceable
Every communication must be stored in the workspace (`issues/`, `todo/`, `plans/`) so it can be audited later. Verbal-only communication (not stored) is invalid.

### 3. Structured
Messages follow defined formats. Free-form messages are allowed only for clarification, not for formal communication.

### 4. Acknowledged
Every message requiring action must receive an acknowledgment. Unacknowledged communication is considered undelivered.

### 5. Reuse Existing Files (File Minimization)
When responding to a message or updating status, **DO NOT create a new file**. Instead:
- **Reply in the same file** — append or update the existing `issues/`, `todo/`, or `plans/` file
- **Update status inline** — change `[STATUS: AWAITING_REVIEW]` to `[STATUS: COMPLETE]` in the same file
- **Avoid redundant files** — don't create `review-request-2.md` if `review-request.md` already exists for the same topic
- **One topic = One file** — related communication stays in one file, not spread across multiple files

**Exception:** Only create a new file if:
  - The original file is closed/resolved (prefixed with `[CLOSED]`)
  - The topic is genuinely new (not a reply to existing communication)

---

## Communication Channels

| Channel | Location | Purpose | Urgency |
|---------|----------|---------|---------|
| **Plans** | `plans/` | Proposals requiring approval | Normal |
| **Issues** | `issues/` | Findings, bugs, reports, notes | Variable (P0-P3) |
| **Todos** | `todo/` | Task lists, delegation, follow-up | Normal |
| **Direct** | Chat/terminal | Clarification, quick questions | Immediate |
| **Handoff** | Chat/terminal + file | Role transitions | High |

---

## Agent-to-Agent Communication

### Scenario 1: Engineer Requests Review from Supervisor

**Trigger:** Engineer completes implementation and is ready for review.

**Channel:** Create issue in `issues/` + direct message.

**Issue file:** `{YYYY-MM-DD}-review-request-{description}.md`

```markdown
# Review Request: {Short Description}

**From:** Engineer Agent
**To:** Supervisor Agent
**Date:** YYYY-MM-DD
**Type:** Review Request
**Priority:** P2 (normal review) / P1 (security-sensitive)
**Status:** AWAITING_REVIEW

---

## Scope
{What was built or changed, in project language}

## Plan Reference
{Link to approved plan, or bug report ID if bug fix}

## Changes
- Created: file1, file2
- Modified: file3, file4
- Deleted: file5 (reason)

## Tests
- Added: N tests
- Full suite: X passed, Y failed, Z todos

## Areas Needing Extra Attention
1. {Specific file or concern the Engineer wants the Supervisor to focus on}

## Known Issues
{Anything not resolved, anything deferred}

---

## Handoff Status
Status: AWAITING_REVIEW
```

**Supervisor Response:**

```markdown
# Review: {Short Description}

**From:** Supervisor Agent
**To:** Engineer Agent
**Date:** YYYY-MM-DD
**Type:** Review Response
**Status:** REVIEWED

---

## Scope Reviewed
{What was reviewed — files, tests, documentation}

## S1 — Security
| Check | Result | Finding |
|-------|--------|---------|
| Secrets | [PASS/FAIL] | ... |
| Input validation | [PASS/FAIL] | ... |
| Rule enforcement | [PASS/FAIL] | ... |
| Failure handling | [PASS/FAIL] | ... |
| Data exposure | [PASS/FAIL] | ... |
| Destructive ops | [PASS/FAIL] | ... |

## S2 — Sustainability
| Check | Result | Finding |
|-------|--------|---------|
| Project language | [PASS/FAIL] | ... |
| Rule duplication | [PASS/FAIL] | ... |
| Dead code | [PASS/FAIL] | ... |
| Documentation sync | [PASS/FAIL] | ... |
| Unbounded ops | [PASS/FAIL] | ... |

## S3 — Scalability
| Check | Result | Finding |
|-------|--------|---------|
| Hidden dependencies | [PASS/FAIL] | ... |
| Contract integrity | [PASS/FAIL] | ... |
| Decision records | [PASS/FAIL] | ... |

## Verdict
[APPROVE / REQUEST CHANGES / COMMENT]

## Findings (if REQUEST CHANGES)
1. [S1] {description} — violated section — required action
2. [S2] {description} — violated section — required action

---

## Handoff Status
{If APPROVE: "Approved. Handing back to Engineer for merge."}
{If REQUEST CHANGES: "Handing back to Engineer with findings above."}
Status: {APPROVED / AWAITING_ENGINEER_FIX}
```

---

### Scenario 2: Supervisor Discovers Issue During Audit

**Trigger:** Supervisor finds a bug, security issue, or quality problem.

**Channel:** Create issue in `issues/`.

**Issue file:** `{YYYY-MM-DD}-audit-{type}-{description}.md`

```markdown
# Audit Finding: {Short Description}

**From:** Supervisor Agent
**To:** Engineer Agent
**Date:** YYYY-MM-DD
**Type:** Audit Finding
**Priority:** P0 / P1 / P2 / P3
**Status:** OPEN

---

## Summary
{What was found, in project language}

## Classification
- Type: Security / Logic / Degradation / Documentation
- 3S Dimension: S1 / S2 / S3

## Affected Area
- Files: ...
- Rules: ...

## Evidence
{Reproduction steps, code excerpts, test results}

## Impact
{What happens if this is not fixed}

## Recommended Action
{What the Supervisor suggests the Engineer do}

---

## Handoff Status
Status: AWAITING_ENGINEER_ACTION
```

**Engineer Response (in same file or new todo):**

```markdown
## Engineer Response
**Date:** YYYY-MM-DD
**Action Taken:** {What was done to resolve the finding}
**Tests Added:** {Regression test details}
**Status:** RESOLVED / REJECTED (with reason) / ESCALATED (to human)
```

---

### Scenario 3: Engineer Proposes Plan (Needs Human Approval)

**Trigger:** Engineer needs to build something new or make a significant change.

**Channel:** Create plan in `plans/` + notify human.

**Plan file:** `{YYYY-MM-DD}-plan-{description}.md`

```markdown
# Plan: {Short Description}

**From:** Engineer Agent
**To:** Human
**Date:** YYYY-MM-DD
**Type:** Implementation Plan
**Status:** AWAITING_APPROVAL

---

## Requirement Summary
{What is being requested, in project language}

## Project Impact
- Affected areas: ...
- Boundary crossings: ...
- Project language terms: new/changed/affected

## Project Rules Involved
1. {Rule} — currently lives at: {location} — will not be bypassed
2. {Rule} — currently lives at: {location} — will not be bypassed

## Security and Data Assessment
- Data created/read/updated/deleted: ...
- Sensitive data involved: yes/no — if yes, how protected
- Access controls required: ...
- Irreversible operations: yes/no — if yes, what

## Implementation Approach
{How it will be built, in terms fitting the project}

### Alternatives Considered
1. {Alternative} — why not chosen
2. {Alternative} — why not chosen

### 3S Impact
- S1 (Security): {How security is served or risk mitigated}
- S2 (Sustainability): {How maintainability is affected}
- S3 (Scalability): {How future evolution is affected}

## Known Risks and Tradeoffs
1. {Risk} — likelihood: low/medium/high — mitigation: ...

## Decomposed Tasks
1. {Task} — independently verifiable — acceptance criterion: ...
2. {Task} — independently verifiable — acceptance criterion: ...

---

## Approval Status
Status: AWAITING_APPROVAL
```

**Human Response (in same file):**

```markdown
## Human Decision
**Date:** YYYY-MM-DD
**Decision:** APPROVED / APPROVED WITH CONDITIONS / REJECTED
**Conditions:** {Any modifications or constraints}
**Notes:** {Feedback or direction}
```

---

## Agent-to-Human Communication

### When the Agent Must Contact the Human

| Situation | Channel | Priority | Format |
|-----------|---------|----------|--------|
| Plan approval needed | `plans/` + direct | Normal | Plan document |
| P0 security finding | Direct + `issues/` | Critical | Escalation format |
| Role boundary violation | Direct + `issues/` | High | Violation report |
| Ambiguous requirement | Direct | High | Question format |
| Destructive operation | Direct | Critical | Preview + confirmation request |
| Architectural decision needed | Direct + `plans/` | Normal | Decision proposal |
| Baseline instability | Direct + `issues/` | High | Baseline report |

### Escalation Format

```
[ESCALATION]
From: {Agent role}
Priority: P0 / DECISION NEEDED
Issue: {Clear, concise description}
Impact: {What is at risk if not resolved}
Recommendation: {What the agent suggests}
Urgency: {Now / This cycle / Next cycle}
Status: AWAITING_HUMAN
```

### Question Format

```
[QUESTION]
From: {Agent role}
Context: {What prompted the question}
Question: {Specific, answerable question}
Options:
  A: {Option A description}
  B: {Option B description}
  C: {Other}
Recommendation: {Which option the agent recommends and why}
Status: AWAITING_HUMAN_ANSWER
```

### Destructive Operation Preview

```
[DESTRUCTIVE OPERATION REQUEST]
From: {Agent role}
Operation: {What will be done}
Target: {What will be affected}
Irreversible: Yes / No (if no, describe rollback)
Preview:
  {List of what will change, added, removed}
Risk: {What could go wrong}
Reason: {Why this is necessary}
Status: AWAITING_HUMAN_CONFIRMATION
```

---

## Acknowledgment Protocol

Every message that requires action must follow this pattern:

```
Sender:   [MESSAGE] → Receiver
Receiver: [ACKNOWLEDGED] → Sender
Sender:   [CONFIRMED] → Receiver (if needed)
```

### Acknowledgment Types

| Message Type | Expected Acknowledgment |
|-------------|------------------------|
| Review request | "Received. Reviewing now." or "Received. ETA: ..." |
| Finding/bug | "Acknowledged. Investigating." |
| Plan approval | "Plan received. Reviewing for approval." |
| Escalation | "Escalation received. Deciding now." |
| Question | Answer directly or "Need more context: ..." |
| Destructive operation | "Approved — proceed." or "Denied — reason." |

### Timeout

If a message requiring acknowledgment is not acknowledged within a reasonable time:

1. Agent sends a follow-up reminder
2. If still no response, agent halts work in that area
3. Agent reports the block as an issue

---

## Message Storage and Naming

All formal communication must be stored as files in the workspace.

### Naming Convention

`{YYYY-MM-DD}-{type}-{short-description}.md`

| Type | Purpose | Directory |
|------|---------|-----------|
| `review-request` | Engineer requesting Supervisor review | `issues/` |
| `review` | Supervisor review response | `issues/` |
| `audit` | Audit findings | `issues/` |
| `bug` | Bug report with reproduction | `issues/` |
| `escalation` | P0 issue requiring human decision | `issues/` |
| `plan` | Implementation proposal | `plans/` |
| `todo` | Task execution list | `todo/` |
| `decision` | Significant decision record | `.agents/` or `docs/` |

### Closed Items

When an issue is resolved:
- Rename file: `[CLOSED]-{YYYY-MM-DD}-{type}-{description}.md`
- Add resolution summary at the end of the file
- Do not delete — closed items remain as audit history

---

## Communication Anti-Patterns

### What Must Not Happen

| Anti-Pattern | Why It Is Wrong | Correct Approach |
|-------------|-----------------|-----------------|
| Agent reviews own work | Conflict of interest — S1 | Hand off to Supervisor or human |
| Agent approves own plan | No self-authorization — S1 | Requires human approval |
| Agent executes destructive ops without confirmation | Irreversible — S1 | Preview + wait for human |
| Agent proceeds on ambiguous requirement | Risk of wrong output — S2 | Ask before proceeding |
| Agent hides findings from human | Invisible debt — S2 | Report all findings |
| Communication not stored in workspace | Not auditable — S1 | Store all formal messages |
| Agent fabricates human approval | S1 violation | Never assume approval |
| Agent skips acknowledgment | Message may be lost | Always acknowledge |
| **Creating redundant reply files** | Too many files, hard to track — S2 | **Reuse existing file for replies** |
| **Multiple files for same topic** | Confusion, scattered context — S2 | **One topic = One file** |
| **New file for every status update** | File proliferation — S2 | **Update status inline in same file** |

---

## Multi-Agent Coordination

When multiple agents operate simultaneously:

### Coordination Rules

1. **One Engineer at a time per area** — multiple Engineers can work on different areas, but not the same file or module simultaneously.
2. **One Supervisor per review** — multiple Supervisors can audit different areas, but the same change gets one review verdict.
3. **Plan lock** — when a plan is being reviewed by the human, no Engineer may start implementing it until approval is recorded.
4. **Baseline lock** — when a Supervisor is auditing, the Engineer must not modify the codebase until the audit is complete.

### Conflict Resolution

When two agents need the same resource or are blocking each other:

```
[CONFLICT]
Agent 1: {role and what it needs}
Agent 2: {role and what it needs}
Conflict: {Description of the conflict}
Resolution: {Agent 1 yields / Agent 2 yields / Escalate to human}
```

**Priority for conflict resolution:**
1. P0 security finding (any agent) — highest
2. Supervisor review in progress
3. Engineer implementation
4. Non-urgent tasks
