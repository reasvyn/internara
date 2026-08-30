# Phase Classification & Size Triage

> **Curated mandatory known context** — adaptive depth classification and session splitting rules. Read at start of every session.

## Phase Classification — Adaptive Depth

Classify the instruction into an SDLC phase. Full = mandatory complete depth · Light = executed but minimal · Note = skip silently. Anything not listed defaults to Note. Depth is now expressed in the 5-step vocabulary.

| SDLC Phase | Full (mandatory) | Light | Note (skip silently) |
|------------|------------------|-------|----------------------|
| **Support** | Understand, Summarize | Plan (brief context check) | Implement, Verify (findings only) |
| **Analysis** | Understand, Plan, Summarize | Verify (sanity check) | Implement |
| **Planning** | Understand, Plan, Summarize | Verify (feasibility check) | Implement |
| **Design** | Understand, Plan, Summarize | Verify (design review) | Implement |
| **Implementation** | **All 5 steps** | — | — |
| **Testing** | Understand, Implement, Verify, Summarize | Plan (scope the test plan) | — |
| **Documentation** | Understand, Plan, Implement, Summarize | Verify (link & metadata check) | — |
| **Tooling** | Understand, Plan, Implement, Verify, Summarize | — | — |
| **Maintenance** | Understand, Verify, Summarize | Plan, Implement | — |

> **How to read:** e.g., a `Support` question runs Understand deeply (intent + spec lookup), skims Plan just enough to locate relevant docs, skips Implement/Verify except to validate the answer, and delivers a full Summarize. An `Implementation` task runs all 5 steps at full depth.

## Size Triage — Session Splitting

| Size | Criteria | Execution | User check-in |
|------|----------|-----------|---------------|
| **S** | ≤3 files, single concern, no cross-module | Single pass, full 5 steps at phase depth | None |
| **M** | 4-10 files, 2-3 concerns, or cross-layer | Single session, staged internally, batch verification (Verify once, then Summarize) | One checkpoint before commit (Step 5) |
| **L** | >10 files, multi-module, cross-cutting | **MUST split into multiple sessions** | **MUST inform the user first** |

**L-size protocol:** after **Plan** (Step 2), tell the user in one short paragraph: *"This instruction is too broad for a single pass — I will split it into N sessions"* + the session list. Execute sessions in order; each session runs Implement → Verify → Summarize with its own `git status` + `git diff` review, targeted verification, and short report. Never attempt L-size in one pass.

---
*Source: AGENTS.md §Phase Classification & §Size Triage. For instruction ordering, see `.agents/context/instruction-ordering.md`.*