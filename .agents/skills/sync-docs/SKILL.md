---
name: sync-docs
description: Synchronize ALL markdown documentation across the entire repository against actual implementation. Covers docs/, README.md, AGENTS.md, GEMINI.md, .agents/, and every other .md file. Focuses on content accuracy, terminology consistency, structural integrity, and cross-document coherence — NOT on chasing easily-stale counts or lists.
---

# Documentation Sync Skill

## When to Activate

Apply this skill when asked to synchronize, update, refresh, or align documentation with implementation. Covers **every `.md` file in the entire repository** without exception.

## Core Principle

**`docs/` is the Single Source of Truth (SSOT).** All other markdown files (AGENTS.md, GEMINI.md, README.md, .agents/skills/) are **derivative documents** — they summarize, excerpt, or reference `docs/`. The sync must verify these derivatives against the SSOT, never the reverse.

**Do NOT trust AGENTS.md, GEMINI.md, or similar quick-reference files.** These are frequently the most outdated files in the repository — they copy rules from `docs/` and are rarely updated when `docs/` changes. Treat them as suspect and verify everything they claim against `docs/` and actual code.

## What NOT to Do

- ❌ Do NOT chase exact counts (models, actions, policies, migrations, enums — these change constantly)
- ❌ Do NOT copy-paste a claim from AGENTS.md into another doc without verifying it against `docs/`
- ❌ Do NOT assume AGENTS.md/GEMINI.md are correct just because they exist

## Workflow

### Step 1 — Discover All Markdown Files

```bash
find . -name "*.md" -not -path "./node_modules/*" -not -path "./vendor/*" -not -path "./.git/*" -not -path "./storage/*" | sort
```

Group results into two categories:

| Category | Scope | Example files |
|----------|-------|---------------|
| **SSOT** | `docs/` | `docs/architecture.md`, `docs/conventions.md`, `docs/modules/*.md` |
| **Derivative** | Root + config | `README.md`, `AGENTS.md`, `GEMINI.md`, `.agents/skills/*/SKILL.md` |

### Step 2 — Load Reference Context

Read these authoritative SSOT references FIRST:
- `docs/architecture.md` — architecture patterns, framework stack
- `docs/conventions.md` — all conventions
- `docs/modules/module-index.md` — module listing, dependency map
- `docs/doc-index.md` — documentation catalog

### Step 3 — Audit Derivative Documents Against SSOT

This is the **most important step**. Derivative documents (AGENTS.md, GEMINI.md, README.md) are the most likely to be stale.

#### 3.1 AGENTS.md Audit

For **every** claim in AGENTS.md, verify against `docs/`:

| AGENTS.md claim | Verify against |
|-----------------|----------------|
| Stack version (PHP 8.4, Laravel v13, Livewire v4, Boost v2) | `composer.json` + `docs/architecture.md` Layer 1 |
| Skill list (12 skills listed) | `ls .agents/skills/` — must match exactly |
| Module invariants (super admin name/username) | `config/setup.php` + `docs/architecture.md` §Module Invariants |
| Quick-reference rules (`declare(strict_types=1)`, `foreignUuid()->constrained()`, etc.) | `docs/conventions.md` §2 and §5 |
| Boost tools listed | `boost.json` + actual Boost version |
| PHP essentials | `docs/conventions.md` §2 General PHP |
| Deployment essentials | `docs/infrastructure/deployment.md` |
| Testing essentials | `docs/infrastructure/testing.md` |

**If AGENTS.md and `docs/conventions.md` disagree, `docs/conventions.md` wins.** Update AGENTS.md to match.

#### 3.2 GEMINI.md Audit

Same as AGENTS.md — this is a Gemini-specific mirror. Verify:
- Stack version, invariants, quick-reference rules, deployment essentials, testing essentials all match `docs/`
- GEMINI.md must not contain rules that contradict AGENTS.md or `docs/`

#### 3.3 README.md Audit

| Claim | Verify against |
|-------|----------------|
| Project description | `docs/foundation/product-definition.md` |
| Module listing (19 modules) | `ls -d app/*/` — must match actual directories |
| Tech stack table | `composer.json` + `package.json` for actual versions |
| Prerequisites | `docs/getting-started.md` §Prerequisites |
| Quick start commands | Run each command mentally — `composer install`, `npm install`, `cp .env.example .env`, `php artisan key:generate`, `php artisan setup:install`, `composer run dev` |
| Documentation links | Every `docs/` link must point to an existing file |

#### 3.4 `.agents/skills/*/SKILL.md` Audit

| Claim | Verify against |
|-------|----------------|
| All file paths referenced | Must resolve to real files |
| Base class signatures | Must match actual `app/Core/` base classes |
| Command examples | Must reference real Actions, Entities, or Components |
| Workflow steps | Must be actionable with existing tools and skills |

### Step 4 — Audit SSOT (`docs/`) for Content Quality

#### 4.1 Structural Integrity (HIGH)

| Check | What to verify |
|-------|---------------|
| **Broken links** | Every `[text](path)` must resolve to an existing file or valid URL |
| **Orphan files** | Every `.md` file should be referenced somewhere in `doc-index.md` or `README.md` |
| **File path accuracy** | Every documented class/config/view path must point to a real file |

#### 4.2 Terminology Consistency (HIGH)

Same concept must use the same name across **every** markdown file:

| Canonical term (from `docs/architecture.md`) | Watch out for |
|----------------------------------------------|---------------|
| `Action Triad` | Not "Action pattern" vs "Triad pattern" |
| `Command Action` / `BaseCommandAction` | Not generic "action" |
| `Read Action` / `BaseReadAction` | Not "query action" |
| `Process Action` / `BaseProcessAction` | Not "orchestration action" |
| `BaseEntity` (final readonly) | Not just "entity" when referring to the class |
| Module names | Case-sensitive: `Auth` not `AUTH`, `SysAdmin` not `Sysadmin` |
| Role names | `super_admin` in code, `superadmin` in Spatie — must explain the normalization |

#### 4.3 Conceptual Accuracy (HIGH)

Spot-check key claims against actual code:

1. **Pick 2-3 key patterns** (Action Triad, Entity-Model separation, Base Class Mandate, Exception hierarchy) — verify the doc description matches the actual class signatures
2. **Pick 2-3 feature descriptions** — verify the described behavior actually exists in code (not aspirational)
3. **Pick 2-3 code snippets** — verify the classes/methods referenced actually exist with documented signatures
4. **Module dependency claims** — if doc says A depends on B, verify at least one import from B in A

#### 4.4 Cross-Document Consistency (MEDIUM)

1. **Cross-references:** If `doc-a.md` links to `doc-b.md#section`, ensure that section header still exists
2. **Shared descriptions:** Same feature across multiple docs should not contradict each other
3. **Derivative vs SSOT:** After fixing derivative docs (Step 3), ensure no contradictions remain

### Step 5 — Fix Pattern

| Scenario | Action |
|----------|--------|
| Derivative doc contradicts SSOT | Fix derivative doc to match SSOT |
| Broken link | Update to correct path or remove |
| Inconsistent terminology | Align to canonical term from `docs/architecture.md` |
| Conceptually wrong description | Update doc to match actual code behavior |
| Stale exact count | Replace with generic description (e.g. "40+" instead of "42") |
| Cross-document contradiction | Fix less authoritative doc; SSOT wins |
| Missing feature in code that doc claims exists | Add to `docs/known-issues.md` |

### Step 6 — Update Metadata

Every edited file must have its metadata updated:

```markdown
> **Last updated:** {YYYY-MM-DD}
> **Changes:** {brief summary of what changed and why}
```

### Step 7 — Update doc-index.md

After all edits:
1. Ensure every `.md` file is listed in the appropriate section
2. Ensure listed files actually exist (remove dead references)
3. Update date and changes summary

### Step 8 — Final Verification

```bash
# Verify no broken relative markdown links
find docs -name "*.md" -exec grep -oP '\[.*?\]\([^)]+\.md[^)]*\)' {} \; | sort -u | while IFS= read -r line; do
  link_text=$(echo "$line" | grep -oP '\([^)]+\)' | tr -d '()' | sed 's/#.*$//' | sed 's|^\.\/||')
  echo "$link_text"
done | sort -u | while read target; do
  if [ -n "$target" ] && ! echo "$target" | grep -qE '^https?://'; then
    if [ ! -f "$target" ] && [ ! -f "docs/$target" ]; then
      echo "BROKEN LINK TARGET: $target"
    fi
  fi
done

vendor/bin/pint --format agent
```

### Step 9 — Known Issues

Update `docs/known-issues.md`:
1. Mark resolved documentation gaps as `[RESOLVED]` with date
2. Add new content-quality gaps found during sync
3. Update the change summary at the top

## Priority Reference

| Priority | What to check | Where |
|----------|---------------|-------|
| **P0** | Derivative docs match SSOT | AGENTS.md, GEMINI.md, README.md vs docs/ |
| **P1** | Broken links + orphan files | All .md files |
| **P1** | Terminology consistency | Cross-file |
| **P2** | Conceptual accuracy (spot-check) | docs/ |
| **P3** | Cross-document consistency | docs/ |
| **P4** | Stale counts (generify only) | docs/ |

## Quality Checklist

- [ ] AGENTS.md verified against `docs/` — no contradictions
- [ ] GEMINI.md verified against `docs/` — no contradictions
- [ ] README.md links and commands verified
- [ ] Skill file paths verified against actual code
- [ ] No broken links in docs tree
- [ ] No orphan `.md` files
- [ ] Terminology consistent across all checked docs
- [ ] Key conceptual descriptions match actual code
- [ ] `doc-index.md` up to date
- [ ] `known-issues.md` updated with any gaps found
- [ ] `vendor/bin/pint --format agent` passes
