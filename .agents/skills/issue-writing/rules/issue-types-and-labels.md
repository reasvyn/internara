# Issue Types & Labels — Classification and Triage

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

Every issue is classified by type before it is written. The type drives the template sections it
will include (bugs get Reproduction; features get Acceptance Criteria), the label it carries, and
its severity/priority framing. Misclassification is not cosmetic — it misroutes the issue into the
wrong workflow and the wrong dashboard.

---

## Choose the Type First

**What it enforces:** Before writing body text, select the issue type from the eight defined types
and apply the matching label. The type is the anchor for everything else in the issue.

**Why it matters:** The type determines which template sections are mandatory (reproduction for bugs,
acceptance criteria for features) and which label the tracker receives. An untyped or mis-typed issue
forces every reader to infer intent, which is exactly the ambiguity issues exist to remove.

**How to apply:** Match the concern to the type table:

| Type            | Label         | When to Use                                   |
| --------------- | ------------- | --------------------------------------------- |
| **Bug**         | `bug`         | Behavior doesn't match specification          |
| **Feature**     | `enhancement` | New capability                                |
| **Security**    | `security`    | Security vulnerability                        |
| **Refactor**    | `refactor`    | Structure improvement without behavior change |
| **Performance** | `perf`        | Speed/memory optimization                     |
| **Test**        | `test`        | Test addition or fixes                        |
| **Docs**        | `docs`        | Documentation update                          |
| **Chore**       | `chore`       | Tooling, dependencies, config                 |

**Pitfalls to avoid:**

- Calling a spec-mismatch a "feature request" — a behavior that contradicts the spec is a `bug`
  referencing the violated requirement ID.
- Using `chore` for real behavior changes — `chore` is tooling/config, not feature work in disguise.
- Labeling every non-bug as `enhancement` — refactors, perfs, and tests are distinct types with
  distinct workflows.

**Verification:** The concern maps to exactly one row in the type table; the label applied equals
that row's label.

---

## Severity vs. Priority — Two Axes, Both Filled

**What it enforces:** Severity (the damage an issue causes) and priority (the urgency of fixing it)
are distinct and both are explicitly set. Severity is an attribute of the defect; priority is a
judgment call about sequencing.

**Why it matters:** A low-severity issue can be high-priority (a cosmetic bug in the login screen
during a marketing push) and a high-severity issue can be low-priority (a rare data-loss path in a
feature nobody uses yet). Collapsing them into one number loses the information triage runs on.

**How to apply:**

- **Severity:** critical (data loss/security breach), high (blocks core flow for many), medium
  (broken flow with workaround), low (cosmetic).
- **Priority:** urgent (fix now, halt other work), high (next batch), medium (this cycle), low
  (backlog).
- Base severity on the measurable impact in the Scope & Impact section; base priority on release
  timing and population.

**Pitfalls to avoid:**

- Making priority a copy of severity.
- Filling severity but leaving priority to "default" — the triage queue needs both.

**Verification:** Both fields are filled with values from their defined sets, and each is justified
by the impact description.

---

## Labels Come From the Repo's Defined Set

**What it enforces:** Only repository-defined labels are applied — the labels below are the fixed
set. No ad-hoc label names.

**Why it matters:** `scan_issues.py` and the tracker's filters group by fixed labels. An
out-of-repo label silently drops the issue from every summary report, so tracking becomes
incomplete without any visible error.

**How to apply:** Apply the type label plus any secondary label that already exists in the repo
(e.g., `good first issue` for a well-scoped newcomer task). The label set:

| Label              | Color     | Description                |
| ------------------ | --------- | -------------------------- |
| `bug`              | `#d73a4a` | Something isn't working    |
| `enhancement`      | `#a2eeef` | New feature or request     |
| `security`         | `#000000` | Security vulnerability     |
| `refactor`         | `#fbca04` | Code restructuring         |
| `perf`             | `#0e8a16` | Performance improvement    |
| `test`             | `#fef2c0` | Test additions or fixes    |
| `docs`             | `#0075ca` | Documentation              |
| `chore`            | `#bfdadc` | Maintenance, tooling, deps |
| `good first issue` | `#7057ff` | Good for newcomers         |
| `help wanted`      | `#008672` | Extra attention needed     |
| `duplicate`        | `#cfd3d7` | Already reported           |
| `wontfix`          | `#ffffff` | Will not be addressed      |

**Pitfalls to avoid:**

- Combining `bug` with both `security` and `perf` — that is three concerns (one-issue-one-concern
  applies to labels too).
- Creating a new label at submission time instead of using the set.

**Verification:** Every label on the issue exists in the set above; the label-to-type mapping is
exact.

---

## Auxiliary Labels Are Applied Deliberately

**What it enforces:** `good first issue`, `help wanted`, `duplicate`, and `wontfix` are applied only
when their conditions hold — never out of convenience.

**Why it matters:** `good first issue` routes the issue to newcomers; misusing it floods that backlog
with work that isn't newcomer-sized. `duplicate` is an evidence statement about tracker health;
`wontfix` is a maintained decision that must survive the issue's lifetime (and links to the
reasoning).

**How to apply:**

- `good first issue`: scope is small, well-documented, no deep domain prerequisite.
- `help wanted`: the maintainers are inviting outside contribution.
- `duplicate`: applied by triage when this issue duplicates a tracked concern — the issue is then
  closed with a link to the original.
- `wontfix`: applied when the decision is made to not resolve; the body records why.

**Pitfalls to avoid:**

- Marking every trimmed-down bug as `good first issue`.
- Applying `wontfix` without a recorded reason — closed-without-reason issues get re-filed.

**Verification:** Each auxiliary label's condition is true at application time and recorded in the
issue body.

---

## References

| Topic                        | Asset                                       |
| ---------------------------- | ------------------------------------------- |
| Label mapping to quality     | `rules/issue-quality.md` (this skill)       |
| Unified template             | `rules/issue-template.md` (this skill)      |
| Issue scanner & labels       | `scripts/scan_issues.py`                    |