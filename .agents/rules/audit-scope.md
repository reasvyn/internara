# Audit Scope — Verify Every Doc Claim Against Code and Specs

## Intent

When auditing documentation against code and specs, verify each claim a doc makes against its ground
truth: file paths against the filesystem, class names and signatures against the code, schemas
against migrations, and — critically — the **agent guides & skills** (`AGENTS.md`,
`.agents/skills/*/SKILL.md`, `.agents/context/`, `.agents/memory/`, `.agents/plans/`) against the specs they document.

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

### Expanded Sync Areas — Full 408-File Coverage (impact-to-effort ordered)

All `*.md` files are in scope (beyond `scan_doc_links.py`'s 207-file subset). Sync in this order:

| Phase | Area | Files | Impact/Effort | Rationale |
|-------|------|-------|---------------|-----------|
| 1 | **Root Community** — `README.md`, `CONTRIBUTING.md`, `SECURITY.md`, `CODE_OF_CONDUCT.md`, `LICENSE` | 5 | 5/1 Quick win | Entry point for every contributor; smallest, most visible |
| 2 | **Core Docs** — `docs/index.md`, `docs/architecture.md`, `docs/conventions.md`, `docs/doc-template.md`, `docs/getting-started.md`, `docs/philosophy.md`, `docs/project-vision.md`, `AGENTS.md` | 9 | 5/2 | Navigation hub; all other docs depend on these |
| 3 | **Guides** — `docs/guides/*`, `docs/guides/arch/*`, `docs/guides/infra/*` | ~48 | 4/3 | Operational & pattern docs; medium reach |
| 4 | **Specs** — `docs/specs/*` (64) | 64 | 5/5 Strategic | Authoritative source; high impact high effort |
| 5 | **ADR** — `docs/adr/*` | 17 | 3/2 | Decision history; stable after rewrite |
| 6 | **Refs** — `docs/refs/modules/*`, `docs/refs/deps/*`, `docs/refs/index.md` | ~56 | 2/2 Fill-in | Reference tier; lowest human urgency |
| 7 | **Agent Layer** — `.agents/rules/*`, `.agents/context/*`, `.agents/memory/*`, `.agents/audit/*`, `.agents/skills/*`, `.opencode/agents/*`, `tools/README.md` | ~212 | 2/4 Deferred | Internal; human docs are SSOT, agents reference them |

Phases 1-3 are quick wins → execute first. Phase 4 is strategic → split if needed. Phases 6-7 are last per explicit owner direction (`docs/refs/` and `.agents/` synchronized last).

### Documentation Audit Scope

Verify these items against actual code and specs (applies to every phase above):

- File paths in docs point to existing files.
- Class names and method signatures match actual code.
- Action listings include all `execute()` methods.
- Enum values include all cases.
- No broken relative links.
- History is tracked via git history, not inline metadata.
- Module structure docs match actual `app/` directory layout.
- **Agent guides, skills, context & memory match specs and code:**
  - Spec IDs referenced in `AGENTS.md` and `.agents/skills/*/SKILL.md` exist in
    `docs/specs/index.md`.
  - Invariant values (names, config defaults, convention IDs C1-C8 / D1-D6) match the governing spec.
  - "where to find" tables point to sections that actually exist.
  - Skill scope covers what the governing spec promises.
  - Mandatory facts live in `.agents/context/`; evolving learnings in `.agents/memory/` — no cross-pollution.

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
find app/Modules/Enrollment/Actions -name '*Action.php'          # ground truth: the files
grep -c '^\|' docs/refs/modules/enrollment-reference.md       # claimed count — must match

# Claim: "spec ID SE5Q9 exists"
grep -r "SE5Q9" docs/specs/index.md                      # ground truth: the spec index

# Claim: "convention IDs C1-C8 / D1-D6"
grep -c "C1\|C8\|D1\|D6" .agents/skills/*/SKILL.md       # must match docs/conventions.md
```

## Anti-Patterns & Pitfalls

- **Auditing only links and metadata.** `scan_doc_links.py` validates links and freshness — it does
  NOT check that a listed Action exists, that a signature matches, or that a skill documents the
  right spec. A doc with zero broken links can still be completely stale.
- **Skipping the agent layer.** `AGENTS.md`, `.agents/skills/*/SKILL.md`, `.agents/context/`, `.agents/memory/`, and
`.agents/plans/` are docs too and rot exactly like `docs/`. An invariant renamed in
`docs/conventions.md` but not in the skills that cite it leaves agents enforcing a dead name.
- **Auditing claims in a doc you're not touching.** If the audit is triggered by one module, still
  spot-check the cross-references that module's docs point to — a renamed target breaks them
  silently.
- **Believing a doc because it's "authoritative".** Conceptual docs carry business rules; reference
  docs carry paths. Both can be stale — the code and the spec are the ground truth for this audit.

## Verification / Detection

- `python3 tools/scan_doc_links.py` — catches broken links and stale metadata, the cheapest
  signal of rot (necessary but not sufficient).
- Manual/grep checks per claim class: `find` against `app/` for file listings, `grep` of class
  signatures, migration files vs `docs/guides/infra/database.md`.
