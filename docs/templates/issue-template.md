# Issue Template — GitHub Issue Skeleton

## Description

The structure for every GitHub issue, plus the rules that govern issue quality, classification, and
labeling. An issue is only worth filing if a developer or an AI agent can start working from it
without a single follow-up question. The type anchors the issue — it decides which sections are
mandatory (bugs get Reproduction; features get Acceptance Criteria) and which label the tracker
receives.

## The Skeleton

```markdown
## Description

{State the PROBLEM concretely — never the solution. Bugs: what happened vs. what should have
happened. Features: a user story or problem statement.}

## Environment

- Module: `{Module}/{Domain}` (or `Core` when cross-cutting)
- Version: `vX.Y.Z` (report on the deployed version)
- PHP / Laravel: `{version}`
- DB: `{engine}`
- Browser: Chrome/Firefox/Safari (if frontend issue)

## Scope & Impact

| | |
|---|---|
| Module | `{Module}/{Domain}` |
| Affected files | `{relative paths}` |
| Dependencies | {modules/packages affected} |
| Severity | critical / high / medium / low |
| Priority | P0 / P1 / P2 / P3 |

{Impact narration — measurably: how many users/records, current measurement, expected target.
An over-quota placement issue affects all 500+ students during registration week, every occurrence
requires manual cleanup.}

## Reproduction Steps

{For bugs, step-by-step reproduction with exact input, ending in the observed failure.}

1. {Step one}
2. {Step two}

## Expected vs Actual

- **Expected:** {spec-defined behavior, with FR/NFR/UC ID}
- **Actual:** {what happens instead}

## Acceptance Criteria

{For features/refactors. Checklist that must be satisfied for the issue to be closed.}

- [ ] Criterion 1
- [ ] Criterion 2
- [ ] Criterion 3

## Recommended Approach

{Describe 2+ approaches with pros/cons when trade-offs are material; name the recommended one.}

### Approach A: {Name} (Recommended)

{Which files change, which pattern (`docs/guides/arch/{pattern}-pattern.md`) is used, how the data
flow changes.}

**Pros:** {…} **Cons:** {…}

### Approach B: {Name}

{Description, pros, cons.}

## Design Decisions

{Record the decisions made and WHY — the audit trail that prevents re-litigation in review.}

| Decision | Chosen | Rationale |
|----------|--------|-----------|
| {Decision} | {Chosen option} | {Why not the alternative} |

## Related

- {Link to related issue / ADR / docs}

## Implementation Notes (for AI Agents)

- Pattern to follow: {link to docs/guides/arch/{pattern}-pattern.md}
- Module context: {link to docs/refs/modules/{module}.md}
- Reference file: `{path/to/existing/implementation}`
- Invariants: {relevant rules from ../conventions.md or ../index.md}
```

## Rules

### One Issue = One Concern

An issue tracks exactly one concern. A bug stays a bug; a feature stays a feature. Never combine a
bug and a feature, or two unrelated bugs. State the single concern in one sentence; if a second
sentence describes a different concern, split it into a second issue. Title, body, labels,
severity, and acceptance criteria must all describe that same concern.

### Title Format — `{type}: {module}/{submodule} — {short description}`

The title is the tracker's filter surface (`scan_issues.py` groups by it). Examples:

- `bug: enrollment/registration — duplicate entry on concurrent submit`
- `feature: reports/report — add CSV export for grade cards`
- `refactor: user/profile — extract business rules to Entity`

Never "Fix bug" (no type/module) and never state the proposed fix instead of the problem.

### Scope Must Be Specific

Name the concrete module, submodule, files, and behavior — never "Fix enrollment module". A reader
must be able to list the files they will touch and the behavior they will change without asking
questions. Quantify the boundary: what changes, what does not.

### Impact Must Be Measurable

Impact statements use numbers and concrete consequences, never feelings. "Slow" is relative to
nothing; "query takes 3s instead of 200ms for 1000 students" is actionable. State the affected
population, the current measurement, and the expected target.

### Recommended Approach Is Mandatory for Technical Issues

A technical issue (bug / refactor / perf / architecture) must describe HOW to fix it — not just
"fix this". When real trade-offs exist, present at least 2 approaches with pros/cons and name the
recommended one. Each approach names changed files and a pattern; never recommend an approach that
violates an invariant (e.g., moving a business rule into a Model).

### Design Decisions Are Mandatory for Technical Issues

Document decisions as a `Decision | Chosen | Rationale` table, not prose. Unrecorded decisions get
re-litigated in review. Every material choice has a Chosen value and a Rationale that names what
was considered and rejected — a "why not Y" is half a decision.

### No Sensitive Information, Ever

Never include credentials, API keys, tokens, PII, plaintext passwords, or personal data. File
references use project-relative paths — never absolute machine paths that leak environment or
username structure. Redact or replace with placeholders (`<API_KEY>`); describe the shape a secret
needs, not the secret itself. Scan for `key` / `token` / `password` / `secret` / absolute paths
before submitting.

### Label According to Type (+ Label Wajib)

Every issue carries **minimal 3 label wajib**, all from the repo registry — no ad-hoc labels:

```
issue.labels = 1×Type  +  1×Severity  +  1×Priority  [+ optional Area/Status/Auxiliary]
```

**Type** (exactly one — two concerns means two issues):

| Type | Label | When to use |
|------|-------|-------------|
| Bug | `bug` | Behavior doesn't match spec (reference the violated requirement ID) |
| Feature | `enhancement` | New capability |
| Security | `security` | Security vulnerability |
| Refactor | `refactor` | Structure improvement without behavior change |
| Performance | `performance` | Speed/memory optimization |
| Test | `test` | Test addition or fixes |
| Docs | `docs` | Documentation update |
| Chore | `chore` | Tooling, dependencies, config (create label if missing) |

`feature` ↔ `enhancement`, `documentation` ↔ `docs`, `perf` ↔ `performance` are aliases; prefer the
canonical name.

**Severity** (exactly one — real impact in Scope & Impact, not a hunch):
`critical` / `high` / `medium` / `low`.

**Priority** (exactly one — business/release urgency, not a copy of severity): `P0` / `P1` / `P2` /
`P3`.

Misclassification is not cosmetic — it misroutes the issue into the wrong workflow and the wrong
dashboard.

### Reference the Spec — Every Issue Traces to a Requirement

Spec-first applies to issues. A bug references the `FR-*` / `NFR-*` / `UC-*` ID it contradicts; a
feature/refactor issue references its governing spec — or explicitly notes that a spec must be
written first. No behavior without a requirement. Verify the ID exists in the current spec file.

### Deduplicate Before Filing

Before creating an issue, run `python3 tools/scan_issues.py` and search existing open issues for
the same concern/module. A match means either update the existing issue (add evidence) or link it
in the Related section — never file a second copy. Only file new when the concern is genuinely
untracked.

### Pre-Existing Defects Are Filed Immediately

A warning, error, or defect discovered during other work that cannot be safely fixed in-session
becomes a GitHub issue in the same run — a defect noticed is a defect tracked. Capture the evidence
(error message, file:line, repro steps, severity) at discovery; deferral is how defects evaporate.

### Quality Gates — Verification Before Submitting

Before submitting, self-review passes: understandable without additional context; scope specific;
impact measurable; no sensitive data; no duplicates; label matches type (1 Type + 1 Severity + 1
Priority); spec referenced with a requirement ID; every mandatory section filled for the type.

## Quick References

- [`spec-template.md`](spec-template.md) — spec structure an issue traces to
- [`doc-template.md`](doc-template.md) — shared documentation standards
- [`index.md`](index.md) — template catalog hub
- `tools/scan_issues.py` — dedup & triage helper (`python3 tools/scan_issues.py`)