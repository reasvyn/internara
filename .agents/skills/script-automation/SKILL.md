---
name: script-automation
description: >
    SDLC Phase: TOOLING. Standards and conventions for writing, maintaining, and integrating Python
    devtool scripts in `scripts/`. Defines script interface, output format, error handling, testing,
    and how scripts integrate with agent skills. Reference this skill BEFORE creating or modifying
    any script in `scripts/`.
---

# Script Automation

> **Last updated:** 2026-08-18 **Changes:** slimmed to index form — comprehensive rules (script
> interface, output format, script structure, error handling, testing & performance, agent-skill
> integration) now live in `rules/` and are mapped by the `## Skill Rules` table

Standards for writing, maintaining, and integrating Python devtool scripts in `scripts/`.

## Agent Workflow

This is a TOOLING standards reference — not an implementation flow. Follow the
`agent-workflow` skill for the canonical 5-step pipeline (Understand → Plan → Implement → Verify → Summarize), **Size Triage** and commit format —
this skill adds script-writing standards below — nothing else. When creating or modifying a script,
follow the same decision discipline as other skills:

- **Spec-first:** only add a scanner/script when a governing spec (or a documented automation need)
  justifies it. Never build a script to work around a one-off problem a reusable script already
  covers.
- **Reuse before create:** check `scripts/README.md` and the `## Automation Scripts` tables in other
  skills before writing a new script. If the pattern is covered, use the existing tool.
- **Size-aware:** a multi-scanner initiative is **M/L** per the `agent-workflow` Size Triage — stage
  it per script, and inform the user before committing if it crosses into **L**.
- **Verify:** run `python3 scripts/{name}.py --module {Module} --strict` and confirm the JSON output
  schema before integrating into any skill.
- **Git verify:** before committing a script change, run `git status` + `git diff` to confirm only
  intended files changed and no unrelated edits or lost content (version-control verification).

## Phase Context

| Role           | Skill                                                                       |
| -------------- | --------------------------------------------------------------------------- |
| **Upstream**   | `arch-guard` (verify gates), all skills with `## Automation Scripts` tables |
| **This skill** | **TOOLING** — script standards and conventions                              |
| **Downstream** | `arch-guard`, `context-awareness` (Automation Scripts reference)            |

## Skill Handoffs (Actionable)

| Condition                                 | Action                                                                                       |
| ----------------------------------------- | -------------------------------------------------------------------------------------------- |
| Creating/modifying a script in `scripts/` | Follow this skill's template and interface                                                   |
| A skill needs a new automation            | Check `scripts/README.md` + skill `## Automation Scripts` tables first (reuse-before-create) |
| Script is a quality gate                  | Load `arch-guard` to integrate it into the Quality Gate Commands                             |
| Multi-scanner initiative                  | Classify **M/L** per Size Triage; stage per script, inform user if **L**                     |

## Script Directory Structure

```
scripts/
├── scan_architecture.py      # Component counts, module stats
├── scan_class_contracts.py   # Action/Entity/DTO/Model/Enum contracts
├── scan_conventions.py       # strict_types, Fillable, debug, hardcoded strings
├── scan_dead_code.py         # Unused observers, DTOs, events
├── scan_doc_links.py         # Broken links in docs
├── scan_issues.py            # GitHub issue metrics
├── scan_naming.py            # Naming convention compliance
├── scan_security.py          # XSS, SQLi, mass assignment patterns
├── scan_skills.py            # Agent SKILL.md meta-framework consistency
├── scan_tests.py             # Test pass/fail results
├── scan_violations.py        # C1-C8, D1-D6 violations
├── scan_files.py             # File inventory, LOC counts
├── outputs/                  # .gitignored
│   ├── .gitkeep
│   └── 20260711120000-violations.json
└── README.md                 # Human-readable script guide
```

## Script Interface

Every script MUST follow this interface. Run as `python3 scripts/{script_name}.py [OPTIONS]`.

**Required flags:**

| Flag              | Description                                           | Default                                        |
| ----------------- | ----------------------------------------------------- | ---------------------------------------------- |
| `--module`, `-m`  | Target specific module (e.g., `Student`, `Academics`) | `null` (all)                                   |
| `--output`, `-o`  | Output file path                                      | `scripts/outputs/{timestamp}-{scan_name}.json` |
| `--format`, `-f`  | Output format: `json`, `text`, `summary`              | `json`                                         |
| `--verbose`, `-v` | Include detailed context in findings                  | `false`                                        |
| `--quiet`, `-q`   | Only output summary, no findings                      | `false`                                        |
| `--strict`, `-s`  | Exit with code 1 on any finding                       | `false`                                        |
| `--json`          | Force JSON output to stdout (for piping)              | `false`                                        |

Every script MUST produce JSON with keys `scan_version`, `scan_name`, `scan_type`, `module`,
`timestamp`, `execution_time_ms`, `summary` (`total_checks`/`passed`/`failed`/`by_severity`),
`findings[]` (each with id/rule/severity/category/file/line/message/suggestion/reference/context),
and `metadata`. Full schema, the script template, and finding-construction examples are in
`rules/output-format.md` and `rules/script-structure.md`. When writing a new scanner, copy the
standard template from `rules/script-structure.md` (or scaffold from an existing sibling scanner)
and keep the constants, dataclasses, `build_report`, `write_report`, `parse_args`, `print_summary`,
and `main` shape consistent.

## Skill Rules

| Rule                                                        | Asset                              | Applies when                                                        |
| ----------------------------------------------------------- | ---------------------------------- | ------------------------------------------------------------------- |
| CLI flags, directory layout & output path convention        | `rules/script-interface.md`        | Creating or modifying any script in `scripts/`                      |
| JSON output schema & output quality rules                   | `rules/output-format.md`           | Building the report or validating a script's output                 |
| Script template, scanner functions & finding construction   | `rules/script-structure.md`        | Writing a new scanner or extending an existing script               |
| Error handling — resilience & exit code discipline          | `rules/error-handling.md`          | Any script that iterates over files                                 |
| Script testing & performance guidelines                     | `rules/testing-and-performance.md` | Before integrating a script into a skill, or when a scan slows down |
| Reuse-before-create, handoffs & the Skill→Script→Skill flow | `rules/agent-skill-integration.md` | Deciding to add a scanner, or wiring a script into skills/README    |

## Quick References

| Topic                 | Location                                      |
| --------------------- | --------------------------------------------- |
| Full script guide     | `scripts/README.md`                           |
| Quality gate commands | `arch-guard` SKILL `## Quality Gate Commands` |
| Scan output reports   | `scripts/outputs/` (`.gitignore`d)            |
