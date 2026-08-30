# Instruction Ordering & Computational Thinking

> **Curated mandatory known context** — impact-to-effort ordering and decision loop. Read at start of every session, especially when batching instructions.

## Instruction Ordering — High-Impact, Low-Effort First

The user sometimes batches instructions in random order. Before executing any of them (during **Understand**), reorder the batch by **impact-to-effort ratio** — quick wins first, heavy lifts scheduled. Apply this to every multi-instruction message; run the scoring silently and surface only the resulting order.

The full rule (the impact-to-effort rule, scoring scale, worked examples, and commit grouping) lives in `.agents/rules/instruction-ordering.md` — apply that rule, not the summary below.

| Quadrant | Impact | Effort | Handling |
|----------|--------|--------|----------|
| **Quick win** | High | Low | Execute first — highest impact-to-effort ratio |
| **Strategic** | High | High | Split into sessions (Size Triage L); schedule after quick wins |
| **Fill-in** | Low | Low | Batch opportunistically alongside larger work; do not skip |
| **Questionable** | Low | High | Challenge or defer; confirm with the user before investing |

**Ordering algorithm (summary — see the rule file for scoring):**
1. **Decompose** the batch into discrete, independently-executable instructions
2. **Score** each by impact (reach × importance) and effort (files × complexity × verification)
3. **Sort** by impact-to-effort ratio, quick wins first
4. **Honor dependencies** — if instruction B depends on A, execute A first even if B scores higher
5. **Group** same-area instructions into one pass (batch file touches, batch verification in Verify)
6. **Surface** the resulting order in one short paragraph when it differs from the user's sequence

## Computational Thinking — Decision Loop

Before each action: *predict outcome → act → verify → adjust*. Anticipate the next step. Resolve ambiguity yourself when the cost is low; escalate to the user only when the decision changes scope or architecture.

| Pillar | Application |
|--------|-------------|
| **Decomposition** | Break into sub-problems (files, layers, concerns); solve each, then integrate |
| **Pattern recognition** | Classify the instruction; reuse known patterns (skills, docs, conventions) |
| **Abstraction** | Filter irrelevant detail; focus on entities, flows, contracts, invariants |
| **Algorithm design** | Plan ordered steps with clear inputs/outputs |

---
*Source: AGENTS.md §Instruction Ordering & §Computational Thinking. For the full rule, see `.agents/rules/instruction-ordering.md`.*