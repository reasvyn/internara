# Agent Skill Integration — Reuse-Before-Create, Handoffs & the Skill→Script→Skill Flow

Scripts exist to serve the agent-skill layer. `arch-guard`, `context-awareness`, and the other skills
document scripts in their `## Automation Scripts` tables, run them as quality gates, and consume their
JSON findings. This rule defines how a script relates to the skills, when a new script is justified,
and how to ship one so the integration stays coherent.

---

## Upstream / Downstream

| Role | Skill |
|------|-------|
| **Upstream** | `arch-guard` (verify gates), all skills with `## Automation Scripts` tables |
| **This skill** | **TOOLING** — script standards and conventions |
| **Downstream** | `arch-guard`, `context-awareness` (Automation Scripts reference) |

**Phase Context:** this skill is a tooling standards reference, not a 4-phase implementation flow. It
adds script-writing standards on top of the canonical `agent-workflow` (5-step pipeline, Size Triage,
commit format).

---

## Reuse-Before-Create (Non-Negotiable)

Before writing a new script — check what already exists:

1. Spec-first: only add a scanner/script when a governing spec (or a documented automation need)
   justifies it. Never build a script to work around a one-off problem a reusable script already covers.
2. Reuse before create: check `tools/README.md` and the `## Automation Scripts` tables in other
   skills. If the pattern is covered, use the existing tool. Never duplicate a scanner that already
   owns a rule family (see `arch-guard/rules/output-and-integration.md`).
3. One-off / few-off scripts NEVER go in `tools/`: a script used only a handful of times — a single
   migration batch, temporary data fix, one-time conversion or bulk edit — must be written to `/tmp`
   (e.g. `/tmp/migrate_x.py`), run, then discarded. `tools/` is exclusively for durable, reusable
   devtools with long-term value; committing throwaway scripts pollutes the toolchain and buries the
   scanners agents rely on.
4. Size-aware: a multi-scanner initiative is **M/L** per Size Triage — stage it per script, inform the
   user before committing if it crosses into **L**.
5. Verify: run `python3 tools/{name}.py --module {Module} --strict` and confirm the JSON output schema
   before integrating into any skill.
6. Git verify: before committing a script change, run `git status` + `git diff` to confirm only intended
   files changed and no unrelated edits or lost content.

**Why reuse matters (dedup doctrine):** a second script that greps for C1 overlaps
`scan_violations.py`, produces duplicate findings, and drifts from the canonical rule the moment rule
text changes. The Automation-First discipline is *check the table, run the script* — not *write
another script*.

---

## Skill Handoffs (Actionable)

| Condition | Action |
|-----------|--------|
| Creating/modifying a script in `tools/` | Follow this skill's template and interface |
| A skill needs a new automation | Check `tools/README.md` + skill `## Automation Scripts` tables first (reuse-before-create) |
| Script is a quality gate | Load `arch-guard` to integrate it into the Quality Gate Commands |
| Multi-scanner initiative | Classify **M/L** per Size Triage; stage per script, inform user if **L** |

---

## How Skills Reference Scripts

Skills reference scripts in their `## Automation Scripts` section:

```markdown
## Automation Scripts

| Script | Purpose | Skill Integration |
|--------|---------|-------------------|
| `tools/scan_violations.py` | C1-C8, D1-D6 checks | arch-guard |
| `tools/scan_class_contracts.py` | Class contract compliance | arch-guard |
```

**Why it matters:** the table is the discoverability layer — an agent hunting for a scanner reads the
table, finds the command, and runs it. A script that exists but appears in no table is invisible to the
Automation-First discipline.

---

## How to Add a New Script

1. Create `tools/scan_{name}.py` following the template (`script-structure.md`)
2. Add an `## Automation Scripts` entry to the relevant skills (above)
3. Test: `python3 tools/scan_{name}.py --module {Module}` (`testing-and-performance.md`)
4. Verify the output schema matches the standard (`output-format.md`)
5. Add to the `tools/README.md` table
6. Commit with message: `chore(scripts): add {name} scan`

**Why this order:** the script is the artifact, but the *integration* is what makes it useful — table
entries in skills and README before merge, schema verified before any consumer depends on it.

---

## Skill → Script → Skill Flow

```
Agent skill detects issue
    ↓
Runs relevant script
    ↓
Script produces JSON findings
    ↓
Skill reads findings
    ↓
Skill produces actionable recommendations
    ↓
User/Agent acts on recommendations
```

**Why it matters:** scripts are the measuring instrument, skills are the interpretation. The flow is
one-directional and repeatable: detect → measure → read → recommend → act. If the skill *also* scans
itself (bypassing the script), the skill's ad-hoc scan and the script drift apart; if the script
*interprets* (recommends beyond its finding), it duplicates skill logic.

**How to apply:** keep scripts producing raw findings; keep interpretation (severity judgment, issue
bodies, prioritization) in the skills (`arch-guard`, `issue-writing`, `spec-audit`).

---

## Verification / Detection

- `grep -l 'scan_' .agents/skills/*/SKILL.md` — confirmed scripts are referenced in skill tables.
- `grep -n 'scan_{name}' tools/README.md` — new scripts appear in README.
- `git diff` of a script change shows only script + integration-table edits, no unrelated files.
- No two scripts in `tools/` target the same rule family (dedup check against `arch-guard`'s
  Automation mapping).
