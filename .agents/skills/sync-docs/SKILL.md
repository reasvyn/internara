---
name: sync-docs
description: SDLC Phase: MAINTENANCE. Comprehensive markdown documentation sync against actual code implementation. Dynamically discovers patterns and rules from authoritative docs, then verifies them against code. Runs after any implementation or refactoring to keep docs in sync.
upstream:
  - feature-building
  - code-refactoring
  - livewire-development
  - tailwindcss-development
  - medialibrary-development
  - pulse-development
  - pest-testing
  - audit-protocol
  - security-audit
  - roadmap-planning
---

# Documentation Sync Skill

## When to Activate

Apply this skill when asked to synchronize, update, refresh, or align documentation with implementation. Covers **every `.md` file in the repository** (excl. `node_modules/`, `vendor/`, `.git/`, `storage/`).

## SDLC Context

| Role | Skill |
|------|-------|
| **Upstream (input)** | ALL skills — any change that affects documentation |
| **This skill** | **MAINTENANCE** — keeps docs in sync with code |
| **Downstream (output)** | Updated documentation across all `docs/` |
| **Phase** | [Planning] → [Analysis] → [Design] → [Implementation] → [Testing] → Maintenance |

## Core Principles

### 1. Never Hardcode Rules

This skill does **not** hardcode what "correct" looks like. Instead, it:

1. **Discovers** what "correct" means by reading the authoritative docs
2. **Verifies** that both docs and code match each other
3. **Fixes** whichever side is wrong

If a rule changes in `docs/conventions.md` next week, this skill still works — it reads the updated rule and checks against it. No maintenance needed.

### 2. Avoid Brittle Content in Documentation

Numbers, states, statuses, and enumerated lists are **brittle** — they become stale the moment code changes. The only exception is if the document is explicitly designed as a catalog, inventory, or registry (e.g. `docs/doc-index.md`, `docs/modules/module-index.md`, `docs/adr/adr-index.md`).

**When writing or fixing documentation, prefer:**

| Brittle (avoids) | Resilient (prefers) |
|---|---|
| "There are 42 models" | "Models extend `BaseModel`" (structural statement) |
| "Currently in ALPHA stage" | Describe what is implemented (factual statement) |
| "Auth module has 10 actions" | "Actions live under `app/Auth/*/Actions/`" (locational statement) |
| "Status: 3 resolved, 2 open" | List known issues without promising total counts |
| "PHP 8.4, Laravel 13, Livewire 4" (in derivative docs only) | Keep stack versions only in `composer.json` / `package.json` — derivative docs should reference those instead of duplicating |
| "Tiers: Shared Hosting, VPS" (enumeration that may grow) | Describe the concept without promising an exhaustive list |

**When you encounter brittle content during a sync:**

1. If the doc is **not** a catalog (e.g. `docs/architecture.md`, guide chapters, pattern docs): rewrite the brittle statement into a structural/locational/factual form
2. If the doc **is** a catalog (e.g. `docs/doc-index.md`, `docs/modules/module-index.md`): keep the listing but note that counts will drift — do not exhaustively correct them
3. If the doc is **derivative** (AGENTS.md, README.md): remove duplicated version numbers and counts entirely; they should reference authoritative sources instead

## Scope

Every `.md` file in the repo. Grouped into two categories:

| Category | Examples | Role |
|----------|----------|------|
| **SSOT** | `docs/architecture.md`, `docs/conventions.md`, `docs/architecture/*.md`, `docs/modules/*.md`, `docs/infrastructure/*.md` | Define what's correct |
| **Derivative** | `README.md`, `AGENTS.md`, `.agents/skills/*/SKILL.md` | Reference/summarize SSOT; must be verified against it |

---

## Full Workflow

### Phase 1: Load the Rulebook

Read these to understand what the project claims about itself. These define the "contract" between docs and code.

```bash
# Start with these (foundational):
docs/architecture.md
docs/conventions.md
docs/modules/module-index.md
docs/doc-index.md

# Then add these based on the scope of sync needed:
docs/architecture/action-pattern.md
docs/architecture/entity-pattern.md
docs/architecture/model-pattern.md
docs/architecture/enum-pattern.md
docs/architecture/event-pattern.md
docs/architecture/livewire-pattern.md
docs/architecture/policy-pattern.md
docs/architecture/logging-pattern.md
docs/architecture/testing-pattern.md

# If syncing module docs:
docs/modules/{module}.md
docs/modules/{module}-reference.md
```

As you read each doc, extract a list of **verifiable claims** — statements that can be checked against actual code. For example:

- "Command Actions extend `BaseCommandAction`" → verifiable claim
- "Entities are `final readonly`" → verifiable claim
- "business modules listed in module-index.md" → verifiable claim
- "Uses Livewire 4" → verifiable claim
- "Every Action has its own test file" → verifiable claim

### Phase 2: Discover All Markdown Files

```bash
find . -name "*.md" -not -path "*/node_modules/*" -not -path "*/vendor/*" -not -path "*/.git/*" -not -path "*/storage/*" | sort
```

Categorize each file: SSOT or Derivative.

### Phase 3: Verify Derivative Files (AGENTS.md, README.md)

These are the **most likely to be stale** because they're manual summaries of `docs/`.

#### 3.1 AGENTS.md

For each section, extract claims and verify:

| Section in AGENTS.md | How to verify |
|----------------------|---------------|
| **Project Context**: stack versions | Check `composer.json` require section, `docs/architecture.md` Layer 1 |
| **Skills Activation**: listed skills | `ls -d .agents/skills/*/` — every skill directory must be listed; no extra, no missing |
| **Documentation (NOT Duplicated Here)**: topic→location mappings | Each mapped file must actually exist and cover that topic |
| **Module Invariants**: listed invariants | Check each against actual code/config. Example: "Super Admin name is ALWAYS `Administrator`" → check `config/setup.php` key `defaults.admin_name` |
| **Quick-Reference Rules**: listed rules | Match each against `docs/conventions.md`. If rule exists in conventions.md, ensure AGENTS.md says the same thing. If rule changed in conventions.md, AGENTS.md is stale. |
| **PHP Essentials**: listed rules | Cross-check with `docs/conventions.md` §2 General PHP |
| **Deployment Essentials**: listed commands | Each `php artisan` command must exist or be a valid command name |
| **Testing Essentials**: listed rules | Match against `docs/infrastructure/testing.md` |

**Fix**: If AGENTS.md and `docs/` disagree, `docs/` is authoritative. Update AGENTS.md.

#### 3.2 README.md

| Claim | How to verify |
|-------|---------------|
| **Project description** | Compare with `docs/foundation/product-definition.md` |
| **Module listing** (all directories under `app/`) | `ls -d app/*/` — every listed module must exist; no extra |
| **Tech stack table** (every row) | Each package/version claim: `composer show laravel/framework \| grep versions`, `composer show livewire/livewire \| grep versions`, etc. |
| **Prerequisites** (PHP/Node versions) | Check `composer.json` require.php, `package.json` engines |
| **Quick Start** (every step) | `php artisan list --raw \| grep setup:install` — command must exist; `composer run dev` — check `composer.json` scripts |

#### 3.4 `.agents/skills/*/SKILL.md`

For each skill's SKILL.md, verify all file path references and code examples:

```bash
# Extract all file paths from a skill file
grep -oP 'app/[a-zA-Z/]+\.php' path/to/SKILL.md | while read f; do
  if [ ! -f "$f" ]; then echo "MISSING: $f"; fi
done

# Extract all class references and verify declarations
grep -oP '`[A-Z][a-zA-Z]+`' path/to/SKILL.md | sort -u
```

### Phase 4: Verify SSOT (`docs/`) Against Code

This is the **largest phase**. Process each SSOT file systematically.

#### 4.1 Structural Integrity

```bash
# 4.1.1 — Check all relative markdown links resolve
# For each .md file, extract links and verify targets exist
find docs -name "*.md" | while read source; do
  dir=$(dirname "$source")
  grep -oP '\[.*?\]\([^)]+\)' "$source" | while read link; do
    target=$(echo "$link" | sed 's/.*\[.*\](\(.*\))/\1/' | sed 's/#.*$//')
    case "$target" in
      http*|#*) ;;
      *) resolved="$dir/$target"
         [ ! -f "$resolved" ] && [ ! -f "$target" ] && echo "BROKEN: $source -> $target" ;;
    esac
  done
done

# 4.1.2 — Check orphan .md files (not linked from doc-index or README)
# Every .md file should be discoverable from doc-index.md or README.md

# 4.1.3 — Check documented file paths resolve
grep -roh 'app/[A-Za-z0-9/]\+\.php' docs/ | sort -u | while read f; do
  [ ! -f "$f" ] && echo "MISSING PATH: $f"
done
grep -roh 'config/[a-z-]\+\.php' docs/ | sort -u | while read f; do
  [ ! -f "$f" ] && echo "MISSING CONFIG: $f"
done
grep -roh 'resources/views/[a-z0-9/_-]\+\.blade\.php' docs/ | sort -u | while read f; do
  [ ! -f "$f" ] && echo "MISSING VIEW: $f"
done
grep -roh 'routes/[a-z/]\+\.php' docs/ | sort -u | while read f; do
  [ ! -f "$f" ] && echo "MISSING ROUTE: $f"
done
grep -roh 'tests/[A-Za-z0-9/_]\+Test\.php' docs/ | sort -u | while read f; do
  [ ! -f "$f" ] && echo "MISSING TEST: $f"
done
```

#### 4.2 Architecture Pattern Compliance

Read the pattern docs (`docs/architecture/action-pattern.md`, `entity-pattern.md`, etc.) and extract the **verifiable rules**. Then check the codebase for compliance.

**How this works dynamically** — instead of hardcoding checks, you read the pattern doc and create checks on the fly:

```bash
# Example: If docs/architecture/action-pattern.md says
# "Command Actions MUST extend BaseCommandAction"
# Then run:
grep -rl "class.*Action" app/*/Actions/ app/*/*/Actions/ | while read f; do
  if grep -q "extends BaseCommandAction\|extends BaseReadAction\|extends BaseProcessAction" "$f" 2>/dev/null; then
    : # OK
  elif grep -q "BaseAction\|abstract class" "$f" 2>/dev/null; then
    : # OK (base files)
  else
    echo "OUTLIER: $f does not extend any Action base class"
  fi
done

# Example: If docs/architecture/entity-pattern.md says
# "Entities are final readonly classes extending BaseEntity"
# Then run:
find app -path "*/Entities/*.php" -type f | while read f; do
  if grep -q "final readonly" "$f"; then
    : # OK
  else
    echo "OUTLIER: $f is not final readonly"
  fi
done
```

Repeat this pattern for every verifiable rule in the pattern docs.

#### 4.3 Inline Code Example Verification

Docs often contain inline PHP code snippets. These are **frequently wrong** (method signatures change, classes get renamed, etc.).

```bash
# Extract inline code blocks that look like PHP
# Then verify key elements:
# - Class names referenced (grep for class declaration)
# - Method names referenced (grep for method in that class)
# - Method signatures (parameter types, return types)
# - Static method calls (verify method exists on that class)
```

For each inline code block, verify at least:
1. Every class referenced actually exists
2. Every method referenced exists on that class
3. Constructor parameter names match actual code
4. Return types match actual code

#### 4.4 Module Reference Doc Accuracy

For each `docs/modules/{module}-reference.md`, verify:

```bash
module="Academics"  # or whatever module being checked
ref="docs/modules/$module-reference.md"

# 4.4.1 — Action file listing
grep -oP '`Actions/[^`]+`' "$ref" | while read f; do
  path=$(echo "$f" | tr -d '`')
  full="app/$module/$path"
  [ ! -f "$full" ] && [ ! -f "app/$module/${path#Actions/}" ] && echo "MISSING ACTION: $full"
done

# 4.4.2 — Model file listing
grep -oP '`Models/[^`]+`' "$ref" | while read f; do
  path=$(echo "$f" | tr -d '`')
  full="app/$module/$path"
  [ ! -f "$full" ] && echo "MISSING MODEL: $full"
done

# 4.4.3 — Policy file listing
grep -oP '`Policies/[^`]+`' "$ref" | while read f; do
  path=$(echo "$f" | tr -d '`')
  full="app/$module/$path"
  [ ! -f "$full" ] && echo "MISSING POLICY: $full"
done

# 4.4.4 — Livewire file listing
grep -oP '`Livewire/[^`]+`' "$ref" | while read f; do
  path=$(echo "$f" | tr -d '`')
  full="app/$module/$path"
  [ ! -f "$full" ] && echo "MISSING LIVEWIRE: $full"
done

# 4.4.5 — Event file listing (if present)
grep -oP '`Events/[^`]+`' "$ref" | while read f; do
  path=$(echo "$f" | tr -d '`')
  full="app/$module/$path"
  [ ! -f "$full" ] && echo "MISSING EVENT: $full"
done

# 4.4.6 — Entity file listing (if present)
# 4.4.7 — Enum file listing (if present)
# 4.4.8 — Data/DTO file listing (if present)
# 4.4.9 — Notification file listing (if present)
```

#### 4.5 Cross-Module Consistency

```bash
# 4.5.1 — Module dependency claims
# If docs/modules/module-index.md says "Enrollment depends on User"
# verify: grep -r "use App\\User" app/Enrollment/ | head -5

# 4.5.2 — Same term, same meaning across all docs
# Pick 3-5 key terms from docs/architecture.md and grep all .md files
# for variants:
#   "Action Triad" — not "Action triad", "action triad", "Triad pattern"
#   "Command Action" — not "write action", "mutator action"
#   "super_admin" — consistent spelling with underscore
```

### Phase 5: Fix Everything

| Finding | Action |
|---------|--------|
| Derivative doc (AGENTS.md/README.md) contradicts `docs/` | Fix derivative, `docs/` wins |
| Doc claims behavior X but code does Y | Fix doc to match code |
| Doc claims behavior X, code should do X but doesn't | Add to `docs/known-issues.md` as code gap |
| Doc file path points to nonexistent file | Fix path or remove reference |
| Broken markdown link | Fix target or remove |
| Doc contains stale inline code example | Fix example to match actual code |
| Architecture pattern rule violated in code | Add to `docs/known-issues.md` |
| Inconsistent terminology across docs | Align all to canonical term from `docs/architecture.md` |
| Stale numeric count | Replace with generic (e.g. "40+") — not worth chasing |

### Phase 6: Finalize

1. **Update metadata** on every edited file:
   ```markdown
   > **Last updated:** {YYYY-MM-DD}
   > **Changes:** {brief summary}
   ```

2. **Update `docs/doc-index.md`** — date, changes summary, ensure all `.md` files referenced exist

3. **Update `docs/known-issues.md`** — add new gaps found, mark resolved ones

4. **Run quality commands:**
   ```bash
   vendor/bin/pint --format agent
   ```

---

## Quick Reference: Most Common Sync Gaps

Based on past syncs, these are the most frequently encountered issues:

| Gap | Where it hides | What to check |
|-----|----------------|---------------|
| Action listed in doc but file missing | Module reference docs | `ls app/{Module}/**/Actions/*.php` vs listed actions |
| UserManagement actions incorrectly listed under SysAdmin | `docs/modules/sysadmin-reference.md` | SysAdmin has no UserManagement submodule |
| Test count stale | `docs/modules/core-reference.md` | `find tests -name "*Test.php" \| wc -l` |
| Command examples in skills reference renamed classes | `.agents/skills/*/SKILL.md` | `grep -oP 'app/[a-zA-Z/]+\.php'` and check existence |
| AGENTS.md skill list out of sync with `.agents/skills/` | `AGENTS.md` | `ls -d .agents/skills/*/` vs listed skills |
| Derivative doc says rule X but ssot doc says Y | `AGENTS.md` | Cross-check every rule against `docs/conventions.md` |
| Doc references class that was renamed | Any `.md` file | `grep -oP 'App\\[a-zA-Z\\\\]+'` and check `app/` path |

## Quality Checklist

- [ ] AGENTS.md verified — no contradictions with `docs/` or actual code
- [ ] README.md — module listing, tech stack, quick start all accurate
- [ ] Skill files — all referenced paths and classes exist
- [ ] All module reference action/model/policy listings verified against actual files
- [ ] No broken markdown links
- [ ] Inline code examples verified against actual class signatures
- [ ] Architecture pattern claims spot-checked against actual code
- [ ] `doc-index.md` up to date
- [ ] `known-issues.md` updated
- [ ] `vendor/bin/pint --format agent` passes
