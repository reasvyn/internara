# Metacognitive Loop & Automation Scripts

> **Curated mandatory known context** — the construct→evaluate→verify→decide loop and available devtools. Read at start of every session.

## Metacognitive Loop

```
CONSTRUCT → EVALUATE → VERIFY → DECIDE
```

1. **CONSTRUCT** — Read relevant docs and existing code; verify paths and signatures; consider multiple approaches
2. **EVALUATE** — Does it match requirements (FR/NFR/UC from the governing spec)? Respect layer boundaries? Do ONE thing?
3. **VERIFY** — Lint + static analysis + tests pass; no debug calls; `__()` for strings
4. **DECIDE** — Accept / Revise / Split / Escalate / Defer
   - **Split** when: task classified **L** (Size Triage) or scope grew beyond one session — inform the user, propose a session plan, never push through in one pass
   - **Escalate** when: the decision changes scope or architecture, or a governing spec is missing or ambiguous — surface it to the user rather than guessing

## Automation Scripts

| Script | What it does | Command |
|--------|-------------|---------|
| `scan_files.py` | File counts and lines of code per module | `python3 tools/scan_files/cli.py` |
| `scan_architecture.py` | Component counts per module, submodule structure | `python3 tools/scan_architecture/cli.py` |
| `scan_violations.py` | C1-C8, D1-D6 invariant violations | `python3 tools/scan_violations/cli.py` |
| `scan_class_contracts.py` | Action/Entity/DTO/Model/Enum class contracts | `python3 tools/scan_class_contracts/cli.py` |
| `scan_security.py` | XSS, SQLi, CSRF, auth patterns | `python3 tools/scan_security/cli.py` |
| `scan_naming.py` | Naming conventions | `python3 tools/scan_naming/cli.py` |
| `scan_conventions.py` | strict_types, Fillable, debug calls | `python3 tools/scan_conventions/cli.py` |
| `scan_doc_links.py` | Broken links in docs | `python3 tools/scan_doc_links/cli.py` |
| `scan_tests.py` | Per-module test results | `python3 tools/scan_tests/cli.py` |
| `scan_skills.py` | SKILL.md meta-framework consistency | `python3 tools/scan_skills.py` |
| `scan_issues.py` | GitHub issues by module/severity | `python3 tools/scan_issues/cli.py` |
| `scan_dead_code.py` | Dead code detection | `python3 tools/scan_dead_code/cli.py` |

Output: `tools/outputs/{timestamp}-{description}.json`.

**Automation-First:** before doing manual or repeated work, check `tools/` and this table for an existing scanner or helper. Never redo by hand what a script does. If a recurring pattern has no script, load `script-automation` to add one.

---
*Source: AGENTS.md §Metacognitive Loop & §Automation Scripts. For script conventions, see `.agents/skills/script-automation/SKILL.md`.*