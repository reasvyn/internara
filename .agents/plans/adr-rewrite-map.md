# ADR Rewrite Map — MADR-lite Migration Plan

> **Last updated:** 2026-08-25 **Changes:** dropped sequential numbering per owner decision — ADRs identified by slug only; plan realigned

## Description

Working plan for rewriting all 14 ADRs to industry-standard **MADR-lite** format. One session =
one ADR. This file is the session-to-session handoff artifact: each rewrite session reads the
target format below plus its own row, executes, and commits as a checkpoint.

---

## De-numbering Decision

ADRs carry **no sequential numbers** (`ADR-014: …` → `# Cross-Role Proxy`). Rationale: numbers
imply a fixed ordering that fights reordering flexibility and have already drifted in practice
(e.g., `docs/architecture.md` labels cross-module-communication as "ADR-010"; it was 011).

- Filenames KEEP the `adr-` prefix (`adr-cross-role-proxy.md`) — zero inbound-link churn.
- Chronology lives in the metadata table (`Date` field) and git history, not in identifiers.
- Every rewrite session must also fix outbound numeric mentions pointing AT its ADR:

```bash
rg -n "ADR-[0-9]{3}" --glob '*.md' | grep "<slug>"
```

Known numeric mentions awaiting cleanup (fix in the owning session):

| Slug | Numeric mentions outside adr/ |
|------|-------------------------------|
| cross-role-proxy | QLHDO spec, T4B26 spec ×2, journals/reports/assessment module docs, guides/rbac, modular-pattern, conventions.md |
| flat-rbac-with-functional-roles | T4B26 spec metadata, policy-pattern |
| action-pattern-over-services | service-pattern |
| cross-module-communication | architecture.md (wrong number!) |
| gradual-migration | scaling guide |
| self-hosted-single-tenant, performance-optimization | scaling guide |
| entity-model-separation | modular-pattern |

## Target Format — MADR-lite (locked)

```markdown
# {Title}

> **Last updated:** YYYY-MM-DD **Changes:** rewrite to MADR-lite industry-standard format

| Field | Value |
|-------|-------|
| Status | Accepted |
| Deciders | {name} |
| Date | {original decision date, else YYYY-MM-DD} |
| Technical Story | {spec/issue reference} |

## Context and Problem Statement

{Problem and forces. Ends with an explicit **Decision Drivers** list.}

## Considered Options

- **{Option A}**
- **{Option B}**
- …

## Decision Outcome

**Chosen option: {X}** — {justification paragraph}

### Positive Consequences

### Negative Consequences

## Links

{≥ 2 outbound markdown links — specs, patterns, module docs.}
```

Rules: preserve title meaning · Status stays `Accepted` unless superseded · every backtick path
becomes a real markdown link · optional domain sections (Coverage Map, Replaces, Comparison) stay
after Links · numbering stripped per De-numbering Decision · footer renamed `References`→`Links`.

## Stage 0 — Template Lock-in (this session)

`adr-template.md` updated to the MADR-lite skeleton before any rewrite begins (document-first:
template describes target state).

## Inventory & Gaps

| Slug | Words | Inbound cites | Gaps |
|------|-------|---------------|------|
| uuid-primary-keys | 477 | 0 | no status/options/links |
| action-based-mvc-architecture | 487 | 0 | no status/options/links |
| action-pattern-over-services | 590 | 1 | no status/links |
| entity-model-separation | 453 | 1 | no status/options/links |
| smartlogger-dual-channel | 593 | 0 | no status/options/links |
| base-class-mandate | 574 | 0 | no status/links |
| exception-hierarchy | 521 | 0 | no status/links |
| flat-rbac-with-functional-roles | 728 | 2 | no status/links |
| performance-optimization | 676 | 1 | no status/options/links |
| self-hosted-single-tenant | 589 | 1 | no status/options/links |
| cross-module-communication | 432 | 2 | no status/options/links |
| gradual-migration | 728 | 3 | no status/options/links |
| program-closure-archival | 601 | 0 | no status/options/links |
| cross-role-proxy | 1906 | 8 | no status/links (has comparison content) |
| eloquent-observers | 551 | 0 | no status/options/links |

## Ordered Session Plan (impact-to-effort applied)

Pass 1 dependencies: template first; action-based-mvc anchors the architecture cluster;
flat-rbac precedes cross-role-proxy (RBAC chain). Pass 2 bands: cited ADRs rank higher (reach
proxy). Pass 3 ratio: small+cited first, the 1906-word strategic item scheduled last-but-guaranteed.

| Session | ADR (slug) | Rationale |
|---------|------------|-----------|
| 1 | cross-module-communication | Quick win — smallest file, 2 citations |
| 2 | action-based-mvc-architecture | Foundation anchor — cluster depends on it conceptually |
| 3 | base-class-mandate | Architecture cluster, builds on mvc anchor |
| 4 | entity-model-separation | Cluster, smallest effort |
| 5 | action-pattern-over-services | Cluster, cited by service-pattern |
| 6 | exception-hierarchy | C8 anchor, standalone |
| 7 | uuid-primary-keys | Quick win, standalone |
| 8 | smartlogger-dual-channel | Standalone observability |
| 9 | performance-optimization | Cited via scaling guide |
| 10 | self-hosted-single-tenant | Cited via scaling guide |
| 11 | program-closure-archival | Standalone domain lifecycle |
| 12 | eloquent-observers | Standalone, newest record |
| 13 | gradual-migration | Cited 3×; links land better after refresh wave |
| 14 | flat-rbac-with-functional-roles | RBAC root — must precede cross-role-proxy |
| 15 | cross-role-proxy | Strategic: 8 citations, 1906 words — capstone, runs last so every link target is fresh |

## Per-session Checklist

1. Read the ADR fully + skim cited specs/modules for accurate Technical Story
2. Rewrite into the locked skeleton above (preserve meaning, never invent history); strip numbers
3. Fix every numeric mention targeting this ADR (see table above + grep command)
4. Add ≥ 2 outbound markdown links; convert stale backtick paths to links
5. Update [index.md](../../docs/adr/index.md) entry title if it carried a number
6. Bump metadata; run `python3 scripts/scan_doc_links.py` — zero findings
7. Commit checkpoint: `docs(adr): rewrite {slug} per MADR-lite`
