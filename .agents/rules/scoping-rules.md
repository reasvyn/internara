# Scoping Rules — One Initiative, One Spec

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

A spec file must cover exactly **one initiative** — a cohesive, independently deliverable unit of work
with a clear boundary. When a spec tries to cover two capabilities, its requirements, tests, and
deployment decisions tangle: you can't ship half, you can't test one without the other, and the file
grows unbounded. Scoping rules keep each spec atomic.

---

## One Initiative = One Spec

An initiative has a clear boundary: it can be planned, implemented, tested, and shipped without
requiring changes outside its scope.

**How to decide if something is one initiative or two:**

| Signal | One spec | Two specs |
|--------|----------|-----------|
| Implementation | Single PR or tightly coupled PRs | Independent PRs |
| Testing | Shared test suite, same mock boundaries | Separate test suites |
| Deployment | Deploy together or not at all | Can deploy independently |
| Rollback | Rollback means rollback both | Each can rollback alone |
| Team ownership | One person/team owns it | Different people/teams own it |

**Why this matrix matters:** the signals are concrete engineering consequences, not vibes. If two
capabilities pass the deployment and rollback tests independently, they are two initiatives and belong
in two specs — even if the code touches the same module.

**Rule of thumb:** If you can describe the feature without using "and" between two independent
capabilities, it belongs in one spec. If the spec has sections that are only relevant to one
capability, split it.

---

## When a Spec Grows Too Large, Split It

1. **Identify distinct user-facing capabilities** (e.g., "CLI install" vs "browser wizard")
2. **Each capability gets its own** Problem Statements, Goals, Use Cases, and Requirements
3. **Shared infrastructure** (data models, config) goes into the more foundational spec
4. **Cross-reference related specs** instead of duplicating content

**How to apply the split (see also `spec-indexing.md` for index mechanics):**

- When splitting, each new spec gets its own §9 Roadmap referencing the sibling specs
- The Description block states the split provenance: `"split from {ID}-{original}.md"`
- Non-Goals in each new spec explicitly list capabilities that moved to siblings
- Quick References in both files cross-reference the sibling specs (relative links)
- Dependencies: if spec A is split from spec B, the new spec inherits B's dependencies

**Failure mode if ignored:** a mega-spec with "CLI" and "browser wizard" sections where the wizard
half never ships because every change to the CLI half requires re-review of the whole document, and
tests cannot target one capability.

---

## Cross-Cutting: Scoping Signals Per Section Type

Beyond the one-initiative rule, each section has its own scoping disciplines (detailed rules in
`section-content.md`); the scoping signals are:

| Section | Scoping rule |
|---------|--------------|
| Problem Statements | Each PS is one problem with its consequences; group symptoms of one root cause |
| Goals / Non-Goals | Non-Goals are explicit out-of-scope boundaries against creep |
| Use Cases | Primary path + important alternatives; named error/edge flows when important |
| Functional Requirements | Atomic — one requirement per line; `must`/`should`/`may` strength |
| Roadmap | Only direct dependencies and direct downstream specs, not transitive chains |

---

## Verification / Detection

Ask about any spec:

- Can it be described without "and" joining independent capabilities?
- Do any sections apply to only part of the spec? If yes → split candidate.
- Would implementation be one PR (or tightly-coupled PRs) — or two independent PRs? Independent → split.
- Could each hypothetical half be rolled back alone? Yes → split.

If any answer points to two initiatives, follow the split procedure rather than extending the file.