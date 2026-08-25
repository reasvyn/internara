# Pre-existing Defects — Fix or File

> **Last updated:** 2026-08-25 **Changes:** extracted from AGENTS.md into .agents/rules/ (AGENTS.md becomes navigation hub)

## Description

The agent must not leave pre-existing warnings and errors untouched: fix them, file them as a
GitHub issue, or explicitly report them as deferred — never silently tolerate.

---

**The agent must not leave pre-existing warnings and errors untouched.**

- **Fix by default, after the main work:** once the instruction's primary work is complete and
  verified, fix pre-existing warnings and errors the agent noticed along the way (lint, PHPStan,
  tests, arch-guard scans, deprecations, broken doc links). Do this before the final commit so the
  repository is left cleaner than found.
- **Fix only what is safe and in-scope-adjacent:** small, low-risk fixes (missing strict types,
  unused imports, dead doc references, obvious typos) are applied directly without asking. Anything
  that changes behavior, requires a design decision, or touches a spec needs the user informed
  first ([Clean Code & Dedup-Align Doctrine](clean-code-dedup-align.md)).
- **Cannot fix? File an issue immediately:** if fixing requires design decisions, significant
  effort, or is out of the current change surface, **create a GitHub issue first** (using the
  `issue-writing` skill) before ending the session — never let a noticed defect go unrecorded.
- **A defect noticed is a defect tracked:** no silent tolerance. Every pre-existing warn/error
  either gets fixed, gets a GitHub issue, or is explicitly reported to the user as deferred with
  the reason.
