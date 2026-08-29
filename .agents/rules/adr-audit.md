# ADR Audit — Metadata, Linkage & Staleness

Architecture Decision Records (`docs/adr/*.md`) are the durable memory of why the system is shaped the way it is. A missing, stale, or unlinked ADR is a quality failure — it leaves future agents and reviewers without rationale.

---

## What it enforces

Every ADR must satisfy:

- **Structure present:** `## Description` first, `## Quick References` last, plus `## Description`, `## Context`, `## Decision`, `## Consequences`.
- **Indexed:** Listed in `docs/adr/index.md` under the correct section (Foundation / Observability / Quality / Strategy / Proxy). No orphan ADR file, no index entry without a file.
- **Linked to code:** Decision section references the actual code locations it governs (`docs/guides/arch/*.md`, `docs/conventions.md`, `app/Modules/Core/*`, module paths). A grep for the ADR's key terms (e.g., `Action-based MVC`, `Entity-Model Separation`) must find code/doc anchors — otherwise the ADR is stale or the code drifted.
- **Freshness:** `git log --follow -- <adr-file>` is within 6 months if the governed code changed (`git log -- docs/adr/<file> -- app/...` correlation). A decision whose code moved without ADR update is flagged stale.
- **No duplicate decisions:** One decision per ADR. Overlapping rationale across ADRs must be deduplicated via cross-reference, not copy-pasted.
- **Decision coverage:** Any non-trivial architectural invariant (C1-C8, D1-D6, module boundaries, tenant mode) must have a corresponding ADR; arch-guard scans `docs/guides/arch/*.md` for invocations of invariants and verifies an ADR exists.

## Why it matters

ADRs are the only place that records *why* an alternative was rejected. Without them, refactors re-litigate settled debates; with stale ADRs, code and rationale diverge and agents follow the wrong source.

## How to apply

- On any architectural change (new pattern, module boundary, exception hierarchy), create or update the governing ADR *before* changing code (documentation-first, same as specs).
- When auditing, run `python3 tools/scan_adr.py` (or `scan_doc_links.py` which checks ADR index ↔ files and ADR ↔ architecture doc links). Verify:
  - `docs/adr/index.md` lists exactly the files in `docs/adr/*.md` (no orphans).
  - Each ADR's git history (`git log --follow -- <file>`) is not older than its governed code's last commit.
  - Each ADR's `Decision` section contains at least one concrete path/pattern reference that exists in the repo.

## Pitfalls to avoid

- Creating `docs/adr/adr-new-thing.md` without adding it to `docs/adr/index.md` — invisible decision.
- Copy-pasting an ADR's `Decision` block into `docs/guides/arch/*.md` instead of cross-referencing — duplication drifts.
- Leaving ADR without a commit after amending the Decision — freshness check fails.

## Verification

- `python3 tools/scan_adr.py` clean (or `scan_doc_links.py` with ADR checks) — no orphan, stale, or unlinked ADR.
- `grep -R "adr-" docs/guides/arch/*.md docs/conventions.md` references resolve to existing ADR files.
- Every ADR's `Decision` paths exist (`ls` check) and its git history (`git log --follow -- <file>`) correlates with recent code changes.
