# Impact-to-Effort — Universal Work Ordering

> **Last updated:** 2026-08-25 **Changes:** initial — promoted from inline guidance into a global rule covering all prioritization, not only batched instructions

## Description

All work — batched instructions, backlog picks, multi-stage plans, issue triage — is **ordered
before execution**, never taken verbatim. Three passes run in fixed order: dependency chains,
then business priority bands, then impact-to-effort ratio.

---

## The Three Passes (Fixed Order)

### Pass 1 — Dependency Chains (topological)

- If B needs A, A executes first **even when B scores higher** — a high-ratio item behind an
  unmet prerequisite is not actually available.
- Unblock downstream work early: when two candidates score equally, take the one others depend on.
- Never start blocked work "anyway" unless reordering the chain is more expensive than waiting.

### Pass 2 — Business Importance & Urgency (priority bands)

Band every candidate before comparing ratios — urgency never outranks importance alone:

| Band | Importance | Urgency | Handling |
|------|-----------|---------|----------|
| **P0 — Critical** | Core flow broken / data loss / security | Now | Jumps the queue regardless of ratio |
| **Important-first** | Matters to mission (daily PKL operations, compliance) | Low–medium | Scheduled deliberately after P0s and quick wins |
| **Urgent-only** | Low mission impact | Deadline-driven | Batch tightly; challenge whether the deadline is real |
| **Neither** | Cosmetic / hygiene | None | Fill-ins, deferrals, or filed issues |

Tie-break inside a band: higher importance beats higher urgency.

### Pass 3 — Impact-to-Effort Ratio

Within the same band, sort by ratio — quick wins first, heavy lifts scheduled, low-value work
batched or challenged. The numeric 1–5 scoring scale and the four quadrants (quick win /
strategic / fill-in / questionable) are defined in
[`.agents/rules/instruction-ordering.md`](.agents/rules/instruction-ordering.md)
— apply them as-is; do not re-invent scales locally.

## Where It Applies

| Situation | Application |
|-----------|-------------|
| Message batches 2+ instructions | Full three-pass sort before executing anything |
| Multi-stage plan / L-size session split | Stage order follows the passes; each stage ends in a checkpoint commit ([commit-as-checkpoint](commit-as-checkpoint.md)) |
| Backlog / issue triage | Score open issues; P0 band floats to top; Questionable items get challenged or filed with low priority |
| Mid-task discovery of extra work | Same passes decide *now vs file-as-issue* ([pre-existing-defects](pre-existing-defects.md)) |

## Surfacing

Run all scoring silently. Surface the resulting order only when it differs from the given order,
when a dependency forces resequencing, or when a Questionable item is being deferred/challenged.

## Quick References

- [`.agents/rules/instruction-ordering.md`](.agents/rules/instruction-ordering.md) — scoring scale, quadrants, worked examples
- [commit-as-checkpoint.md](commit-as-checkpoint.md) — one checkpoint per ordered stage
- [pre-existing-defects.md](pre-existing-defects.md) — now-vs-file decisions for discovered work
