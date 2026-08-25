# Deprecation — spatie/laravel-model-status

> **Last updated:** 2026-08-25 **Changes:** initial — dependency marked deprecated pending removal

## Description

`spatie/laravel-model-status` (composer `^1.18`) is **deprecated and scheduled for removal** via
[GitHub issue #419](https://github.com/reasvyn/internara/issues/419). Do not write new code
against it.

## Facts (verified 2026-08-25)

- No model imports `Spatie\ModelStatus\HasStatus`; no `model_statuses` migration exists
- The only artifact is `config/model-status.php` — it has no consumer
- Account status persistence is app-owned: `User::setStatus()` / `Registration::setStatus()`
  force-fill a plain `status` column; transitions live in the `StatusEnum` domain layer

## AI Agent Guides

| Situation | Action |
|-----------|--------|
| Adding status to a model | Use a plain column + enum-backed `setStatus()` like `User::setStatus()` — never the package trait |
| Reviewing code that imports `Spatie\ModelStatus\*` | Reject; point to #419 |
| Executing the removal | Follow acceptance criteria in issue #419 (`composer remove`, delete `config/model-status.php`, update dep doc) |

## Quick References

- [Issue #419](https://github.com/reasvyn/internara/issues/419) — removal plan and evidence
- `docs/refs/deps/spatie-laravel-model-status.md` — deprecation notice in the dep doc
