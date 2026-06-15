---
name: sync-docs
description: Synchronize ALL markdown documentation across the entire repository against actual code implementation. The core job is to verify that what docs SAY matches what the code ACTUALLY DOES — every claim about classes, methods, signatures, behaviors, patterns, and features must be grounded in real code. Structural checks (links, orphans) are secondary.
---

# Documentation Sync Skill

## When to Activate

Apply this skill when asked to synchronize, update, refresh, or align documentation with implementation. Covers **every `.md` file in the repository**.

## Core Principle

Documentation that incorrectly describes the code is worse than no documentation — it actively misleads. The primary job is **truthfulness**: every claim in every `.md` file must accurately reflect what the codebase actually implements.

## Scope: ALL `.md` Files

Excluding `node_modules/`, `vendor/`, `.git/`, `storage/`:

```
docs/**/*.md         → main documentation tree
README.md            → project root
AGENTS.md            → agent instructions
GEMINI.md            → Gemini configuration
.agents/skills/**/*.md → skill files
opencode.json        → (check for stale skill references)
```

**Do NOT trust derivative files (AGENTS.md, GEMINI.md).** They are frequently outdated copies from `docs/`. Always verify them against both `docs/` SSOT and actual code.

## Workflow

### Step 1 — Discover + Classify

```bash
find . -name "*.md" -not -path "*/node_modules/*" -not -path "*/vendor/*" -not -path "*/.git/*" -not -path "*/storage/*" | sort
```

Classify each file: **SSOT** (`docs/`) or **Derivative** (root-level files, `.agents/`).

### Step 2 — Load Reference Context

Read FIRST:
- `docs/architecture.md` — action triad, base classes, patterns
- `docs/conventions.md` — naming, file structure, PHP rules
- `docs/modules/module-index.md` — module boundaries and dependencies
- `docs/doc-index.md` — document catalog

### Step 3 — Audit Against Code Implementation

This is the **primary** step. For every substantive claim in a `.md` file, verify against actual code.

#### 3.1 Class & Method Claim Verification

When a doc mentions a specific class or method, verify:

| Claim | How to verify |
|-------|---------------|
| **"`X extends Y`"** | `grep "class X" app/ -r | grep "extends"` |
| **"`X::execute()` accepts params P, Q, R"** | Read the actual `execute()` method signature |
| **"`X` is in namespace `App\Foo\Bar`"** | `grep "namespace" app/Foo/Bar/X.php` |
| **"File lives at `app/Foo/Bar/X.php`"** | `ls app/Foo/Bar/X.php` |
| **"Contract `LabelEnum` has method `label()`"** | `grep "function label" app/Core/Contracts/LabelEnum.php` |
| **"`BaseAction` provides `transaction()`, `log()`"** | Read `app/Core/Actions/BaseAction.php` methods |

**For every doc that lists classes (module reference docs, architecture docs), spot-check at least 3 claims against actual code.**

#### 3.2 Behavior & Business Rule Verification

When a doc describes what code DOES, verify:

| Claim | How to verify |
|-------|---------------|
| **"Super Admin name is ALWAYS `Administrator`"** | Check `config/setup.php` + `SetupSuperAdminAction::execute()` |
| **"Login locks after 5 failed attempts"** | Check `LoginAction` for rate limiter / throttle logic |
| **"Recovery codes are 12-character uppercase"** | Check `GenerateRecoverySlipAction` for `str()->random(12)` |
| **"Certificate QR contains verification URL"** | Check `CertificateRenderer` or `Certificate` model |
| **"Entity is `final readonly`"** | Check actual entity class declaration |

#### 3.3 Architecture Pattern Verification

When a doc describes a pattern, verify it's actually followed:

| Pattern claim | How to verify |
|---------------|---------------|
| **"All Command Actions extend `BaseCommandAction`"** | `grep -r "extends Base\|class.*Action" app/*/Actions/` — find outliers |
| **"Entities have `fromModel()` and are `final readonly`"** | Check 2-3 random entity files |
| **"Policies extend `BasePolicy`"** | Check 2-3 random policy files |
| **"Livewire CRUD uses `BaseRecordManager`"** | Check 2-3 CRUD Livewire components |
| **"Events extend `BaseEvent`"** | Check 2-3 random event files |
| **"No `DB::` in Entities"** | `grep -r "DB::" app/*/Entities/` |

#### 3.4 Derivative Document Verification

**AGENTS.md** — Every claim must match `docs/` SSOT:
- Stack version → `composer.json` + `docs/architecture.md`
- Skill list → `ls -d .agents/skills/*/`
- Module invariants → `config/setup.php` + `docs/architecture.md` §Module Invariants
- Quick rules → `docs/conventions.md` §2, §5
- PHP essentials → `docs/conventions.md` §2
- Deployment essentials → `docs/infrastructure/deployment.md`
- Testing essentials → `docs/infrastructure/testing.md`

**GEMINI.md** — Same as AGENTS.md; must not contradict `docs/`.

**README.md** — Quick start commands must work; module listing must match `app/` directories; tech stack versions must match `composer.json`/`package.json`.

### Step 4 — Structural Checks (Secondary)

Only after code verification:

- **Broken links**: Every `[text](path)` resolves to an existing file
- **Orphan files**: Every `.md` file appears in `doc-index.md` or `README.md`
- **File paths**: Documented paths resolve to real files

### Step 5 — Terminology Consistency (Secondary)

Same concept uses same name everywhere:
- `Action Triad` not "Action pattern" in one place and "Triad pattern" in another
- Module names case-sensitive: `Auth` not `AUTH`
- Role names consistent with actual enum values

### Step 6 — Fix Pattern

| Finding | Action |
|---------|--------|
| Doc says X, code does Y | Fix doc to match actual code |
| Doc says X, code should do X but doesn't | Add to `docs/known-issues.md` as code gap |
| Derivative doc contradicts SSOT | Fix derivative, SSOT wins |
| Broken link | Fix or remove |
| Stale exact count | Replace with generic (e.g. "40+") |
| Inconsistent terminology | Align to canonical term |

### Step 7 — Update Metadata + Index

- Update `> **Last updated:**` on every edited file
- Update `docs/doc-index.md` date + changes summary
- Ensure all `.md` files appear in `doc-index.md`

### Step 8 — Final Verification

```bash
vendor/bin/pint --format agent
```

### Step 9 — Known Issues

Update `docs/known-issues.md` with new gaps found.

## Priority Reference

| Priority | Focus | Effort |
|----------|-------|--------|
| **P0** | Derivative docs match SSOT + actual code (AGENTS.md, GEMINI.md, README.md) | Quick per-file scan |
| **P1** | Code implementation verification — class claims, behavior claims, pattern claims | Spot-check 3-5 per doc |
| **P2** | Broken links, orphan files, file path accuracy | Automated |
| **P3** | Terminology consistency | Cross-file grep |
| **P4** | Stale counts → generify | Quick replace |

## Quality Checklist

- [ ] AGENTS.md verified against `docs/` AND actual code — no contradictions
- [ ] GEMINI.md verified against `docs/` AND actual code — no contradictions
- [ ] README.md commands verified, module listing matches `app/`
- [ ] Skill file paths verified against actual code files
- [ ] At least 3 class/method claims spot-checked per doc
- [ ] Architecture pattern claims verified against actual code
- [ ] No broken links in docs tree
- [ ] No orphan `.md` files
- [ ] `doc-index.md` up to date
- [ ] `known-issues.md` updated with any gaps found
- [ ] `vendor/bin/pint --format agent` passes
