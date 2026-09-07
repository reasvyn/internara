# Documentation Template & Standards — How We Write Docs

## Description

The working standard for every document in `docs/`: which of the four documentation types a piece
of content belongs to, where it lives, how it is written, and how it stays alive as the project
evolves. Copy-paste skeletons live as `{type}-template.md` files in `docs/templates/` (see
[Documentation Templates](#documentation-templates)). This template is the master standard; each
type-specific template owns the skeleton **and** the rules that govern that document type.

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
| Tutorial | [`getting-started.md`](../getting-started.md), contributor onboarding in `CONTRIBUTING.md` |
| How-to guides | [`guides/`](../guides/index.md) (install, setup wizard, upgrade, backup), ops guides in [`guides/infra/`](../guides/infra/index.md) |
| Reference | [`refs/modules/{module}-reference.md`](../refs/modules/index.md) (API reference), [`guides/infra/`](../guides/infra/index.md) technical topics (queue, cache, routes) |
| Explanation | [`architecture.md`](../architecture.md) + [`guides/arch/*-pattern.md`](../guides/arch/index.md), [`philosophy.md`](../philosophy.md), [`project-vision.md`](../project-vision.md), [`adr/`](../adr/index.md), conceptual [`refs/modules/{module}.md`](../refs/modules/index.md) |

Feature specs ([`specs/`](../specs/index.md)) are the requirements SSOT — they feed all four
quadrants but are not themselves user documentation.

### Where Does My Content Belong?

| I need to write… | It belongs in | Type | Skeleton |
|------------------|---------------|------|----------|
| A new contributor's first successful run | `getting-started.md` | Tutorial | — |
| Steps to perform an operation (deploy, restore, recover) | `guides/{operation}.md` | How-to | [`guide-template.md`](guide-template.md) |
| Complete list of Actions/Routes/Models/Policies of a module | `refs/modules/{module}-reference.md` | Reference | [`module-reference-template.md`](module-reference-template.md) |
| What a module does, its boundary, design principles | `refs/modules/{module}.md` | Explanation | [`module-template.md`](module-template.md) |
| Why a pattern exists and when to apply it | `guides/arch/{pattern}-pattern.md` | Explanation | [`pattern-template.md`](pattern-template.md) |
| Feature requirements with traceable IDs | `specs/{ID}-{feature}.md` | Spec (SSOT) | [`spec-template.md`](spec-template.md) |
| Environment variables, config options | `guides/infra/configuration.md` | Reference | — |
| A recorded architectural decision with context | `adr/adr-{slug}.md` | Explanation | [`adr-template.md`](adr-template.md) |
| A per-dependency reference | `refs/deps/{package}.md` | Reference | [`dep-template.md`](dep-template.md) |

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

## Document Quality Gate

Every doc change (new, edited, or rewritten) must pass this combined gate before commit. A failure
in any area blocks the commit:

1. **Structural completeness** — `# Subject — Subtitle` H1; no inline metadata block (history via
   `git log --follow`); `## Description` first H2 (1–3 sentences); `## Quick References` last;
   `---` horizontal rules between major sections; correct heading hierarchy.
2. **Link integrity** — all `[text](path)` resolve to existing files; `[text](path#anchor)` match
   existing headings; relative paths only; no orphaned references; no duplicated content (link to
   the one authoritative location instead).
3. **Two-tier separation** — conceptual docs contain no implementation details (file paths, class
   names, schemas, Actions/Routes tables); reference docs contain no design rationale.
4. **Content quality** — footer named `Quick References` (never "References"/"See Also"); code
   fences use correct syntax highlighting; tables aligned; English only (Indonesian only in
   `lang/id/`).

**Pitfall — "the scanner passed":** `scan_doc_links.py` validates links and metadata only. It does
not check tier separation, section names, or content duplication. A clean scanner is necessary,
not sufficient — run the manual checks above too.

## Documentation Templates

Copy-paste skeletons live in `docs/templates/` (centralized — one home instead of scattered in each
target directory). Every directory in `docs/` still has its own `index.md` catalog, and each
recurring document type has a template:

| Template | Produces |
|----------|----------|
| [`spec-template.md`](spec-template.md) | Feature spec — fixed 11-section structure with requirement IDs |
| [`module-template.md`](module-template.md) | Conceptual module overview (`refs/modules/{module}.md`) |
| [`module-reference-template.md`](module-reference-template.md) | Module API reference (`refs/modules/{module}-reference.md`) |
| [`dep-template.md`](dep-template.md) | Dependency reference (`refs/deps/{package}.md`) |
| [`pattern-template.md`](pattern-template.md) | Architecture pattern doc (`guides/arch/{pattern}-pattern.md`) |
| [`guide-template.md`](guide-template.md) | Operational how-to guide (`guides/`) |
| [`adr-template.md`](adr-template.md) | Architecture decision record (`adr/adr-{slug}.md`) |
| [`index-template.md`](index-template.md) | Doc index / catalog (`{dir}/index.md`) |
| [`issue-template.md`](issue-template.md) | GitHub issue |

Every template already satisfies the structure contract above — copy, fill, delete what does not
apply.

---

## Keeping Docs Alive — Adaptivity

Documentation rots silently unless decay is made visible and repair is routine:

- **Document first.** Behavior changes start in docs/specs, then code follows — never the reverse
  ([conventions.md](../conventions.md)).
- **Docs ship with code.** A PR that changes behavior without updating affected docs is incomplete
  and must not merge.
- **Freshness via git.** `scan_doc_links.py` validates links (file, anchor, external); age is checked via `git log`, not inline dates.
- **Delete rather than let lie.** Outdated content is removed or rewritten — never annotated with
  "may be outdated". Git history preserves everything.
- **Audit periodically.** Verify after batch changes:

```bash
python3 tools/scan_doc_links.py   # Links resolve (freshness via git log)
```

---

## AI Agent Guides

| When the agent… | It must… |
|-----------------|----------|
| Creates any markdown file in `docs/` | Copy the matching template from `docs/templates/`; `Description` first H2; `Quick References` last |
| Cannot classify content into one quadrant | Split it — mixed-type docs are rejected |
| Restates a fact documented elsewhere | Link to the SSOT instead |
| Edits doc content | Write a descriptive commit message; history via `git log --follow -- <file>` |
| Finishes doc work | Run `python3 tools/scan_doc_links.py` — zero findings required |

---

## Quick References

- [`../conventions.md`](../conventions.md) — Documentation-First and additional invariants
- [`../index.md`](../index.md) — full documentation catalog and reading order
- [`../../CONTRIBUTING.md`](../../CONTRIBUTING.md) — contribution workflow and quality gates
- [Diátaxis](https://diataxis.fr) — the framework behind the four-quadrant model
