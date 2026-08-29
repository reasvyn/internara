# Agent Workflow — Key Rules

> **Last updated:** 2026-08-17 **Changes:** rewritten comprehensively — per-rule intent, rationale, application, and verification

These rules govern every instruction, in every form, before any task-specific skill loads. They are
non-negotiable. A violation of any rule below is a workflow violation regardless of the outer task's
success.

---

## Rule 1 — Load `agent-workflow` first, then the task-specific skill

**What it enforces:** On every instruction, the `agent-workflow` skill must be the first skill
loaded, before any other skill. The task-matching skill from the Skill Map loads second.

**Why it matters:** `agent-workflow` is the single source of truth for the 5-step pipeline
(Understand → Plan → Implement → Verify → Summarize), size triage, narration discipline, and
verification strategy. Loading it first guarantees the agent operates on current workflow facts even
when other skills or files restate them in stale form. Skipping it means task-specific skills (which
assume it) are interpreted without their governing context.

**How to apply:** At the start of the session, load `agent-workflow` (skill tool), then load only
the skills the task actually uses (see Skill Map in `AGENTS.md`). Load nothing before it — not even
`context-awareness`.

**Pitfalls to avoid:**
- Loading `context-awareness` or a task skill before `agent-workflow`.
- Treating a task as "too trivial to need the workflow" — the rule applies even to one-line
  questions (depth scales with the phase, per Phase Classification).
- Restating the workflow inside a task skill to compensate — skills reference `agent-workflow`, they
  never duplicate it.

**Verification:** The first skill-load tool call in the session targets `agent-workflow`.

---

## Rule 2 — Never restate the workflow inside another skill

**What it enforces:** No skill (other than `agent-workflow` itself) may re-transcribe the canonical
5-step pipeline (Understand → Plan → Implement → Verify → Summarize) or generic workflow steps that
belong to `agent-workflow`.

**Why it matters:** A duplicated workflow re-injects the same text into context on every skill load,
bloating context and silently drifting from the canonical version. The meta-framework scanner
(`scan_skills.py`) flags restatements as `SKILL_NO_DUP_WORKFLOW` violations.

**How to apply:** When writing or editing a skill, keep only that skill's unique execution steps,
rules, and references. Refer to `agent-workflow` with a one-liner: "Follow the `agent-workflow`
skill for the canonical 5-step pipeline (Understand → Plan → Implement → Verify → Summarize) — this
skill adds {X} — nothing else."

**Pitfalls to avoid:**
- Copying the 4-phase Construct/Execute/Verify/Report skeleton into a new skill "for self-containment".
- Adding a redundant "Workflow" table in a skill when `agent-workflow` already owns it.

**Verification:** `python3 tools/scan_skills.py` reports no `SKILL_NO_DUP_WORKFLOW` findings.

---

## Rule 3 — Follow the governing spec before any work (Spec-First Doctrine)

**What it enforces:** Every action must be driven by the governing spec in `docs/specs/`. The spec
defines intent, requirements (FR/NFR/UC IDs), scope, and acceptance criteria. No behavior change,
feature, or fix may proceed without a corresponding requirement ID — and no instruction may proceed
without locating its governing spec (or an explicit recorded decision).

**Why it matters:** The spec is the authoritative source of truth. User wording, ad-hoc reasoning,
and existing code can all be wrong or stale; the spec outranks them. Working without a requirement
produces orphan code, undocumented behavior, and audit drift that `spec-audit` must later unwind.

**How to apply:** In **Understand** (Step 1) and **Plan** (Step 2), locate the spec (foundation,
module, or feature) via `docs/specs/index.md`, read its FR/NFR/UC IDs, and drive scoping, design,
and tests from it. If no requirement exists, write it into the spec first — spec-first, never
fix-first. If spec and code disagree, align code to spec ("fix code, assert spec"); if the spec is
demonstrably wrong, amend the spec with a recorded decision first.

**Pitfalls to avoid:**
- Proceeding on a bug/feature with no requirement ID "because the fix is obvious".
- Treating existing code as authority when the spec says otherwise.
- Editing the spec without recording the decision (ADR or metadata `**Changes:**` line).

**Verification:** The goal/task trace to at least one `FR-*`/`NFR-*`/`UC-*` ID; every touched test
asserts a spec requirement (no orphan tests, no spec gaps).

---

## Rule 4 — Narration discipline: surface only the necessary

**What it enforces:** The 5 pipeline steps are internal reasoning and must not be narrated, restated,
or listed. Surface to the user **only**: (1) ambiguity that needs their input, (2) a decision that
changes scope, structure, or behavior, (3) an L-size session plan (one short paragraph), (4) one
checkpoint before commit (M-size) or per-session (L-size), (5) the final report (what changed, what
was verified, caveats).

**Why it matters:** Every sent sentence costs user attention and context. Narrating steps duplicates
what tool use already shows and buries the decisions that actually need human judgment. Response
quality is measured by information delivered, not process described.

**How to apply:** Before sending a sentence, ask: does it carry new information or a decision? If
neither, drop it. Keep responses short (typically under 4 lines unless the user asks for detail).

**Pitfalls to avoid:**
- "First I will X, then I will Y" play-by-play.
- Restating the task back to the user before acting.
- Summarizing why a tool call was made when the output is self-evident.

**Verification:** The transcript contains no step-by-step narration of the pipeline; user-facing
messages are decision/ambiguity/report-only.

---

## Rule 5 — Batch verification; full suite only when the user asks

**What it enforces:** Batch all changes first, then verify once. Default verification is the targeted
per-change checks (module suite, `--filter`, pint, prettier, arch-guard scanners). The full suite
(`php artisan test --compact`) and PHPStan full analysis are on-demand only — never run as part of
routine work.

**Why it matters:** The full suite consumes ~2GB+ RAM and 10+ minutes. Running it per-edit wastes
resources and slows feedback. Coverage is measured in spec requirements covered, not lines, so
spec-scoped tests (which run in seconds) are the default gate.

**How to apply:** Group edits in **Implement** (Step 3), then verify once in **Verify** (Step 4) via
the change-type matrix in `AGENTS.md` §Verification Strategy (translation keys → pint + tinker echo;
blade/css/js → `npm run build`; refactor → targeted filter; new business logic → full suite once).
Review `git status` + `git diff` before/after every change.

**Pitfalls to avoid:**
- Running the full suite after every small edit.
- Skipping `git diff` review "because it's just docs".
- Verifying each file in isolation instead of batching.

**Verification:** Verification tool calls match the change-type matrix; the full suite runs only when
explicitly requested.

---

## Rule 6 — Batch instructions run by impact-to-effort ratio

**What it enforces:** When a message bundles multiple instructions, reorder and execute them by
impact-to-effort ratio — **quick wins first, heavy lifts scheduled** — rather than the literal order
given. See `rules/instruction-ordering.md` for the full algorithm (decompose → score → sort → honor
dependencies → group → surface).

**Why it matters:** Executing in literal order lets low-value/high-effort work consume the session
while quick wins wait. Impact-to-effort ordering maximizes delivered value per unit of effort and
matches user intent (the sequence is usually accidental, not deliberate priority).

**How to apply:** Score each instruction on 1-5 scales for impact (reach × importance) and effort
(files × complexity × verification); sort by `impact / effort` descending; honor dependency
constraints first; group same-area instructions into one pass. Surface the resulting order in one
short paragraph only when it differs from the user's sequence.

**Pitfalls to avoid:**
- Assuming the user's item order expresses priority.
- Queuing "strategic" (high/high) work ahead of "quick win" (high/low) items.
- Batching unrelated concerns into one pass purely for speed.

**Verification:** Execution order in the transcript matches impact-to-effort ranking, not message
order; each executed item has a scored rationale traceable to `rules/instruction-ordering.md`.