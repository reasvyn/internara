# Documentation Template & Standards — How We Write Docs

> **Last updated:** 2026-08-25 **Changes:** feat — initial template based on the Diátaxis framework, mapped onto Internara's doc tree with per-type skeletons

## Description

The working standard for every document in `docs/`: which of the four documentation types a piece
of content belongs to, where it lives, how it is structured, and how it stays alive as the project
evolves. Full rule prose lives in [`conventions.md`](conventions.md) §0 and the
`doc-writing` skill — this file is the practical map and the copy-paste skeletons.

---

## The Four Types of Documentation

Internara follows [Diátaxis](https://diataxis.fr) — the industry-standard documentation framework
(used by Django, Gatsby, Canonical, Cloudflare). Every document serves exactly **one** of four
user needs. Mixing types in one document is the root cause of most documentation noise.

| Type | Answers | Oriented to | Form | Tone |
|------|---------|-------------|------|------|
| **Tutorial** | "Can you teach me to…?" | Learning | A lesson — safe, repeatable, guaranteed result | Encouraging mentor |
| **How-to guide** | "How do I…?" | Goals | A recipe — steps for a specific task | Direct instructions |
| **Reference** | "What is…?" | Information | Dry, complete, precise description | Neutral encyclopedia |
| **Explanation** | "Why…?" | Understanding | Discursive discussion — design rationale, trade-offs | Reflective essay |

### Mapping to Internara's Doc Tree

| Quadrant | Location in this repo |
|----------|----------------------|
| Tutorial | [`getting-started.md`](getting-started.md), contributor onboarding in `CONTRIBUTING.md` |
| How-to guides | [`foundation/`](foundation/index.md) (install, setup wizard, upgrade, backup), ops guides in [`infrastructure/`](infrastructure/index.md) |
| Reference | [`modules/{module}-reference.md`](modules/index.md) (API reference), [`infrastructure/`](infrastructure/index.md) technical topics (queue, cache, routes) |
| Explanation | [`architecture.md`](architecture.md) + [`architecture/*-pattern.md`](architecture/index.md), [`philosophy.md`](philosophy.md), [`project-vision.md`](project-vision.md), [`adr/`](adr/index.md), conceptual [`modules/{module}.md`](modules/index.md) |

Feature specs ([`specs/`](specs/index.md)) are the requirements SSOT — they feed all four
quadrants but are not themselves user documentation.

### Where Does My Content Belong?

| I need to write… | It belongs in | Type |
|------------------|---------------|------|
| A new contributor's first successful run | `getting-started.md` | Tutorial |
| Steps to perform an operation (deploy, restore, recover) | `foundation/{operation}.md` | How-to |
| Complete list of Actions/Routes/Models/Policies of a module | `modules/{module}-reference.md` | Reference |
| What a module does, its boundary, design principles | `modules/{module}.md` | Explanation |
| Why a pattern exists and when to apply it | `architecture/{pattern}-pattern.md` | Explanation |
| A recorded architectural decision with context | `adr/{NNN}-{title}.md` | Explanation |
| Environment variables, config options | `infrastructure/configuration.md` | Reference |

---

## Writing Principles — Low Noise by Default

1. **One document, one job.** If you are writing both steps and rationale, split into a how-to and
   an explanation that link to each other.
2. **Audience first.** Name who the doc serves (new contributor? operator? reviewer?) before
   writing a word — it decides vocabulary, depth, and what to leave out.
3. **Progressive disclosure.** One-line summary → essentials → depth via links. Never front-load
   everything a reader might someday need.
4. **Link, don't restate.** Every fact lives in exactly one place; every other mention links to
   it. Restated facts drift independently and become contradictions.
5. **Show, don't tell.** A runnable command beats three paragraphs describing it. Use real paths,
   real class names, verified commands.
6. **Tables for enumerable facts, prose for reasoning.** Readers scan tables; they read prose only
   when they need the "why".
7. **Cut filler.** No marketing tone, no "simply/obviously/just", no restating the title as the
   first sentence, no empty introductions or summary paragraphs.
8. **English only** in code and docs; Indonesian only in `lang/id/`.

---

## Metadata Contract

Every markdown file carries a self-describing header so freshness is visible and scanners can
enforce it (`scan_doc_links.py` flags missing dates and anything untouched for 14+ days):

```markdown
# Title — Subtitle

> **Last updated:** YYYY-MM-DD **Changes:** one-line description of the latest change
```

- Line 3, immediately after the H1 — nowhere else.
- `Changes:` holds **only the latest change** — history belongs in git, not in the file.
- Update both fields whenever content changes; prefix with `sync —` / `feat —` / `fix —` where apt.
- Structure below the header is fixed: `## Description` is always the first H2,
  `## Quick References` is always the last. Footer is named `Quick References` — never
  "References", "See Also", or "Where to Find It".

---

## Structural Skeletons

All skeletons below already satisfy the metadata contract — copy, fill, delete what does not apply.

### Conceptual Doc — `docs/modules/{module}.md`

Pure design intent. No file paths, no class names, no schemas.

```markdown
# {Module} — {One-line Purpose}

> **Last updated:** YYYY-MM-DD **Changes:** {change}

## Description

{1–3 sentences: what this module owns and why it exists.}

## Boundary

{What this module handles / explicitly does not handle.}

## Key Concepts

{Domain terms and business rules a newcomer must know.}

## Design Principles

{Decisions shaping the module, each with a one-line rationale.}

## How It Works

{Conceptual flow — Livewire → Action → Entity → Model narrative, no class names.}

## Quick References

- `modules/{module}-reference.md` — full API reference (markdown link in the real doc)
- `../specs/{ID}-{feature}.md` — governing spec
```

### Reference Doc — `docs/modules/{module}-reference.md`

Complete, dry, factual. No design rationale here.

```markdown
# {Module} Reference — API & Structure

> **Last updated:** YYYY-MM-DD **Changes:** {change}

## Description

Complete API reference for the {Module} module: Models, Actions, Routes, Policies, Livewire,
Events. Design rationale lives in `{module}.md`.

## Models

| Model | Table | Purpose |
|-------|-------|---------|

## Actions

| Action | Type | Signature | Notes |
|--------|------|-----------|-------|

## Routes

| Method | URI | Name | Middleware |
|--------|-----|------|------------|

## Policies & Permissions

| Permission | Role(s) | Gate |
|------------|---------|------|

## Events

| Event | Fired by | Listeners |
|-------|----------|-----------|

## Quick References

- `modules/{module}.md` — conceptual overview (markdown link in the real doc)
```

### Architecture Pattern Doc — `docs/architecture/{pattern}-pattern.md`

Explanation-first: why the pattern exists, then how to apply it correctly.

```markdown
# {Pattern} Pattern

> **Last updated:** YYYY-MM-DD **Changes:** {change}

## Description

{What problem this pattern solves and when it applies.}

## Non-Negotiable

{Hard rules. Violations are architecture violations.}

## How to Apply

{Concrete application with a minimal example.}

## Anti-Patterns

| You see… | It should be… | Violation |
|----------|---------------|-----------|

## Quick References

- `../conventions.md` — related invariants (markdown link in the real doc)
```

### Operational Guide — `docs/foundation/{operation}.md` (How-to)

Goal-oriented steps for operators. Assumed knowledge goes in prerequisites, not in the body.

```markdown
# {Operation}

> **Last updated:** YYYY-MM-DD **Changes:** {change}

## Description

{What this achieves and when an operator needs it.}

## Prerequisites

{Access, versions, prior steps.}

## Steps

1. {Action with exact command}
2. …

## Verification

{How to confirm success — expected output, health check.}

## Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|

## Quick References

- `other-operation.md` — related operation (markdown link in the real doc)
```

---

## Keeping Docs Alive — Adaptivity

Documentation rots silently unless decay is made visible and repair is routine:

- **Document first.** Behavior changes start in docs/specs, then code follows — never the reverse
  ([conventions.md §0](conventions.md)).
- **Docs ship with code.** A PR that changes behavior without updating affected docs is incomplete
  and must not merge. Update the metadata line in the same commit.
- **Freshness is enforced, not hoped for.** `scan_doc_links.py` fails on broken links, missing
  metadata, and documents untouched beyond 14 days.
- **Delete rather than let lie.** Outdated content is removed or rewritten — never annotated with
  "may be outdated". Git history preserves everything.
- **Audit periodically.** Run the `sync-docs` skill or verify manually after batch changes:

```bash
python3 scripts/scan_doc_links.py   # Links resolve, metadata present & fresh
```

---

## AI Agent Guides

| When the agent… | It must… |
|-----------------|----------|
| Creates any markdown file in `docs/` | Copy the matching skeleton above; metadata on line 3; `Description` first H2; `Quick References` last |
| Cannot classify content into one quadrant | Split it — mixed-type docs are rejected |
| Restates a fact documented elsewhere | Link to the SSOT instead |
| Edits doc content | Bump `Last updated` + rewrite `Changes` (latest change only) |
| Finishes doc work | Run `python3 scripts/scan_doc_links.py` — zero findings required |

---

## Quick References

- [`conventions.md`](conventions.md) — §0 Documentation-First (authoritative rules)
- [`index.md`](index.md) — full documentation catalog and reading order
- [`../CONTRIBUTING.md`](../CONTRIBUTING.md) — contribution workflow and quality gates
- [Diátaxis](https://diataxis.fr) — the framework behind the four-quadrant model
