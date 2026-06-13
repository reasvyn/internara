---
name: sync-docs
description: Comprehensive synchronization of ALL markdown documentation against actual implementation across the entire codebase. Covers docs/, README.md, AGENTS.md, GEMINI.md, .agents/, and every other markdown file without exception. Fixes inconsistencies, updates stale counts, corrects examples, and ensures every file references files that exist.
---

# Documentation Sync Skill

## When to Activate

Apply this skill when asked to synchronize, update, refresh, or align documentation with implementation. This covers every `.md` file in the entire repository without exception.

## Core Principle

Documentation IS the single source of truth (per `docs/conventions.md` §0). When documentation and implementation disagree, documentation describes the target state — but it must accurately describe the *actual* state, not an aspirational one. The goal is to make every `.md` file truthfully describe the current codebase.

## Workflow

### Step 0 — Discover All Markdown Files

```bash
find . -name "*.md" -not -path "./node_modules/*" -not -path "./vendor/*" -not -path "./.git/*" -not -path "./storage/*" | sort > /tmp/all_markdown_files.txt
```

This gives a complete inventory. The list includes (but is not limited to):
- `docs/**/*.md` — the main documentation tree
- `README.md` — project root
- `AGENTS.md` — agent instructions
- `GEMINI.md` — Gemini configuration
- `.agents/skills/**/*.md` — all skill files
- `.github/**/*.md` — GitHub templates and workflows
- `opencode.json` — opencode config (if markdown-like sections exist)
- Any other `.md` files found

### Step 1 — Load Reference Context

Read these authoritative references FIRST (concurrent):
- `docs/architecture.md` — architecture patterns, counts, framework stack
- `docs/conventions.md` — all conventions
- `docs/modules/module-index.md` — module listing, global counts
- `docs/doc-index.md` — documentation catalog
- `AGENTS.md` — invariants, quick rules

### Step 2 — Audit Against Actual Implementation

For each markdown file, execute these checks:

#### 2.1 Structural Verification

| Check | What to verify |
|-------|---------------|
| **Directory exists** | Every `app/{Module}/` or `resources/views/{module}/` path mentioned in docs must actually exist |
| **File exists** | Every class path, config path, or test path mentioned must be a real file |
| **Broken links** | Every `[text](path)` and `<path>` reference must resolve to an existing file or valid URL |
| **Orphan files** | Every `.md` file should be referenced somewhere in `doc-index.md` or `README.md` |

#### 2.2 Count Verification

For every number claim in docs (e.g., "50 models", "19 modules", "10 policies"), verify against actual:

```bash
# Count models (concrete classes extending BaseModel)
grep -r "extends BaseModel" app/ --include="*.php" | grep -v "abstract class" | wc -l

# Count actions
find app -path "*/Actions/*.php" -type f | wc -l

# Count policies
find app -path "*/Policies/*.php" -type f | not matching Concerns/ | wc -l

# Count Livewire components
find app -path "*/Livewire/*.php" -type f | grep -v "Concerns\|BaseRecordManager" | wc -l

# Count routes files
ls routes/web/*.php 2>/dev/null | wc -l

# Count migrations
ls database/migrations/*.php 2>/dev/null | wc -l

# Count test files
find tests -name "*Test.php" -type f | wc -l
```

Update every discrepancy. Use generic descriptions where counts change frequently (e.g., "40+" instead of "42").

#### 2.3 Enum Value Verification

For every enum documented with specific values, check against actual:

```bash
# List all enum files with their cases
grep -r "case " app/ --include="*.php" -l | while read f; do
  echo "=== $f ==="
  grep "case " "$f" | sed 's/^[[:space:]]*//'
done
```

#### 2.4 File Path Verification

For every file path mentioned in docs:
1. Verify the file exists at that exact path
2. Verify the namespace matches the path convention
3. Verify the class name matches the file name

#### 2.5 API/Interface Contract Verification

For documented method signatures:
1. Check the method exists on the class
2. Check the parameter types match
3. Check the return type matches

### Step 3 — Cross-Document Consistency

After fixing individual files, verify consistency ACROSS documents:

1. **Cross-references:** If `doc-a.md` links to `doc-b.md#section`, ensure that section header still exists in doc-b.md after edits.
2. **Shared claims:** If both `architecture.md` and `module-index.md` mention "50 models", both must be updated to the same number.
3. **Dependency order:** Module dependency claims in module-index.md should match actual `use` imports across modules.
4. **Terminology:** Same concept uses same name everywhere (e.g., "Action Triad" not "Action pattern" in one place and "Triad pattern" in another).

### Step 4 — Fix Pattern

For every discrepancy found:

1. **If doc says X but code does Y:**
   - If X is the target state and Y is legacy: Keep doc as X, note the gap in `docs/known-issues.md`
   - If X is aspirational and Y is what actually works: Update doc to Y
   - If X is just stale (outdated count, old name): Update doc to Y

2. **If doc says X and code should do X but doesn't:**
   - If it's a missing feature/pattern: Add to `docs/known-issues.md`
   - If it's an unwritten rule: Keep doc, add enforcement note

3. **If doc references a file that doesn't exist:**
   - Remove the reference or update to the correct path

4. **If doc lists enum values that don't match:**
   - Update doc to match actual enum cases

5. **If doc has stale counts (models, actions, policies):**
   - Update to actual count from codebase
   - Use ranges or approximate counts where exact numbers change frequently

### Step 5 — Update Metadata

Every edited file must have its metadata updated:

```markdown
> **Last updated:** {YYYY-MM-DD}
> **Changes:** {brief summary of what changed and why}
```

### Step 6 — Document Index Consistency

After all edits, update `docs/doc-index.md`:
1. Ensure every `.md` file is listed in the appropriate section
2. Ensure listed files actually exist (remove dead references)
3. Update the "Last updated" date and changes summary

### Step 7 — Final Verification

```bash
# Verify no broken markdown links (relative paths)
find docs -name "*.md" -exec grep -oP '\[.*?\]\(.*?\.md[^)]*\)' {} \; | sort -u | while read link; do
  target=$(echo "$link" | grep -oP '\(.*?\.md[^)]*\)' | tr -d '()')
  if [ ! -f "$target" ] && [ ! -f "docs/$target" ]; then
    echo "BROKEN LINK: $link in some file"
  fi
done

# Verify all module directories exist
grep -r "app/" docs/ --include="*.md" | grep -oP 'app/\w+' | sort -u | while read dir; do
  if [ ! -d "$dir" ]; then
    echo "NON-EXISTENT MODULE: $dir referenced in docs"
  fi
done

# Run tests to ensure docs changes didn't break anything
php artisan test --compact --filter=Documentation
vendor/bin/pint --format agent
```

### Step 8 — Known Issues Document

Update `docs/known-issues.md`:
1. Mark resolved documentation gaps as `[RESOLVED]` with date
2. Add new documentation gaps found during sync as new entries
3. Update the change summary at the top

## Specific Files Requiring Special Attention

### `README.md`
- Project description matches actual feature set
- Badges (PHP version, Laravel version, license) match actual
- Quick start commands work
- Feature list matches `docs/key-features.md`

### `AGENTS.md`
- Module invariants match actual code
- Skill list matches `.agents/skills/` directory
- Quick-reference rules match `docs/conventions.md`
- Boost commands match available tools
- PHP essentials match actual language features used

### `GEMINI.md`
- Model context matches actual Gemini API version
- Configuration matches actual `.env` values
- Feature descriptions match implementation

### `.agents/skills/*/SKILL.md`
- All file paths referenced in skills exist
- All command examples produce expected output
- All workflow steps are actionable with current tools

### `opencode.json` / `opencode.jsonc`
- Agent configurations reference valid skill names
- Permission rules match directory structure
- Custom commands reference valid scripts

## Quality Checklist

- [ ] Every `.md` file inventoried
- [ ] File existence verified for all documented paths
- [ ] Counts updated to match actual implementation
- [ ] Enum values match actual enum cases
- [ ] Cross-references validated (no broken links)
- [ ] `doc-index.md` updated
- [ ] `known-issues.md` updated with new/resolved gaps
- [ ] `AGENTS.md` invariants verified
- [ ] Last updated dates set on all edited files
- [ ] `vendor/bin/pint --format agent` passes
