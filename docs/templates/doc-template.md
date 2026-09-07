# Documentation Template & Standards — How We Write Docs

## Description

The working standard for every document in `docs/`: which of the four documentation types a piece
of content belongs to, where it lives, how it is written, and how it stays alive as the project
evolves. Copy-paste skeletons live as `{type}-template.md` files in their target directories.
Full rule prose lives in [`conventions.md`](conventions.md) §0 and the `doc-writing` skill.

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
| How-to guides | [`guides/`](guides/index.md) (install, setup wizard, upgrade, backup), ops guides in [`guides/infra/`](guides/infra/index.md) |
| Reference | [`refs/modules/{module}-reference.md`](refs/modules/index.md) (API reference), [`guides/infra/`](guides/infra/index.md) technical topics (queue, cache, routes) |
| Explanation | [`architecture.md`](architecture.md) + [`guides/arch/*-pattern.md`](guides/arch/index.md), [`philosophy.md`](philosophy.md), [`project-vision.md`](project-vision.md), [`adr/`](adr/index.md), conceptual [`refs/modules/{module}.md`](refs/modules/index.md) |

Feature specs ([`specs/`](specs/index.md)) are the requirements SSOT — they feed all four
quadrants but are not themselves user documentation.

### Where Does My Content Belong?

| I need to write… | It belongs in | Type | Skeleton |
|------------------|---------------|------|----------|
| A new contributor's first successful run | `getting-started.md` | Tutorial | — |
| Steps to perform an operation (deploy, restore, recover) | `guides/{operation}.md` | How-to | [`guides/guide-template.md`](guides/guide-template.md) |
| Complete list of Actions/Routes/Models/Policies of a module | `modules/{module}-reference.md` | Reference | [`modules/module-reference-template.md`](refs/modules/module-reference-template.md) |
| What a module does, its boundary, design principles | `modules/{module}.md` | Explanation | [`modules/module-template.md`](refs/modules/module-template.md) |
| Why a pattern exists and when to apply it | `guides/arch/{pattern}-pattern.md` | Explanation | [`guides/arch/pattern-template.md`](guides/arch/pattern-template.md) |
| Feature requirements with traceable IDs | `specs/{ID}-{feature}.md` | Spec (SSOT) | [`specs/spec-template.md`](specs/spec-template.md) |
| Environment variables, config options | `guides/infra/configuration.md` | Reference | — |
| A recorded architectural decision with context | `adr/{NNN}-{title}.md` | Explanation | follow existing ADR format |

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

## Document Structure Contract

No inline `Last updated` metadata in markdown files. History lives in git:

```bash
git log --follow -- <file>      # history of a doc
git diff -- <file>              # what changed in this branch
```

Structure below the title is fixed: `## Description` is always the first H2,
`## Quick References` is always the last. Footer is named `Quick References` — never
"References", "See Also", or "Where to Find It".

---

## Per-Directory Templates

Copy-paste skeletons live next to the docs they produce. Every directory in `docs/` has its own
`index.md` catalog, and each recurring document type has a template:

| Template | Produces |
|----------|----------|
| [`specs/spec-template.md`](specs/spec-template.md) | Feature spec — fixed 11-section structure with requirement IDs |
| [`refs/modules/module-template.md`](refs/modules/module-template.md) | Conceptual module overview (`{module}.md`) |
| [`refs/modules/module-reference-template.md`](refs/modules/module-reference-template.md) | Module API reference (`{module}-reference.md`) |
| [`refs/deps/dep-template.md`](refs/deps/dep-template.md) | Dependency reference (`deps/{package}.md`) |
| [`guides/arch/pattern-template.md`](guides/arch/pattern-template.md) | Architecture pattern doc (`{pattern}-pattern.md`) |
| [`guides/guide-template.md`](guides/guide-template.md) | Operational how-to guide |
| [`adr/adr-template.md`](adr/adr-template.md) | Architecture decision record (`adr-{slug}.md`) |

Every template already satisfies the structure contract above — copy, fill, delete what does not
apply.

---

## Keeping Docs Alive — Adaptivity

Documentation rots silently unless decay is made visible and repair is routine:

- **Document first.** Behavior changes start in docs/specs, then code follows — never the reverse
  ([conventions.md §0](conventions.md)).
- **Docs ship with code.** A PR that changes behavior without updating affected docs is incomplete
  and must not merge.
- **Freshness via git.** `scan_doc_links.py` validates links (file, anchor, external); age is checked via `git log`, not inline dates.
- **Delete rather than let lie.** Outdated content is removed or rewritten — never annotated with
  "may be outdated". Git history preserves everything.
- **Audit periodically.** Run the `sync-docs` skill or verify manually after batch changes:

```bash
python3 tools/scan_doc_links/cli.py   # Links resolve (freshness via git log)
```

---

## AI Agent Guides

| When the agent… | It must… |
|-----------------|----------|
| Creates any markdown file in `docs/` | Copy the matching per-directory template; `Description` first H2; `Quick References` last |
| Cannot classify content into one quadrant | Split it — mixed-type docs are rejected |
| Restates a fact documented elsewhere | Link to the SSOT instead |
| Edits doc content | Write a descriptive commit message; history via `git log --follow -- <file>` |
| Finishes doc work | Run `python3 tools/scan_doc_links/cli.py` — zero findings required |

---

## Quick References

- [`conventions.md`](conventions.md) — §0 Documentation-First (authoritative rules)
- [`index.md`](index.md) — full documentation catalog and reading order
- [`../CONTRIBUTING.md`](../CONTRIBUTING.md) — contribution workflow and quality gates
- [Diátaxis](https://diataxis.fr) — the framework behind the four-quadrant model
