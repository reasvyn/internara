---
name: internara-build
description: Implement Internara features with quality gates — full 4-layer build (Action triad, Entity/DTO/Model, Livewire, Policy) per spec. Use when implement, code, refactor, build, feature, or fix is mentioned.
---

# Build Command — Spec to Tested Code (Internara)

Delegates to the `internara-builder` agent (implementation specialist) and its skills
(`code-writing`, `code-refactoring`, `feature-building`, `laravel-best-practices`,
`livewire-development`, `tailwindcss-development`, `medialibrary-development`, `pulse-development`).
Turns a `docs/specs/*.md` FR/NFR/UC into a tested, 4-layer module increment without breaking
architecture invariants.

Scope: $ARGUMENTS

If $ARGUMENTS is empty, ask for the spec/feature, the FR/NFR/UC IDs, target module/domain, and how
it should be verified. No behavior without a requirement ID — the spec comes first.

## Steps

1. **Understand — Read the governing spec.** Locate it via `docs/specs/index.md`;
   read FR/NFR/UC + module docs + `docs/guides/arch/*.md` + `docs/conventions.md` before code. Verify
   paths against actual `app/{Module}/{Domain}/` layout.
2. **Plan — Design contracts.** Command/Read/Process Action + base class; DTO for 3+ params (C7);
   `declare(strict_types=1)` (D1); `RejectedException` (C8); cache keys in `config/cache-keys.php` (C4);
   `__()` for user strings (D3); no `app()->make` (C2).
3. **Build — Write surgically.** Small, DRY, well-named modules; testable increments. Delegate business
   rules to Entities; Models are persistence only (`#[Fillable]`, D4). No model mutations in Livewire (C1).
   Update PHPDoc + module docs in the same step.
4. **Verify — Quality gates.** `vendor/bin/pint --dirty --test`, targeted tests
   (`php artisan test --compact --filter={Class}` or `vendor/bin/pest --testsuite={Module}`),
   scanners via `internara-reviewer` if needed. Full suite only on-demand.
5. **Summarize — Close the loop.** Commit `type(scope): desc`, reference the spec IDs, report what was
   verified and any spec gaps.

## Validation

- [ ] Spec-traced (FR/NFR/UC ID), strict types, no debug calls, `__()` coverage
- [ ] Action triad + DTO + Entity delegation, cache registry, N+1 avoided, escaped output
- [ ] Pint/arch-guard clean; tests trace to requirement IDs (no orphans)