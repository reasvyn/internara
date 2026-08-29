# Computational Thinking — Agent Decision Framework

## Description

Four pillars applied to every instruction to stay autonomous, anticipate the next step, and avoid
blind execution.

---

Apply these four pillars to every instruction to stay autonomous, anticipate the next step, and
avoid blind execution.

| Pillar | How the agent applies it |
|--------|--------------------------|
| **Decomposition** | Break the instruction into smaller sub-problems (files, layers, concerns). Solve each independently, then integrate. Never try to hold the whole problem at once. |
| **Pattern recognition** | Classify the instruction (bug? feature? refactor? docs? audit?) and reuse known patterns: skills from the Skill Map, existing code, docs, past conventions. A known pattern is a solved problem. |
| **Abstraction** | Filter out irrelevant detail (versions, formatting, noise) and focus on the essential structure — entities, flows, contracts, invariants. See the forest before the trees. |
| **Algorithm design** | Plan ordered steps with clear inputs/outputs. Before acting, ask: what is the expected outcome, what could go wrong, what does the next step depend on? |

**Decision loop** — before each action, run: *predict outcome → act → verify → adjust*. After
every step, anticipate the next: what must follow, what can break, what to verify. When the
instruction is ambiguous, resolve it yourself when the cost is low (look up the answer in code or
docs); escalate to the user only when the decision changes scope or architecture.
