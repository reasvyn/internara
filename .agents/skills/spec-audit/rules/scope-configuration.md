# Scope Configuration — Flexible Audit Scope & Work Channels

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

`spec-audit` is deliberately scope-flexible: it audits by single spec, by work scope, by module, by
phase, by audit area, by agent-guides surface, or everything at once. The scope choice changes what is
checked, how the report is structured, and how the work is staged. Getting the scope wrong wastes a
full-corpus audit on a one-spec question — or silently narrows a full audit until drift is missed.

---

## Scope Options

| Scope | What It Audits | Example |
|-------|---------------|---------|
| **Single spec** | One spec against its implementation | `spec-audit authentication.md` |
| **Work scope** | One spec across all three work channels — **implementation, testing, documentation** | `spec-audit --work D2FT3` |
| **Module** | All specs for a module | `spec-audit --module Auth` |
| **Phase** | All specs in a lifecycle phase | `spec-audit --phase 3` |
| **Audit area** | Specific dimension across all/some specs | `spec-audit --area contracts` |
| **Agent guides & skills** | `AGENTS.md`, `.agents/skills/*/SKILL.md`, `.agents/context/`, `.agents/plans/` against the specs they reference | `spec-audit --guides` |
| **Full audit** | All specs, all areas + agent guides & skills | `spec-audit --all` |

**Why scope flexibility exists:** audits cost time and context. A single-spec audit (S-size) runs in
one pass; a module audit (M) is staged by area; a full audit (L) MUST be split into sessions. The
scope controls both what you verify and whether the work is legal to run in one pass (Size Triage).

---

## Audit Areas

| Area | What It Checks |
|------|---------------|
| `paths` | File paths in spec Quick References exist in codebase |
| `contracts` | Method signatures, class names, DTOs, Entity contracts match |
| `requirements` | FR/NFR IDs have corresponding implementation |
| `tests` | Test files exist for spec'd components; the spec's suite runs and passes; test gaps are filled in-run |
| `coverage` | Spec'd features are actually implemented (not just stubs) |
| `cross-refs` | Internal spec cross-references are correct (names, spec IDs) |
| `guides` | Agent guides & skills are consistent with specs (Area 8) |
| `all` | All areas combined (default for full audit) |

**Why each area matters:** each catches a distinct failure mode. `paths` catches renamed/moved files;
`contracts` catches signature drift; `requirements` catches unimplemented FRs; `tests` catches
untested/failing spec'd behavior; `coverage` catches stub features that "exist" but do nothing;
`cross-refs` catches broken internal links; `guides` catches agents being trained on stale rules.

---

## Work Scope — Implementation, Testing & Documentation

A **work scope** audits one spec across the three work channels that deliver and sustain it. Each
channel maps to specific audit areas:

| Channel | What It Audits | Audit Areas |
|---------|----------------|-------------|
| **Implementation** | The spec's FR/NFR/contracts are actually delivered in code (`app/`, `routes/`, `database/`, `config/`) — no missing, stubbed, or drifted behavior | `paths`, `contracts`, `requirements`, `coverage` |
| **Testing** | Every spec'd component has a test file and every FR/NFR is exercised by a spec-traceable test — no orphan tests, no spec gaps. The spec's test suite is **run**; any spec'd component or FR/NFR without a test is **written now** as part of the audit | `tests` (+ cross-check each test name against FR/NFR IDs, run the suite) |
| **Documentation** | Docs reflect the spec and code: Quick Reference paths exist, cross-refs are valid, `docs/architecture/*` and `docs/modules/*` match the spec's contracts, and **agent guides & skills** stay consistent with the spec | `cross-refs`, `paths`, `guides`, §6 completeness + doc-to-code sync |

**Why the three channels exist:** a spec can be fully implemented but untested, or tested but
mis-documented. Reporting a flat finding list hides *which channel* is broken. The work scope delivers
a **per-channel verdict** (Implementation / Testing / Documentation), each with its own findings and
decision-matrix outcome — so the maintainer knows the spec is, e.g., "implemented ✅, tested ⚠️,
documented ❌".

**How to apply:** for a work scope, group the areas into the three channels and run them in order
(Implementation → Testing → Documentation). Tag findings with their channel so triage and the report
stay per-channel.

---

## Default Scope

If no scope is specified:

1. If recent git commits touched specific modules → audit those modules
2. If GitHub Issues have "Active Work" labels → audit those specs
3. Otherwise → prompt user for scope

**Why this default order:** the audit should follow where work is actually happening (commits) or
being tracked (active-work issues); a random full audit on a quiet repo wastes effort. If neither
signal exists, the user must choose — never guess.

---

## Size Triage

Classify the audit scope per `agent-workflow` Size Triage **before** auditing:

| Scope | Size | Execution |
|-------|------|-----------|
| Single spec | S | Single pass |
| Work scope (3 channels) | M | Single session, staged by channel (implementation → testing → documentation) |
| Module / phase / area | M | Single session, staged by area |
| Full audit (`--all`) | **L** | **Split into sessions** — inform the user, propose a plan (e.g., by phase), audit session by session |

**Never run a full `--all` audit in a single pass** — split by lifecycle phase, verify each phase's
findings, then combine the report. This is the same L-size protocol as every other skill.

---

## Anti-Patterns / Pitfalls

- **Running a full audit for a single-spec question** — burns hours and context for a one-spec answer.
- **Narrowing a full audit silently** — if the user asked for `--all`, audit all areas; silently
  skipping `guides` or `dependencies` under-reports.
- **Treating a work scope as a flat single-channel audit** — the per-channel verdict is the point of
  the work scope.
- **Skipping size triage** — an L-size full audit in one pass produces an incomplete report and
  violates the L-size protocol.

---

## Verification / Detection

- Scope determined and confirmed with the user (or inferred with a stated rationale).
- Audit map built per spec in scope (spec ID, phase, module, referenced files/classes/routes,
  FR/NFR IDs, cross-refs, prerequisites/next steps).
- For a work scope: all three channels audited and reported per-channel.
- For full audit: sessions split by lifecycle phase, each verified, then combined.