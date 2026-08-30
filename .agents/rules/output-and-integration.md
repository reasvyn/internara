# Output & Integration — JSON Report Structure, Automation & Skill Delegation

Arch-guard is a **quality gate, not a source of intent** — it verifies that code conforms to the
governing spec's requirements and emits structured findings. This rule defines (1) the JSON report
contract every scan produces, (2) which scanner owns which rule family, and (3) how arch-guard
delegates to and is consumed by the other skills.

---

## Report Structure

Every scanner emits a JSON report with this schema:

```json
{
  "scan_version": "1.0.0",
  "scan_type": "full|targeted|module",
  "module": null,
  "timestamp": "2026-07-11T12:00:00+07:00",
  "summary": {
    "total_checks": 0,
    "passed": 0,
    "failed": 0,
    "by_severity": { "critical": 0, "high": 0, "medium": 0, "low": 0 }
  },
  "findings": [
    {
      "id": "C1-001",
      "rule": "C1",
      "severity": "high",
      "file": "app/Student/Livewire/StoreStudentForm.php",
      "line": 42,
      "message": "Model::create() found in Livewire component",
      "suggestion": "Use StoreStudentAction instead",
      "reference": "docs/guides/arch/action-pattern.md"
    }
  ]
}
```

**Intent:** Every finding is self-contained — a machine and a human can locate the problem (file +
line), understand it (message), act on it (suggestion), and verify the governing rule (reference)
without opening another document.

**Why it matters:** `issue-writing` and `spec-audit` consume these findings directly to build issue
bodies. A finding without a `reference` cannot be traced to the authoritative doc; a `suggestion`
without `file`/`line` is not actionable.

**How to apply when writing/editing a scanner (see `script-automation` skill):**

- `id` unique per run, rule-referenced (`C1-001`).
- `message` human-readable; `suggestion` concrete ("Use StoreStudentAction instead").
- `reference` points at the authoritative doc for the rule.
- `by_severity` always contains all four buckets (zeros allowed).

---

## Automation — Which Scanner Owns What

| Scanner | Rule families |
|---------|--------------|
| `scan_violations.py` | C1-C8, D1-D6, security, performance |
| `scan_class_contracts.py` | Action/Entity/DTO/Model/Enum contracts |
| `scan_security.py` | XSS, SQL injection, mass assignment, auth patterns |
| `scan_naming.py` | File, class, method, variable naming conventions |
| `scan_conventions.py` | strict_types, Fillable, debug calls, hardcoded strings |
| `scan_architecture.py` | Component counts per module, submodule structure |
| `scan_dead_code.py` | Unregistered observers, unused DTOs, orphan events |
| `scan_doc_links.py` | Doc link integrity (all markdown) |
| `scan_tests.py` | Per-module test results |
| `scan_issues.py` | Spec↔code gap analysis via GitHub issue metrics |

**Intent:** Rule families are owned by exactly one scanner; no scanner re-checks another's surface.
This keeps reports non-overlapping and lets a developer run the scanner that maps to the concern they
are changing.

**Why it matters:** A duplicated check produces duplicate issues — two entries for the same defect with
different IDs, argued by two files. Standard output allows `issue-writing` to dedupe cleanly.

**How to apply:** Before extending a scanner, confirm the rule family belongs to it; if it belongs to
another scanner, extend that one instead (single source of truth).

---

## Integration with Other Skills

| Skill | How arch-guard integrates |
|-------|--------------------------|
| `code-writing` | Validate new code before commit |
| `code-refactoring` | Verify refactored code maintains contracts |
| `livewire-development` | Check Livewire components for C1 violations |
| `pest-testing` | Verify test structure conventions |
| `spec-audit` | Reference for contract verification and severity classification |
| `issue-writing` | Use violation data for issue descriptions |
| `sync-docs` | Use conventions for documentation accuracy |
| `test-writing` | Validate test file conventions |
| `doc-writing` | Validate doc structure conventions |

**Intent:** arch-guard is the **delegation target** for every other code skill's quality checks
(per its skill description, all code skills delegate quality checks here). It never initiates
feature work; it verifies work the other skills produced.

**Why it matters:** Without a single delegation target, each skill would write its own ad-hoc
regex/scan and drift from the canonical rule set. arch-guard centralizes enforcement so the rule set
lives in one place (this skill + `docs/`) and is executed by one script family.

**How to apply:**

- When `code-writing` produces new code → run the appropriate scanner before commit.
- When `spec-audit` needs contract verification or severity classification → quote
  `scan_class_contracts.py` / `scan_violations.py` findings.
- When `issue-writing` needs an evidence block → pull `file`, `line`, `message`,
  `suggestion`, `reference` from the JSON report.

---

## Spec-First Quality Gate

**Why it matters:** arch-guard only verifies that code conforms to the governing spec's requirements
(FR/NFR/UC IDs). It is a guard against *drift*, not a source of *intent*.

**How to apply:** If an audit surfaces a behavior with **no requirement** behind it, the finding is a
**spec gap** — report it via `spec-audit` / `issue-writing` rather than changing code (Spec-First
Doctrine). Never "fix" a finding by inventing behavior.

**Failure mode if ignored:** An agent changing behavior in response to a scan finding without adding a
requirement violates the no-behavior-without-a-requirement invariant; the next spec-audit re-flags it
and the change is rolled back.

---

## Scan Scoping

- **Before any commit** — run targeted checks on changed files.
- **After feature implementation** — run the full module scan (`--module {Name}`).
- **Periodic audit** — run the full codebase scan.
- **Onboarding new code** — validate against all contracts.
- **CI/CD gate** — run automated checks with `--strict` (exit code 1 on any finding).
- **L-size audits** — split by module into sessions; inform the user and propose a plan first; never
  run all scanners blindly on a full-module set without batching.

## Verification

```bash
python3 tools/scan_violations/cli.py            # full invariant+security+performance scan
python3 tools/scan_class_contracts/cli.py       # class contract compliance
python3 tools/scan_security/cli.py              # S1-S10
python3 tools/scan_naming/cli.py                # naming
python3 tools/scan_conventions/cli.py           # conventions
python3 tools/scan_doc_links/cli.py             # doc links
python3 tools/scan_tests/cli.py                 # per-module test results
python3 tools/scan_issues/cli.py                # spec↔code gap analysis
```

Validate the JSON schema against this rule's report contract whenever a scanner is added or modified
(the `script-automation` skill defines the full schema). All reports land in
`tools/outputs/{timestamp}-{description}.json`.
