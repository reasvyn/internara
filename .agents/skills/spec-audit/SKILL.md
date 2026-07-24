---
name: spec-audit
description: "SDLC Phase: ANALYSIS. Bidirectional spec-implementation audit — verifies specs match code and code matches specs. Determines which side (spec or implementation) needs fixing. Creates GitHub Issues for significant findings, fixes minor issues directly. Flexible scope: audit by spec, module, phase, or audit area."
downstream:
  - writing-issues
  - roadmap-planning
  - code-refactoring
  - feature-building
  - sync-docs
---

# Spec Audit — Specification ↔ Implementation Synchronization

> **Prerequisite:** Load `context-awareness` for project orientation, module map, and conventions.

## When to Activate

Use this skill to verify that feature specifications (`docs/specs/`) and code implementation
(`app/`, `tests/`, `routes/`, `database/migrations/`) are in sync. Detects three categories of
drift:

1. **Spec → Code:** Spec promises something the code doesn't deliver (missing implementation)
2. **Code → Spec:** Code does something the spec doesn't document (unspecified behavior)
3. **Both stale:** Spec and code disagree on shared contracts (signatures, paths, names)

**Key distinction from `arch-guard`:**
- `arch-guard` checks code against conventions and architecture rules (C1-C8, D1-D6)
- `spec-audit` checks code against feature specifications (FR/NFR/contracts)
- `arch-guard` is code-first; `spec-audit` is spec-first

**Key distinction from `sync-docs`:**
- `sync-docs` updates docs to match code (one direction: code → docs)
- `spec-audit` checks bidirectional sync and determines which side needs fixing
- `sync-docs` is MAINTENANCE; `spec-audit` is ANALYSIS

---

## Scope Configuration

Before executing, ask the user (or infer from context) which scope to audit:

### Scope Options

| Scope | What It Audits | Example |
|-------|---------------|---------|
| **Single spec** | One spec against its implementation | `spec-audit authentication.md` |
| **Module** | All specs for a module | `spec-audit --module Auth` |
| **Phase** | All specs in a lifecycle phase | `spec-audit --phase 3` |
| **Audit area** | Specific dimension across all/some specs | `spec-audit --area contracts` |
| **Full audit** | All 53 specs, all areas | `spec-audit --all` |

### Audit Areas

| Area | What It Checks |
|------|---------------|
| `paths` | File paths in spec Quick References exist in codebase |
| `contracts` | Method signatures, class names, DTOs, Entity contracts match |
| `requirements` | FR/NFR IDs have corresponding implementation |
| `tests` | Test files exist for spec'd components |
| `coverage` | Spec'd features are actually implemented (not just stubs) |
| `cross-refs` | Internal spec cross-references are correct (names, numbers) |
| `roadmap` | §9 Roadmap prerequisites and next-steps are valid |
| `all` | All areas combined (default for full audit) |

### Default Scope

If no scope is specified:
1. If recent git commits touched specific modules → audit those modules
2. If `docs/roadmap.md` has "Active Work" → audit those specs
3. Otherwise → prompt user for scope

---

## Agent Workflow

```
SCOPE → DISCOVER → AUDIT (6 areas) → TRIAGE → FIX/ISSUE → FINALIZE → REPORT
```

### Phase 1 — Scope & Discovery

**Goal:** Determine what to audit and discover all relevant artifacts.

#### 1.1 Load Context

- Load `context-awareness` skill for project orientation
- Read `docs/specs/index.md` for spec list and lifecycle phases
- Read `docs/modules/index.md` for module structure
- Read `docs/roadmap.md` for current development status

#### 1.2 Determine Scope

Based on user input or context:
- Parse scope arguments (spec name, module name, phase number, area name)
- Resolve scope to a list of spec files to audit
- If `--all`: list all specs from `docs/specs/index.md`
- If `--module {Name}`: filter specs by Module column in index
- If `--phase {N}`: filter specs by phase section in index
- If spec name: resolve to single file

#### 1.3 Discover Artifacts

For each spec in scope:

1. **Read the spec** — full content
2. **Extract references** from Quick References section:
   - File paths (e.g., `app/Auth/Actions/LoginAction.php`)
   - Class names (e.g., `LoginAction`)
   - Method signatures (e.g., `execute(string $email, string $password): ActionResponse`)
   - Route definitions (e.g., `POST /login`)
   - Migration files
   - Event/Listener classes
   - Policy classes
   - Test files
3. **Extract FR/NFR IDs** and their descriptions
4. **Extract §9 Roadmap** references (prerequisites, next steps)
5. **Extract §6 API/Data Contracts** — class signatures, DTOs, Entities

#### 1.4 Build Audit Map

For each spec, produce a structured audit map:

```json
{
  "spec": "authentication.md",
  "spec_number": 17,
  "phase": 3,
  "module": "Auth",
  "referenced_files": [...],
  "referenced_classes": [...],
  "referenced_routes": [...],
  "fr_ids": [...],
  "nfr_ids": [...],
  "cross_refs": [...],
  "roadmap_prereqs": [...],
  "roadmap_next": [...]
}
```

**Output:** List of audit maps, one per spec in scope.

---

### Phase 2 — Audit (6 Areas)

Execute each audit area against the audit map. Run areas in order; each area produces findings.

#### Area 1: Path Verification

**Goal:** Verify every file path referenced in the spec actually exists.

For each file path in Quick References and §6 API/Data Contracts:

1. Check if the file exists at the referenced path
2. If missing: check if it was renamed (search by class name in `app/`)
3. If still missing: record as **Spec→Code drift** (spec references non-existent code)

Also check reverse: if code exists in the expected module directory but is NOT referenced in the spec, record as **Code→Spec drift** (unspecified implementation).

```
Check: file_exists($path)
Search: grep -rn "class {ClassName}" app/ (if file missing)
```

**Findings:**

| ID | Type | Finding |
|----|------|---------|
| P-1 | Spec→Code | `{spec}` references `{path}` which does not exist |
| P-2 | Code→Spec | `{path}` exists but is not documented in `{spec}` |

#### Area 2: Contract Verification

**Goal:** Verify method signatures, class names, DTOs, and Entity contracts match between spec and code.

For each class referenced in §6 API/Data Contracts:

1. **Class existence:** Does the class exist at the referenced path?
2. **Class declaration:** Is it `final readonly` (Entity/DTO)? Is it `extends BaseCommandAction` (Action)?
3. **Method signature:** Does `execute()` match the spec'd signature (param types, return type)?
4. **DTO contract:** Does it extend `BaseData`? Are properties scalar/enum/Carbon only?
5. **Entity contract:** Does it have `fromModel()`? Is it `final readonly`?
6. **Model contract:** Does it use `#[Fillable]`? Does it extend `BaseModel`?

```
For each referenced class:
  grep -n "class {ClassName}" {path}
  grep -n "public function execute" {path}
  Compare signature against spec §6
```

**Findings:**

| ID | Type | Finding |
|----|------|---------|
| C-1 | Spec→Code | `{spec}` documents `{ClassName}` with signature `{spec_sig}` but code has `{code_sig}` |
| C-2 | Code→Spec | `{ClassName}` has `{code_sig}` but spec documents `{spec_sig}` |
| C-3 | Spec→Code | `{spec}` promises `{ClassName}` which does not exist |
| C-4 | Contract | `{ClassName}` violates {Entity|DTO|Model} contract ({detail}) |

#### Area 3: Requirements Coverage

**Goal:** Verify every FR/NFR ID has corresponding implementation.

For each FR in §4 Functional Requirements:

1. **Search for implementation** — grep for key terms from the FR description in `app/`
2. **Verify behavior** — read the found code and check if it matches the FR's intent
3. **Check completeness** — are all conditions in the FR satisfied?

For each NFR in §5 Non-Functional Requirements:

1. **Logging:** Is SmartLogger called with the documented event name?
2. **Throttling:** Is rate limiting configured as specified?
3. **Security:** Are the documented security measures in place?
4. **Performance:** Are the documented constraints met?

```
For each FR:
  Extract key terms from FR description
  grep -rn "{key_terms}" app/
  Read matched code, compare against FR intent
```

**Findings:**

| ID | Type | Finding |
|----|------|---------|
| R-1 | Spec→Code | FR `{ID}` (`{description}`) has no implementation found in codebase |
| R-2 | Code→Spec | Implementation found for `{behavior}` but no FR covers it in `{spec}` |
| R-3 | Partial | FR `{ID}` is partially implemented: {what's missing} |

#### Area 4: Test Coverage

**Goal:** Verify test files exist for spec'd components and cover key scenarios.

For each Action, Livewire, Entity, and Policy referenced in the spec:

1. **Test file exists?** Check `tests/` for matching test file
2. **Test coverage:** Does the test cover the spec'd behavior?
3. **FR coverage:** Do tests exercise the key FR scenarios?

```
For each spec'd component:
  Check tests/{Module}/{Component}Test.php exists
  Read test file, compare scenarios against spec FRs
```

**Findings:**

| ID | Type | Finding |
|----|------|---------|
| T-1 | Missing | `{ClassName}` has no test file |
| T-2 | Gap | `{ClassName}` test exists but doesn't cover FR `{ID}` ({scenario}) |
| T-3 | Stale | Test references `{old_path/class}` which has been renamed |

#### Area 5: Cross-Reference Integrity

**Goal:** Verify internal spec cross-references are valid.

For each spec in scope:

1. **§9 Roadmap prerequisites:** Do the referenced spec numbers and names match `index.md`?
2. **§9 Roadmap next steps:** Do the referenced spec numbers and names match `index.md`?
3. **Quick References related specs:** Do the referenced spec numbers match?
4. **§1 Problem Statements:** Do referenced specs (e.g., "see `password-reset.md`") exist?
5. **§2 Non-Goals:** Do referenced specs exist?

```
For each cross-reference like [name.md](name.md) (#N):
  grep "^| N |" docs/specs/index.md
  Verify name matches at that number
```

**Findings:**

| ID | Type | Finding |
|----|------|---------|
| X-1 | Broken ref | `{spec}` references `#{N}` (`{name}`) but index has `{actual_name}` at #{N} |
| X-2 | Missing ref | `{spec}` references `{name}` which does not exist in `index.md` |
| X-3 | Wrong number | `{spec}` references `{name.md}` as `#{N}` but it is `#{M}` in index |

#### Area 6: Spec Completeness

**Goal:** Verify the spec itself is well-formed and complete.

1. **§1 Problem Statements:** At least 1 PS present
2. **§2 Goals/Non-Goals:** Goals and Non-Goals both present
3. **§3 User Stories:** At least 1 UC with Actor/Preconditions/Flow/Postconditions
4. **§4 FR:** All FRs have IDs, descriptions, and are testable
5. **§5 NFR:** NFRs present (even if "none applicable")
6. **§6 Contracts:** Action signatures, Livewire components, routes documented
7. **§7 Design Decisions:** At least 1 DD with Decision/Rationale/Trade-off
8. **§8 Metrics:** Success metrics present with targets
9. **§9 Roadmap:** Prerequisites, Build Guide, Next Steps all present
10. **Quick References:** File paths and related specs listed
11. **Metadata:** `Last updated` and `Changes` present

**Findings:**

| ID | Type | Finding |
|----|------|---------|
| S-1 | Missing section | `{spec}` is missing §{section} |
| S-2 | Incomplete | `{spec}` §{section} is incomplete ({detail}) |
| S-3 | Stale metadata | `{spec}` metadata `Last updated` is older than latest commit to referenced files |

#### Area 7: Dependencies

**Goal:** Verify package versions, known vulnerabilities, and dependency health.

1. **Package versions current** — not EOL or deprecated
2. **Known vulnerabilities** — check `composer audit` and `npm audit` output
3. **No pinned dev-only packages in `require`** — belongs in `require-dev`
4. **Tools:** `composer audit`, `npm audit`, `composer outdated`

```
composer audit 2>&1
npm audit 2>&1
composer outdated --direct 2>&1
```

**Findings:**

| ID | Type | Finding |
|----|------|---------|
| D-1 | Vulnerability | `{package}` has known CVE: {advisory_id} (severity: {level}) |
| D-2 | Outdated | `{package}` is {versions_behind} versions behind current |
| D-3 | Misplaced | `{package}` is dev-only but in `require` section |

---

### Phase 3 — Triage & Decision

**Goal:** Classify each finding and determine the correct resolution.

#### 3.1 Classify Each Finding

For each finding from Phase 2, determine:

1. **Drift direction:** Spec→Code, Code→Spec, or Both
2. **Root cause:** Which side changed first?
3. **Correct source of truth:** Which side should be updated?
4. **Severity:** Critical / High / Medium / Low

#### 3.2 Decision Matrix

| Drift Direction | Evidence | Resolution |
|----------------|----------|------------|
| Spec→Code (missing impl) | Spec exists, code doesn't | **Update roadmap** — spec is ahead of code |
| Code→Spec (unspecified) | Code exists, spec doesn't | **Update spec** — code is ahead of spec |
| Contract mismatch (spec older) | Git log shows code changed after spec | **Update spec** to match code |
| Contract mismatch (code older) | Git log shows spec changed after code | **Update code** to match spec (or update spec if behavior is intentional) |
| Broken cross-ref | Wrong number/name in spec | **Fix spec** — trivial fix |
| Missing test | Code exists, no test | **Create GitHub Issue** — test gap |
| Spec incomplete | Section missing/empty | **Update spec** — fill gap from code |
| FR not implemented | Spec FR has no code | **Update roadmap** — track as TODO |

#### 3.3 Severity Classification

| Severity | Criteria |
|----------|----------|
| **Critical** | Spec and code fundamentally disagree on behavior; data integrity risk |
| **High** | Missing implementation for a spec'd FR; broken cross-reference chain |
| **Medium** | Contract signature mismatch; missing test for critical Action |
| **Low** | Stale metadata; minor path typo; missing NFR documentation |

---

### Phase 4 — Fix (Minor) or Issue (Major)

**Goal:** Resolve each finding — either fix directly or create a GitHub Issue.

#### 4.1 Auto-Fix Criteria

Fix directly (no GitHub Issue) when ALL of these are true:

- **Trivial fix:** Takes < 30 seconds to fix
- **No behavior change:** Fix is purely cosmetic or documentary
- **High confidence:** No ambiguity about the correct fix
- **Low risk:** Fix cannot break anything

**Examples of auto-fixable issues:**

| Finding | Fix |
|---------|-----|
| Broken cross-reference number | Update the `(#N)` in spec |
| Stale `Last updated` metadata | Update date and changes line |
| Typo in class name reference | Fix the typo in spec |
| Missing Quick Reference entry | Add the entry |
| Wrong file path in Quick Reference | Update the path |
| Missing §9 Build Guide text | Write brief build guide |
| Spec section empty with obvious content | Fill from code inspection |

#### 4.2 GitHub Issue Criteria

Create a GitHub Issue when:

- **Non-trivial fix** requiring code changes or significant spec rewrites
- **Behavior question:** Spec and code disagree and it's unclear which is correct
- **Missing implementation:** An FR has no corresponding code
- **Missing tests:** A critical Action has no test file
- **Contract violation:** A class violates its architectural contract

#### 4.3 Issue Format

Use the `writing-issues` skill template. Issue type depends on finding:

| Finding Type | Issue Type | Label |
|-------------|-----------|-------|
| Spec out of date (code is correct) | `docs` | `docs`, `spec-audit` |
| Code out of date (spec is correct) | `bug` | `bug`, `spec-audit` |
| Missing implementation | `feature` | `enhancement`, `spec-audit` |
| Missing test | `test` | `test`, `spec-audit` |
| Contract violation | `refactor` | `refactor`, `spec-audit` |
| Behavior disagreement | `bug` or `docs` | `spec-audit` (clarify in body) |

**Issue title format:** `[spec-audit] {type}: {spec_name} — {short description}`

Examples:
- `[spec-audit] bug: authentication.md — LoginAction signature mismatch`
- `[spec-audit] docs: password-reset.md — cross-ref #21 should be #22`
- `[spec-audit] test: account-recovery-slips.md — missing test for GenerateRecoverySlipAction`
- `[spec-audit] refactor: profile-management.md — ProfileEditor violates Livewire contract`

**Issue body must include:**

- **Spec reference:** Which spec, which FR/NFR/section
- **Code reference:** Which file, which class, which line
- **Scope:** Code / Testing / Security / Documentation / Dependencies
- **Violation:** Which rule/pattern is violated (reference doc and section)
- **Drift direction:** Spec→Code or Code→Spec
- **Evidence:** Git log, code inspection, or spec analysis
- **Severity:** Critical / High / Medium / Low
- **Recommendation:** Which side to update and why (brief approach)

---

### Phase 5 — Finalize

**Goal:** Update `docs/roadmap.md`, commit, and push.

#### 5.1 Update Roadmap

Update `docs/roadmap.md` based on findings:

- **Phase status:** If audit reveals a phase has more implementation than tracked, update status
- **Active work:** Add any new findings that need tracking
- **Blockers:** Add any critical findings that block other work
- **Spec count:** Update the spec count in Quick References

#### 5.2 Commit

If any files were modified (auto-fixes, roadmap update):

```bash
git add -A
git commit -m "docs(spec-audit): synchronize specs with implementation

- Auto-fixed: {N} minor issues (cross-refs, metadata, paths)
- GitHub Issues created: {N} (see issue list)
- Roadmap updated: {changes}
- Specs audited: {N} ({scope description})"
```

#### 5.3 Push

Push if the user requested it or if the scope was a full audit:

```bash
git push
```

---

### Phase 6 — Report

**Goal:** Deliver a comprehensive visual report to the user.

#### Report Structure

```markdown
# Spec Audit Report

**Scope:** {scope description}
**Specs audited:** {N}/{total}
**Date:** {date}

---

## Executive Summary

| Metric | Count |
|--------|-------|
| Specs audited | {N} |
| Total findings | {N} |
| Auto-fixed (minor) | {N} |
| GitHub Issues created | {N} |
| Specs fully synced | {N}/{N} ({percent}%) |
| Specs with drift | {N} |

---

## Sync Status by Spec

| Spec | # | Paths | Contracts | Reqts | Tests | X-Refs | Status |
|------|---|-------|-----------|-------|-------|--------|--------|
| authentication.md | 17 | ✅ | ✅ | ⚠️ | ❌ | ✅ | ⚠️ |
| password-reset.md | 21 | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| ... | ... | ... | ... | ... | ... | ... | ... |

Legend: ✅ synced | ⚠️ drift (auto-fixed) | ❌ drift (issue created)

---

## Findings Detail

### Auto-Fixed (Minor)

| # | Spec | Finding | Fix Applied |
|---|------|---------|-------------|
| 1 | spec.md | Wrong cross-ref #30 → #34 | Updated to #34 |
| 2 | spec.md | Stale metadata | Updated date |

### GitHub Issues Created

| # | Issue | Spec | Type | Severity |
|---|-------|------|------|----------|
| 1 | [#NNN](url) | spec.md | Missing test | High |
| 2 | [#NNN](url) | spec.md | FR not implemented | Medium |

---

## Drift Analysis

### Spec-Forward (Code needs to catch up)
- {list of FRs/features spec'd but not implemented}

### Code-Forward (Spec needs to catch up)
- {list of code behaviors not documented in specs}

### Both Stale (Contracts diverged)
- {list of signature/path/name mismatches}

---

## Phase Status Impact

| Phase | Before Audit | After Audit | Change |
|-------|-------------|-------------|--------|
| Phase 3 | {status} | {status} | {delta} |

---

## Recommendations

1. {Priority 1 recommendation}
2. {Priority 2 recommendation}
3. {Priority 3 recommendation}
```

---

## Automation Scripts

Pre-built scripts for efficient auditing. Run from project root.

| Script | What it does | Command |
|--------|-------------|---------|
| `scan_violations.py` | C1-C8, D1-D6 architecture invariant violations | `python3 scripts/scan_violations.py` |
| `scan_class_contracts.py` | Action/Entity/DTO/Model/Enum contract compliance | `python3 scripts/scan_class_contracts.py` |
| `scan_security.py` | XSS, SQL injection, auth gaps, hardcoded secrets | `python3 scripts/scan_security.py` |
| `scan_naming.py` | File, class, method, variable naming conventions | `python3 scripts/scan_naming.py` |
| `scan_architecture.py` | Component counts per module, submodule structure | `python3 scripts/scan_architecture.py` |
| `scan_conventions.py` | strict_types, Fillable, debug calls, hardcoded strings | `python3 scripts/scan_conventions.py` |
| `scan_dead_code.py` | Unregistered observers, unused DTOs, orphan events | `python3 scripts/scan_dead_code.py` |
| `scan_doc_links.py` | Validate all relative links in markdown files | `python3 scripts/scan_doc_links.py` |
| `scan_issues.py` | Fetch GitHub issues, summarize by module/severity | `python3 scripts/scan_issues.py` |

All scripts output to `scripts/outputs/{timestamp}-{description}.json`. Use `--module {Name}` to scope
to a single module. See `scripts/README.md` for full documentation.

---

## Key Rules

1. **Spec is the starting point** — always read the spec first, then verify against code
2. **Bidirectional check** — always check both Spec→Code AND Code→Spec
3. **Evidence-based** — every finding must include file path, line number, and concrete evidence
4. **Decision transparency** — always explain WHY one side should be updated over the other
5. **Minor auto-fix** — only fix trivial issues (typos, cross-refs, metadata); never change behavior
6. **Major → Issue** — non-trivial findings become GitHub Issues with full context
7. **No spec rewriting** — if a spec needs major rewrites, create an Issue; don't rewrite in-place
8. **No code fixing** — if code needs changes, create an Issue; don't modify business logic
9. **Always update roadmap** — reflect audit findings in `docs/roadmap.md`
10. **Always report** — deliver the visual report even if zero findings
11. **Audit every module** — not just the one being changed
12. **Record issues even if fixing is out of scope** — prioritization happens downstream
13. **Do NOT fix issues during audit** — that is the refactoring phase (except minor auto-fix)
14. **Verify findings against actual code** — docs and skills may be stale
15. **Check existing issues before filing** — prevent duplicates

---

## Verification Checklist

- [ ] Scope determined and confirmed
- [ ] All specs in scope read and audit maps built
- [ ] **Code** — All 4 layers audited: UI, Business, Data, Infra
- [ ] Area 1 (Paths): All file paths verified
- [ ] Area 2 (Contracts): All signatures and class declarations verified
- [ ] Area 3 (Requirements): All FR/NFR IDs checked for implementation
- [ ] Area 4 (Tests): All test files checked for existence and coverage
- [ ] Area 5 (Cross-refs): All internal spec references verified
- [ ] Area 6 (Completeness): All spec sections checked for content
- [ ] Area 7 (Dependencies): Package versions and vulnerabilities checked
- [ ] **Testing** — Coverage, structure, mocking conventions checked
- [ ] **Security** — XSS, SQLi, mass assignment, auth, PII, CSP, CSRF checked
- [ ] **Documentation** — Doc-to-code sync verified
- [ ] Findings triaged with decision matrix
- [ ] Minor issues auto-fixed
- [ ] Major issues created as GitHub Issues
- [ ] All findings recorded as GitHub Issues with scope, severity, and fix recommendation
- [ ] No fixes applied during audit (scope discipline, except minor auto-fix)
- [ ] Existing issues checked for duplicates before filing
- [ ] `docs/roadmap.md` updated
- [ ] Changes committed
- [ ] Pushed (if requested or full audit)
- [ ] Visual report delivered to user

---

## Phase Context

| Role | Skill |
|------|-------|
| **Upstream** | `context-awareness` (project orientation), `spec-writing` (spec conventions) |
| **This skill** | **ANALYSIS** — bidirectional spec-implementation audit |
| **Downstream** | `writing-issues` (GitHub Issues), `roadmap-planning` (prioritize), `code-refactoring` (fix code), `sync-docs` (fix docs), `feature-building` (implement gaps) |

---

## References

| Topic | Doc |
|-------|-----|
| Feature specs | `docs/specs/index.md` |
| Spec template | `.agents/skills/spec-writing/SKILL.md` |
| Module structure | `docs/modules/index.md` |
| Architecture & layer rules | `docs/architecture.md` |
| Architecture patterns | `docs/architecture/{pattern}-pattern.md` |
| Action Triad patterns | `docs/architecture/action-pattern.md` |
| Entity-Model separation | `docs/architecture/entity-pattern.md` |
| Model conventions | `docs/architecture/model-pattern.md` |
| Livewire component rules | `docs/architecture/livewire-pattern.md` |
| Exception hierarchy | `docs/architecture/exception-pattern.md` |
| Caching conventions | `docs/architecture/cache-pattern.md` |
| Testing patterns | `docs/architecture/testing-pattern.md` |
| Coding conventions | `docs/conventions.md` |
| Security conventions | `docs/conventions.md` (§3) |
| RBAC & authorization | `docs/foundation/rbac.md` |
| Critical invariants | `AGENTS.md` (§Critical Invariants) |
| Development status | `docs/roadmap.md` |
| Issue writing | `.agents/skills/writing-issues/SKILL.md` |
| Architecture guard | `.agents/skills/arch-guard/SKILL.md` |
| Doc sync | `.agents/skills/sync-docs/SKILL.md` |
