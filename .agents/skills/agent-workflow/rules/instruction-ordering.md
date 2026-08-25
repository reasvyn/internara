# Instruction Ordering — Impact-to-Effort Rule

> This rule operationalizes the global [impact-to-effort](../../../rules/impact-to-effort.md)
> doctrine (dependency chains → business priority bands → ratio) for the specific case of batched
> instructions. The scoring scale below remains its canonical definition.

Users sometimes send instructions in random, unintended order. Never execute a batch verbatim.
Before any work, reorder the batch by impact-to-effort ratio: **quick wins first, heavy lifts
scheduled, low-value work batched or challenged.** Run the scoring silently and surface only the
resulting order.

## When to Apply

- Any message containing **2+ instructions** (batched tasks, a list, follow-ups bundled together)
- The user's sequence is accidental, not intentional — never assume the literal order is the priority

## The Rule

Order instructions so the sequence maximizes delivered value per unit of effort:

1. **Decompose** the batch into discrete, independently-executable instructions. Skip nothing; every
   item is scored even if trivial.
2. **Score** each instruction. Impact = reach × importance. Effort = files touched × complexity ×
   verification cost.
3. **Sort** by impact-to-effort ratio, highest first.
4. **Honor dependencies first** — if B needs A, do A first even when B scores higher.
5. **Group** same-area instructions into one pass (batch file touches, batch verification).
6. **Surface** the final order in one short paragraph only when it differs from the user's sequence,
   or when a "Questionable" item is being deferred.

## Impact-to-Effort Quadrants

| Quadrant | Impact | Effort | Handling |
|----------|--------|--------|----------|
| **Quick win** | High | Low | Execute first — highest impact-to-effort ratio |
| **Strategic** | High | High | Split into sessions (Size Triage L); schedule after quick wins |
| **Fill-in** | Low | Low | Batch opportunistically alongside larger work; do not skip |
| **Questionable** | Low | High | Challenge or defer; confirm with the user before investing |

## Scoring

Estimate on a coarse 1-5 scale per instruction; the ratio decides the order.

- **Impact** = reach (who/how many affected) × importance (how much it matters)
  - 5: breaks core flow, security, data loss, blocks many users
  - 3: moderate feature or fix, single persona affected
  - 1: cosmetic, batch hygiene, docs-only trivia
- **Effort** = files touched × complexity × verification cost
  - 5: multi-module, spec amendment, new migration, full-suite verification
  - 3: single module, a few files, targeted suite
  - 1: one file, mechanical edit, no tests needed

Sort `impact / effort` descending. Ties: dependency-constrained first, then effort (prefer cheaper).

## Examples

| Batch | Scored | Executed order |
|-------|--------|----------------|
| (5) move 1 line, (2) fix dashboard 500, (4) rewrite module, (3) add icon | #2 high-impact/low-effort, #5 quick-win, #3 fill-in, #4 questionable | dashboard 500 → move line → icon → rewrite module (challenge) |
| (1) bump PHP version, (3) fix N+1 in search, (2) add tooltip | #3 high impact/moderate, #1 low/high | N+1 (if it blocks) or PHP bump → N+1 → tooltip |

## Commit Grouping

Follow the same rule when committing a session's work: quick wins and interdependent changes can
share one commit (`fix`), strategically-separate concerns get their own commits. Never mix unrelated
concerns in one commit regardless of ratio.