---
name: doc-writing
description: SDLC Phase: DOCUMENTATION. Writing and maintaining project documentation — PHPDoc blocks, markdown docs, module conceptual/reference docs, metadata format, cross-references, and the documentation-first (SSOT) principle.
upstream:
  - context-awareness
  - feature-building
  - code-refactoring
  - pest-testing
  - test-writing
downstream:
  - sync-docs
---

# Doc Writing

> **Prerequisite:** Load `context-awareness` for project orientation and documentation map.

## When to Activate

Use this skill when:
- Writing new documentation files (markdown, PHPDoc)
- Updating existing docs to reflect code changes
- Adding or editing PHPDoc blocks on classes and methods
- Syncing documentation with implementation
- Adding module conceptual or reference docs

## Agent Workflow

### 1. Construct — Knowledge, Context & Scope

- Load `context-awareness` skill for project orientation
- Identify which tier the doc belongs to: **conceptual** or **reference**
- Read the existing doc (if updating) or a peer doc of the same type (if creating new)
- Verify code paths, class names, and signatures against actual source — never trust docs blindly
- Determine approach: at least 2 options before deciding

### 2. Execute — Write Documentation

- Follow the two-tier model (conceptual vs reference)
- Follow metadata format on every file
- Follow document structure template
- Apply PHPDoc conventions for PHP files
- Ensure all cross-references resolve to real files
- Output: documentation files matching the conventions below

### 3. Verify — Quality Gates

- All relative links resolve to existing files
- Anchor links (`#section`) match actual section headings
- Metadata block present with current date
- `## Description` section present
- No implementation details in conceptual docs
- No design rationale in reference docs
- PHPStan still passes (if PHPDoc was added): `vendor/bin/phpstan analyse --no-progress`
- Pint still passes: `vendor/bin/pint --dirty --format agent`

### 4. Report & Commit

- Deliver a report to the user:
  - Summary of documentation changes
  - Files created or updated
  - Broken links found and fixed
  - Metadata updated
- Feeds into: `sync-docs` (automated sync verification)
- Commit using format: `docs(scope): description`

---

## 1. Documentation-First (SSOT)

Documentation is the **single source of truth**. When docs and code disagree, docs win. Every change
starts with documentation before code is written. A change is not complete until relevant docs are
updated.

**Implication for agents:** When implementing a feature, write or update the relevant doc FIRST, then
write the code to match.

---

## 1b. Edit, Don't Rewrite

**Always prefer targeted edits over full rewrites.** Full rewrites risk silently dropping details —
naming conventions, edge case notes, cross-references, or nuance that took effort to capture.

| Scenario | Approach |
|----------|----------|
| Update a section | Edit that section only — leave everything else untouched |
| Rename a term across doc | Use `replaceAll` edit — never rewrite the file |
| Add new content | Insert at the right position — don't reconstruct the file |
| Restructure headings | Move sections individually — preserve all content |
| Fix a typo | Edit the line — not the whole paragraph |

**When a full rewrite seems necessary:** read the entire file first, confirm no details will be lost,
then proceed. But ask yourself — can this be done with 2-3 targeted edits instead?

---

## 2. Two-Tier Model

Every module has exactly two documents. The separation is strict.

| Tier | File | Content | Must NOT contain |
|------|------|---------|-----------------|
| **Conceptual** | `docs/modules/{module}.md` | Purpose, design principles, business rules, module boundary | File paths, class names, schemas, Actions tables, Routes tables |
| **Reference** | `docs/modules/{module}-reference.md` | File paths, class names, table schemas, Actions/Routes tables, dependency graphs | Design rationale, "why" explanations |

**When creating or editing a module doc, ask:** "Is this design intent or implementation detail?"
Design intent → conceptual. Implementation detail → reference.

**Non-module docs** (architecture patterns, infrastructure, foundation) follow the same principle:
conceptual docs explain *why*, reference docs explain *what*.

---

## 3. Metadata Format

Every markdown file MUST have a metadata blockquote on line 3 (immediately after the H1 title):

```markdown
# Title — Subtitle

> **Last updated:** YYYY-MM-DD **Changes:** brief description of what changed
```

**Rules:**
- `**Last updated:**` — date in `YYYY-MM-DD` format
- `**Changes:**` — one-line description of the change
- Both fields MUST be updated whenever content changes
- Format: `sync — {description}` for auto-syncs, `feat — {description}` for new content,
  `fix — {description}` for corrections

---

## 4. Document Structure Template

Every markdown doc follows this **minimal** structure:

```markdown
# Title — Subtitle/Scope

> **Last updated:** YYYY-MM-DD **Changes:** description

## Description

{1-3 sentence summary of what this doc covers.}

---

## {Content Heading}

{Body content — explanation, rules, guidelines, etc.}

### {Sub-section}

{Deeper detail under the content heading}

---

## AI Agent Guides  *(optional)*

{Structured, machine-readable instructions optimized for AI agents — checklists,
decision tables, scan commands, quick lookups. Only add when the doc serves as a
reference that agents will consult during tasks.}

---

## Quick References

- `{path}` — {what's there}
- `{path}` — {what's there}
- [Related Doc](relative-path.md) — {why it's relevant}
```

**Structure breakdown:**

| Level | Element | Purpose |
|-------|---------|---------|
| H1 | `# Title` | Document identity — one per file, always first |
| H2 | `## Description` | What this doc covers — mandatory on every doc |
| H2 | `## {Content}` | Main body — as many H2 sections as needed |
| H3 | `### {Sub-section}` | Deeper detail under a content H2 |
| H2 | `## AI Agent Guides` | Optional — machine-readable instructions for AI agents |
| H2 | `## Quick References` | Links to related files, always last section |

### AI Agent Guides Rules

This section is **optional**. Add it only when the document is a reference that AI agents
will consult during coding tasks.

**What goes here:**
- Checklists agents can step through
- Decision tables (if X → do Y)
- Scan/verification commands
- Quick-lookup tables (invariant → file path, rule → line number)
- Anti-pattern → fix mapping

**What does NOT go here:**
- Explanations (those belong in the content body)
- Design rationale (that's for humans)
- Full code examples (link to source instead)

**Format principles:**
- Prefer tables over prose — agents parse tables faster
- Prefer concrete over abstract — `python3 scripts/scan_violations.py` not "run the scan"
- Prefer flat over nested — avoid deep heading trees inside this section
- Every entry should be actionable without reading surrounding context

**Rules:**
- H1 title: `# Subject — Subtitle` format, exactly one per file
- `## Description` is always the first H2 after metadata
- Content sections (`##`) are topical — name them after what they explain
- `###` subsections group related detail under a content H2
- `## Quick References` is the standard footer (not `## References`, not `## Where to Find It`)
- `---` horizontal rules separate major H2 sections
- Never skip heading levels: H1 → H2 → H3 (no H4 unless truly necessary)

---

## 5. PHPDoc Conventions

The project uses PHP 8.4 native type hints as the **primary** documentation mechanism. PHPDoc
**supplements** native types — it does not duplicate them.

### When to Use PHPDoc

| Situation | Required? | Tags |
|-----------|-----------|------|
| Action class | Yes | `@throws RejectedException` (list all business rule exceptions) |
| Entity business methods | Recommended | Brief description of the business question |
| Complex algorithm | Yes | Multi-line description of the approach |
| Non-obvious side effect | Yes | `@see` pointing to the listener/event |
| Bridge method (`as*Entity`) | Yes | `@see \App\{Module}\Entities\{Entity}` |
| Simple getter/property access | No | Native types are sufficient |

### When NOT to Use PHPDoc

- **Never** `@author`, `@version`, `@created`, `@package` — metadata lives in git
- **Never** duplicate what native type hints already express (`@param string $name` when the
  signature is `string $name`)
- **Never** use PHPDoc as a substitute for proper typing

### Format Rules

```php
/**
 * Brief one-line description for simple methods.
 */
public function execute(): ActionResponse
{
    // ...
}

/**
 * Multi-line description for complex methods.
 *
 * Explains the business context, side effects, or non-obvious behavior.
 * Use blank line between description and tags.
 *
 * @throws RejectedException when the record is in a terminal state
 * @throws RejectedException when a duplicate exists
 */
public function execute(CreateUserData $data): ActionResponse
{
    // ...
}
```

**Rules:**
- One-line for simple methods, multi-line for complex
- No blank line between description and first tag in multi-line blocks
- `@throws` lists specific exception types, not generic `Exception`
- `@see` for cross-references to related classes
- No `@param` / `@return` when native types are present

---

## 6. Section Naming Conventions

| Purpose | Correct Name | Wrong Names |
|---------|-------------|-------------|
| File/code location pointers | `## Quick References` | `## References`, `## See Also`, `## Resources`, `## Where to Find It` |
| Module overview | `## Description` | `## Summary`, `## Overview` |
| Behavior explanation | `## How It Works` | `## Implementation`, `## Details` |

---

## 7. Link Integrity

### Relative Links

All internal links use relative paths from the current file's location:

```markdown
[Media Library](media-library.md)           # same directory
[Action Pattern](../architecture/action-pattern.md)  # up one, then down
[User Module](modules/user.md)              # from docs/index.md
```

### Anchor Links

Anchor links match the exact section heading (lowercased, spaces → hyphens):

```markdown
[Token Security](setup.md#token-security)  # matches ## Token Security
```

### Verification

Before committing doc changes, verify:
1. Every `[text](path)` resolves to an existing file
2. Every `[text](path#anchor)` matches an existing heading
3. No content is duplicated — use cross-references instead

---

## 8. Content Duplication Rule

**Never duplicate content across docs.** If two docs need the same information:
- Keep it in the **authoritative** location
- Cross-reference from the other doc with a relative link

**Examples:**
- S3 configuration → authoritative in `filesystem.md`, `media-library.md` references it
- Testing conventions → authoritative in `docs/architecture/testing-pattern.md`, skills
  reference it
- Module overview → authoritative in `docs/modules/{module}.md`, reference doc links to it

---

## Phase Context

| Role | Skill |
|------|-------|
| **Upstream** | `feature-building` (new code needs docs), `code-refactoring` (changed code needs doc updates), `pest-testing` / `test-writing` (test docs) |
| **This skill** | **DOCUMENTATION** — writes and maintains all documentation |
| **Downstream** | `sync-docs` (automated sync verification) |

## Automation Scripts

| Script | What it does | Command |
|--------|-------------|---------|
| `scan_doc_links.py` | Validate all relative links in markdown files | `python3 scripts/scan_doc_links.py` |

Output: `scripts/outputs/{timestamp}-doc-links.json`.

## Quick References

| Topic | Location |
|-------|----------|
| Full conventions | `docs/conventions.md` |
| Architecture overview | `docs/architecture.md` |
| Module index | `docs/modules/index.md` |
| Pattern deep-dives | `docs/architecture/{pattern}-pattern.md` |
| Sync-docs workflow | `.agents/skills/sync-docs/SKILL.md` |
| Sync verification rules | `.agents/skills/sync-docs/rules/sync-verification.md` |
| Documentation map | `docs/index.md` |
