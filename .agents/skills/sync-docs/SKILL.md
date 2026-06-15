---
name: sync-docs
description: Synchronize documentation against actual implementation across the entire codebase. Focuses on content accuracy, terminology consistency, structural integrity, and cross-document coherence — NOT on chasing easily-stale counts or lists.
---

# Documentation Sync Skill

## When to Activate

Apply this skill when asked to synchronize, update, refresh, or align documentation with implementation. Covers every `.md` file in the repository.

## Core Principle

Documentation IS the single source of truth (per `docs/conventions.md` §0). The goal is to make every `.md` file truthfully describe the current codebase with accurate, consistent, and coherent content.

**DO NOT** chase easily-stale items: exact counts of models/actions/policies, enum case listings, file counts, or similar metadata that changes frequently. These add maintenance burden without proportional value.

**DO** focus on: broken links, terminology consistency, structural accuracy, conceptual correctness, and cross-document coherence.

## Workflow

### Step 1 — Discover All Markdown Files

```bash
find . -name "*.md" -not -path "./node_modules/*" -not -path "./vendor/*" -not -path "./.git/*" -not -path "./storage/*" | sort
```

### Step 2 — Load Reference Context

Read these authoritative references FIRST:
- `docs/architecture.md` — architecture patterns, framework stack
- `docs/conventions.md` — all conventions
- `docs/modules/module-index.md` — module listing, dependency map
- `docs/doc-index.md` — documentation catalog
- `AGENTS.md` — invariants, quick rules

### Step 3 — Content Quality Audit

For each markdown file, execute these checks in priority order:

#### 3.1 Structural Integrity (HIGH)

| Check | What to verify |
|-------|---------------|
| **Broken links** | Every `[text](path)` and `<path>` reference must resolve to an existing file or valid URL |
| **Orphan files** | Every `.md` file should be referenced somewhere in `doc-index.md` or `README.md` |
| **File path accuracy** | Every documented file path (e.g. `app/User/Models/User.php`) must point to a real file |

#### 3.2 Terminology Consistency (HIGH)

- Same concept uses the same name everywhere throughout the entire doc tree
- Verify these key terms are consistent (check at least the 3 most relevant docs + architecture.md):
  - `Action Triad` (not "Action pattern" in one place and "Triad pattern" in another)
  - `Command Action` / `Read Action` / `Process Action`
  - `BaseEntity` / `Entity` / `final readonly`
  - `Module` / `Submodule` — consistent nesting terminology
  - Module names match directory names case-sensitively (Auth, not AUTH)
  - Role names match enum values (super_admin, not superadmin — unless documented difference)

#### 3.3 Conceptual Accuracy (HIGH)

Spot-check these against actual code:

| Check | Method |
|-------|--------|
| **Architecture pattern described matches actual** | Pick 2-3 key patterns (Action Triad, Entity-Model separation, Base Class Mandate) and verify the doc description matches the actual base class signatures |
| **Feature descriptions match implementation** | Pick 2-3 major features described in docs and verify the described behavior exists in code (not aspirational/planned) |
| **Code examples compile** | Pick 2-3 inline code snippets and verify the classes/methods referenced actually exist with the documented signatures |
| **Dependency claims** | If doc claims module A depends on module B, verify at least one class in A imports something from B |

#### 3.4 Cross-Document Consistency (MEDIUM)

1. **Cross-references:** If `doc-a.md` links to `doc-b.md#section`, ensure that section header still exists
2. **Shared descriptions:** Same feature described across multiple docs should not contradict each other
3. **Terminology alignment:** All docs within a topic area use the same terms for the same concepts

#### 3.5 Stale Counts — Light Touch Only (LOW)

If a count is obviously and significantly wrong (e.g. "50 models" when there are actually 38), update it to a **generic** description:
- ❌ "42 models" → stale next week
- ✅ "40+ models" → survives additions
- ❌ "10 policies, 15 actions, 3 commands"
- ✅ "dozens of actions across all modules"

Use `git log --oneline -5` to quickly assess if the file is actively maintained or abandoned.

### Step 4 — Fix Pattern

| Scenario | Action |
|----------|--------|
| Broken link | Update to correct path or remove |
| Inconsistent terminology | Align to the canonical term (check `architecture.md` for authority) |
| Conceptually wrong description | Update doc to match actual code behavior |
| Stale count | Replace with generic description (e.g. "40+" instead of "42") |
| Cross-document contradiction | Fix the less authoritative doc to match the more authoritative one |
| Missing feature in code that doc claims exists | Add to `docs/known-issues.md` |

### Step 5 — Update Metadata

Every edited file must have its metadata updated:

```markdown
> **Last updated:** {YYYY-MM-DD}
> **Changes:** {brief summary of what changed and why}
```

### Step 6 — Update doc-index.md

After all edits:
1. Ensure every `.md` file is listed in the appropriate section
2. Ensure listed files actually exist (remove dead references)
3. Update the "Last updated" date and changes summary

### Step 7 — Final Verification

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

### Step 8 — Known Issues

Update `docs/known-issues.md`:
1. Mark resolved documentation gaps as `[RESOLVED]` with date
2. Add new content-quality gaps found during sync
3. Update the change summary at the top

## Specific Files Requiring Special Attention

| File | Focus |
|------|-------|
| `README.md` | Quick start commands actually work; feature list matches actual modules |
| `AGENTS.md` | Module invariants match actual code; skill list matches `.agents/skills/` directory |
| `docs/architecture.md` | Architectural patterns described match actual base classes and contracts |
| `docs/conventions.md` | Rules are actually enforced by Pint/PHPStan configs |
| `.agents/skills/*/SKILL.md` | All file paths referenced in skills exist |

## Quality Checklist

- [ ] No broken links in docs tree
- [ ] No orphan `.md` files
- [ ] Terminology consistent across all checked docs
- [ ] Key conceptual descriptions match actual code
- [ ] Feature claims verified against implementation
- [ ] `doc-index.md` up to date
- [ ] `known-issues.md` updated with any gaps found
- [ ] Last updated dates set on all edited files
- [ ] `vendor/bin/pint --format agent` passes
