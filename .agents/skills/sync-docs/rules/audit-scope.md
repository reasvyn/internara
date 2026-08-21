# Audit Scope — Verify Every Doc Claim Against Code and Specs

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

## Intent

When auditing documentation against code and specs, verify each claim a doc makes against its ground
truth: file paths against the filesystem, class names and signatures against the code, schemas
against migrations, and — critically — the **agent guides & skills** (`AGENTS.md`,
`.agents/skills/*/SKILL.md`, `.agents/context/`, `.agents/plans/`) against the specs they document.

## Rationale

A doc is a bundle of claims: "`X` file exists", "`Y` class has method `z()`", "module `M` has N
Actions", "enum `E` has these cases". Each claim is either true or false right now. The audit exists
because docs accumulate claims and stop being checked. The failure mode of skipping an audit is
quiet rot: every stale claim teaches readers (humans and agents) that the docs lie, until nobody
trusts them — and then the whole documentation system is dead weight.

Two classes of claim rot are especially dangerous because they are silent:
- **Deletion/rename rot** — a doc that keeps a removed feature or a renamed class alive. It looks
  like a valid reference but describes something that no longer exists.
- **Unimplemented-promise rot** — a doc that promises a feature the code never shipped. It creates
  false expectations and reads like a roadmap, not a reference.

## How to Apply

### Documentation Audit Scope

Verify these items against actual code and specs:

- File paths in docs point to existing files.
- Class names and method signatures match actual code.
- Action listings include all `execute()` methods.
- Enum values include all cases.
- No broken relative links.
- Metadata (`Last updated`, `Changes`) present on every `.md` file.
- Module structure docs match actual `app/` directory layout.
- **Agent guides & skills match specs and code:**
  - Spec IDs referenced in `AGENTS.md` and `.agents/skills/*/SKILL.md` exist in
    `docs/specs/index.md`.
  - Invariant values (names, config defaults, convention IDs C1-C8 / D1-D6) match the governing spec.
  - "where to find" tables point to sections that actually exist.
  - Skill scope covers what the governing spec promises.

### Verify Documentation Accuracy

For each doc, check:

- File paths exist and are correct.
- Class names match actual code.
- Method signatures match implementation.
- Action listings include all `execute()` methods.
- Enum values include all cases.
- Model relationships match actual Eloquent definitions.
- Migration schemas match database tables.
- Dependency graphs reflect actual imports.

## Examples

```bash
# Claim: "Actions table lists every Action in the module"
find app/Enrollment/Actions -name '*Action.php'          # ground truth: the files
grep -c '^\|' docs/modules/enrollment-reference.md       # claimed count — must match

# Claim: "spec ID SE5Q9 exists"
grep -r "SE5Q9" docs/specs/index.md                      # ground truth: the spec index

# Claim: "convention IDs C1-C8 / D1-D6"
grep -c "C1\|C8\|D1\|D6" .agents/skills/*/SKILL.md       # must match docs/conventions.md
```

## Anti-Patterns & Pitfalls

- **Auditing only links and metadata.** `scan_doc_links.py` validates links and freshness — it does
  NOT check that a listed Action exists, that a signature matches, or that a skill documents the
  right spec. A doc with zero broken links can still be completely stale.
- **Skipping the agent layer.** `AGENTS.md`, `.agents/skills/*/SKILL.md`, `.agents/context/`, and
  `.agents/plans/` are docs too and rot exactly like `docs/`. An invariant renamed in
  `docs/conventions.md` but not in the skills that cite it leaves agents enforcing a dead name.
- **Auditing claims in a doc you're not touching.** If the audit is triggered by one module, still
  spot-check the cross-references that module's docs point to — a renamed target breaks them
  silently.
- **Believing a doc because it's "authoritative".** Conceptual docs carry business rules; reference
  docs carry paths. Both can be stale — the code and the spec are the ground truth for this audit.

## Verification / Detection

- `python3 scripts/scan_doc_links.py` — catches broken links and stale metadata, the cheapest
  signal of rot (necessary but not sufficient).
- Manual/grep checks per claim class: `find` against `app/` for file listings, `grep` of class
  signatures, migration files vs `docs/infrastructure/database.md`.
- `scan_skills.py` — cross-check skill metadata consistency, complementary to the manual agent-layer
  audit.
