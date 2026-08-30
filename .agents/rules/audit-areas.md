# Audit Areas — The 8 Checks in Order

Phase 2 of the audit executes eight distinct areas against each spec's audit map, in order. Each area
has a goal, a method, and typed findings. Running areas in order keeps findings non-overlapping and
the audit exhaustive — skipping an area silently under-reports. This rule documents Areas 1-8 with
their findings and intent.

---

## Discovery First (Phase 1)

Before any area runs, build the audit map for each spec in scope:

1. **Read the spec** — full content
2. **Extract references** from Quick References + §6 API/Data Contracts: file paths, class names,
   method signatures, routes, migrations, event/listener classes, policy classes, test files
3. **Extract FR/NFR IDs** and descriptions
4. **Extract §9 Roadmap references** (prerequisites, next steps)
5. **Extract §6 contracts** — class signatures, DTOs, Entities

```json
{
  "spec": "YB7RG-authentication.md",
  "spec_id": "YB7RG",
  "phase": 3,
  "module": "Auth",
  "referenced_files": [...],
  "referenced_classes": [...],
  "referenced_routes": [...],
  "fr_ids": [...],
  "nfr_ids": [...],
  "cross_refs": [...],
  "issues_prereqs": [...],
  "issues_next": [...]
}
```

**Why the audit map matters:** it is the single list of everything the audit must verify. Without it,
each area re-reads the spec or — worse — verifies only what it happens to remember.

---

## Area 1: Path Verification

**Goal:** verify every file path referenced in the spec actually exists.

For each file path in Quick References and §6:

1. Check the file exists at the referenced path
2. If missing: check if it was renamed (search by class name in `app/`)
3. If still missing: record as **Spec→Code drift** (spec references non-existent code)

**Reverse check:** if code exists in the expected module directory but is NOT referenced in the spec,
record as **Code→Spec drift** (unspecified implementation).

```
Check: file_exists($path)
Search: grep -rn "class {ClassName}" app/ (if file missing)
```

**Findings:** `P-1` Spec→Code (`{spec}` references `{path}` which does not exist) ·
`P-2` Code→Spec (`{path}` exists but is not documented in `{spec}`).

**Why it matters:** a renamed file that the spec still points at breaks every reader and the
implementation trace. The reverse check catches whole files that shipped without ever being spec'd.

---

## Area 2: Contract Verification

**Goal:** verify method signatures, class names, DTOs, and Entity contracts match between spec and
code.

For each class referenced in §6:

1. **Class existence** — does the class exist at the referenced path?
2. **Class declaration** — `final readonly` (Entity/DTO)? `extends BaseCommandAction` (Action)?
3. **Method signature** — does `execute()` match the spec'd signature (param types, return type)?
4. **DTO contract** — extends `BaseData`? properties scalar/enum/Carbon only?
5. **Entity contract** — has `fromModel()`? `final readonly`?
6. **Model contract** — `#[Fillable]`? extends `BaseModel`?

```
grep -n "class {ClassName}" {path}
grep -n "public function execute" {path}
# Compare signature against spec §6
```

**Findings:** `C-1` Spec→Code (spec documents signature `{spec_sig}` but code has `{code_sig}`) ·
`C-2` Code→Spec (class has `{code_sig}` but spec documents `{spec_sig}`) ·
`C-3` Spec→Code (spec promises `{ClassName}` which does not exist) ·
`C-4` Contract (`{ClassName}` violates {Entity|DTO|Model} contract).

**Why it matters:** contracts are the agreement implementers and tests build against; a drifted
signature makes tests fail mysteriously and callers crash at runtime. `arch-guard` is the reference
for the architectural contract shapes (final readonly, BaseData, purity).

---

## Area 3: Requirements Coverage

**Goal:** verify every FR/NFR ID has corresponding implementation.

For each FR in §4:

1. **Search for implementation** — grep key terms from the FR description in `app/`
2. **Verify behavior** — read the found code and compare against the FR's intent
3. **Check completeness** — are all conditions in the FR satisfied?

For each NFR in §5:

1. **Logging** — is SmartLogger called with the documented event name?
2. **Throttling** — is rate limiting configured as specified?
3. **Security** — are the documented security measures in place?
4. **Performance** — are the documented constraints met?

```
grep -rn "{key_terms}" app/
# Read matched code, compare against FR intent
```

**Findings:** `R-1` Spec→Code (FR `{ID}` has no implementation found) ·
`R-2` Code→Spec (implementation for `{behavior}` but no FR covers it) ·
`R-3` Partial (FR `{ID}` partially implemented: {what's missing}).

**Why it matters:** FRs are the contract for behavior; an FR with no code is an unimplemented promise,
and code with no FR is undocumented behavior. `R-3` Partial matters because "it exists" is not "it
works" — grep finding a class is not the same as the FR's conditions being met.

---

## Area 4: Test Coverage — and the mandate to RUN tests and WRITE missing ones

**Goal:** verify test files exist for spec'd components, cover key scenarios, **and pass**. If any
spec'd component or FR/NFR has no test, **write the spec-traceable tests now** — test gaps are closed
as part of the audit (Test-Gap Fill Rule), not filed as issues.

For each Action, Livewire, Entity, and Policy referenced in the spec:

1. **Test file exists?** — check `tests/` for a matching test file
2. **Test coverage** — does the test cover the spec'd behavior?
3. **FR coverage** — do tests exercise the key FR scenarios?
4. **Run the spec's tests** — execute the audited spec's suite and confirm it passes

```
Check tests/{Module}/{Component}Test.php exists
Read test file, compare scenarios against spec FRs
vendor/bin/pest --testsuite={Module}  # run the spec's tests (targeted filter if available)
```

**Findings:** `T-1` Missing ({ClassName} has no test file — **test written now**) ·
`T-2` Gap (test exists but doesn't cover FR `{ID}` — **test extended now**) ·
`T-3` Stale (test references `{old_path/class}` which has been renamed) ·
`T-4` Failing ({ClassName} test suite fails: {failure detail}).

**Why Area 4 mandates running and writing tests:** a spec'd component "having a test file" is not
enough — the file could be stale, orphaned, or failing. Running the suite surfaces `T-4`; running the
cross-check against FR/NFR IDs surfaces `T-2`. And because the audit's purpose is to leave the spec
fully verified, missing tests are **written in-run** per `pest-testing` conventions
(`describe("{SpecID}: Test description...")` + `test("{SpecID}-{ReqID}: Test description...")`),
then run and confirmed passing. Writing tests is the audit's Test-Gap Fill work — it is NOT the
forbidden "code fixing" (see `fix-or-issue.md`).

---

## Area 5: Cross-Reference Integrity

**Goal:** verify internal spec cross-references are valid.

1. **§9 Roadmap prerequisites** — do the referenced spec IDs and names match `index.md`?
2. **§9 Roadmap next steps** — same check
3. **Quick References related specs** — do the referenced spec IDs match?
4. **§1 Problem Statements** — do referenced specs (e.g., "see `3UOZP-dummy-data.md`") exist?
5. **§2 Non-Goals** — do referenced specs exist?

```
grep "^| {ID} |" docs/specs/index.md   # verify name matches at that ID
```

**Findings:** `X-1` Broken ref ({spec} references {ID} but index has {actual_name}) ·
`X-2` Missing ref ({spec} references {name} which does not exist in index.md) ·
`X-3` Wrong ID ({spec} references {name}.md as {ID} but it is {ID2} in index).

**Why it matters:** broken cross-refs break the dependency graph the roadmap and indexing rely on; a
stale `{ID}` reference misleads anyone navigating by the index. These are the cheapest auto-fixable
findings.

---

## Area 6: Spec Completeness

**Goal:** verify the spec itself is well-formed and complete.

1. §1 Problem Statements — at least 1 PS present
2. §2 Goals/Non-Goals — both present
3. §3 User Stories — at least 1 UC with Actor/Preconditions/Flow/Postconditions
4. §4 FR — all FRs have IDs, descriptions, are testable
5. §5 NFR — NFRs present (even "none applicable")
6. §6 Contracts — Action signatures, Livewire components, routes documented
7. §7 Design Decisions — at least 1 DD with Decision/Rationale/Trade-off
8. §8 Metrics — success metrics present with targets
9. §9 Roadmap — Prerequisites, Build Guide, Next Steps all present
10. Quick References — file paths and related specs listed
11. Structure — `## Description` first, `## Quick References` last; history via git

**Findings:** `S-1` Missing section · `S-2` Incomplete ({detail}) ·
`S-3` Stale metadata (metadata older than latest commit to referenced files).

**Why it matters:** a spec that fails §6.5 checks is not implementation-ready; the audit catches it
before `feature-building` does, not after. `S-3` flags specs whose metadata has not tracked the code
they reference — a low-severity but accurate freshness signal.

---

## Area 7: Dependencies

**Goal:** verify package versions, known vulnerabilities, and dependency health.

1. **Package versions current** — not EOL or deprecated
2. **Known vulnerabilities** — check `composer audit` and `npm audit` output
3. **No pinned dev-only packages in `require`** — belongs in `require-dev`
4. **Tools:** `composer audit`, `npm audit`, `composer outdated`

```
composer audit 2>&1
npm audit 2>&1
composer outdated --direct 2>&1
```

**Findings:** `D-1` Vulnerability ({package} has known CVE: {advisory_id}, severity {level}) ·
`D-2` Outdated ({package} is {versions_behind} behind) · `D-3` Misplaced ({package} is dev-only but
in `require`).

**Why it matters:** dependency drift and CVEs are silent until exploited; the audit surfaces them on
the same schedule as code drift so security debt is tracked like any other finding.

---

## Area 8: Agent Guides & Skills

**Goal:** verify agent guides & skills stay consistent with the specs they reference, so every
document (specs, code docs, and agent guides) agrees.

**Audit surface:** `AGENTS.md`, `.agents/skills/*/SKILL.md`, `.agents/context/*.md`, `.agents/memory/*.md`, `.agents/plans/`.

For each guide/skill that references a spec, module, invariant, or config value:

1. **Spec references exist** — referenced spec IDs and names exist in `docs/specs/index.md`
2. **Invariant values match** — hardcoded names, config defaults, rule values match the governing
   spec (e.g., super admin name, module names, C1-C8/D1-D6)
3. **Rule locations resolve** — "where to find" tables point to sections that actually exist
4. **Skill scope covers spec** — if a skill claims a scope, it includes every channel the spec's
   §6/§9 promises
5. **Stale rule values** — a spec amendment (e.g., renamed default) must be mirrored in the guide
   that documents it — otherwise the guide is **Code → Spec** drift (guide lagging behind spec)

```
grep -n "docs/specs/|spec ID|invariant value" .agents/skills/{skill}/SKILL.md AGENTS.md
# Verify each reference against docs/specs/index.md and the governing spec
```

**Findings:** `G-1` Code→Spec ({guide} documents {value} but the governing spec defines {spec_value})
· `G-2` Broken ref ({guide} references {spec}/{doc} §{section} which does not exist) ·
`G-3` Stale scope ({skill} scope omits a channel the governing spec promises) ·
`G-4` Missing mirror ({spec} amended {value} but {guide} still documents the old value).

**Why it matters:** agents act on what the guides say. A stale invariant value in a skill makes every
future agent enforce the wrong rule; a guide lagging a spec amendment propagates drift at scale. This
area closes the documentation loop.

---

## Anti-Patterns / Pitfalls

- **Area 4 without running the suite** — "test files exist" is not "tests pass"; `T-4` is a finding
  the audit must surface.
- **Area 4 without writing missing tests** — closing test gaps is the audit's mandated deliverable,
  not deferred work (Test-Gap Fill Rule).
- **Area 3 satisfied by a grep hit** — a matched keyword is not implemented behavior; read the code
  and verify the FR's conditions (the `R-3` Partial trap).
- **Skipping the reverse checks** (`P-2`, `R-2`, `C-2`) — Code→Spec drift is the silent accumulation;
  the bidirectional check is the point of this skill.
- **Area 8 skipped** — agent guides drift as fast as specs; the guides surface is part of the audit
  scope, not an optional extra.

## Verification / Detection

- Audit maps built for every spec in scope before any area runs.
- Areas 1-8 executed in order; each produced its typed findings.
- Area 4: the spec's suite ran and passed; any missing spec'd-component test written in-run
  (`{SpecID}-{ReqID}` naming) and passing before the audit closes.
- Area 8 covered AGENTS.md + all skills + contexts/plans relevant to the audited specs.
- Findings tagged with their channel for work-scope reports.
