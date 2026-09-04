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

Full table: `AGENTS.md` §Automation Scripts. Key commands:

| Script | Command |
|--------|---------|
| `scan_violations.py` | `python3 tools/scan_violations.py` |
| `scan_class_contracts.py` | `python3 tools/scan_class_contracts.py` |
| `scan_security.py` | `python3 tools/scan_security.py` |
| `scan_dead_code.py` | `python3 tools/scan_dead_code.py` |
| `run_module_tests.py` | `python3 tools/run_module_tests.py --module {Module}` |

Output: `tools/outputs/{timestamp}-{description}.json`.

**Automation-First:** before doing manual or repeated work, check `tools/` and this table for an existing scanner or helper. Never redo by hand what a script does. If a recurring pattern has no script, load `script-automation` to add one.

---
*Source: AGENTS.md §Metacognitive Loop & §Automation Scripts. For script conventions, see `.agents/skills/script-automation/SKILL.md`.*