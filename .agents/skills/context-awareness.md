# Context Awareness — Pause, Observe, Then Act

Skill files describe *intent and patterns*, but the codebase evolves independently.
Numbers, paths, names, and signatures written in a skill may be stale by the time you
read them. **Your best guide is the actual code.**

## Before You Follow Any Instruction

Pause and ask these questions first:

**Does the file path still exist?** Classes get moved between modules, renamed,
or split into smaller files. Run `ls`, `find`, or `glob` — don't trust a path
written in a skill.

**Is that count still accurate?** "There are N models/actions/events/tests" is
always a snapshot in time. Count them yourself with `grep -c`, `find | wc -l`,
or by running the test suite.

**Does that class/method still have the same signature?** Constructor parameters
change, return types narrow, method names shift. Read the actual declaration.

**Has the schema changed?** Columns get renamed, tables get merged, enums lose
or gain cases. Read the migration files, not the skill's description.

**Is this behavior still true?** Business rules evolve. What the skill says
about roles, statuses, or authorization may have been intentionally changed.

## What to Verify vs What to Trust

| You can trust (mostly stable) | But still verify (frequently changes) |
|-------------------------------|---------------------------------------|
| Architecture principles (4 layers, Action Triad, DTO boundaries) | File paths and class locations |
| Coding conventions (strict_types, Fillable, naming) | Exact class/method signatures |
| Base class hierarchy (BaseAction, BaseEntity, etc.) | Enum cases and their values |
| Exception hierarchy (AppException vs ModuleException) | Route definitions and names |
| Contract interfaces (LabelEnum, StatusEnum) | Database column names and types |
| Module structure (app/{Module}/**/) | Test counts and coverage percentages |
| | Factory definitions and relationships |
| | Config keys and cache key names |
| | Listener registrations in config/event.php |

## What This Covers

The same caution applies across ALL areas of documentation:

- **Architecture docs** (`docs/architecture*.md`) — layer diagrams show intent,
  not necessarily current reality. Verify dependency direction by reading code.
- **Module references** (`docs/modules/*.md`) — action/model/policy listings
  may be incomplete. grep for actual files.
- **Pattern docs** (`docs/architecture/*-pattern.md`) — code examples in
  pattern docs are illustrative. Read the actual base classes for truth.
- **Infrastructure docs** (`docs/infrastructure/*.md`) — config keys,
  environment variables, and driver settings may have changed.
- **ADRs** (`docs/adr/*.md`) — decisions may have been revisited. Check for
  superseding ADRs.
- **Skill files** (`.agents/skills/*/SKILL.md`) — this file itself included.
  Everything here is advisory, not authoritative.
- **AGENTS.md** — quick-reference rules summarize conventions but may omit
  edge cases or recent changes.

## When Skill and Reality Differ

The skill describes what the author thought was true. The code is what is
actually true. When they disagree:

1. **Follow the code** — it is the source of truth
2. **Update the skill** — add today's date to `> **Last updated:**` and
   note the discrepancy in `> **Changes:**`
3. **Adapt the pattern** — if the skill suggests an approach that no longer
   fits the codebase structure, adapt the approach to what actually exists

## In Short

> Read the skill. Then read the code. If they disagree, the code wins.
> Verify paths, counts, names, and signatures before acting on them.
> This applies to every document — architecture, modules, patterns,
> infrastructure, ADRs, skills, and AGENTS.md alike.
