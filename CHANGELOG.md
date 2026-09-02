# Changelog

All notable changes to Internara are documented here. This file follows [Keep a Changelog](https://keepachangelog.com/) and uses [Semantic Versioning](https://semver.org/). Past versions are tracked via git tags (`git tag`) — see the `Compare` links below for full diffs.

## [Unreleased]

## [0.15.9] - 2026-09-02

Internara v0.15.9 is a Stabilization release: core P0/P1 code smells removed, all
domain modules aligned on the `#[Fillable]` base-model and Entity contracts, a
tag-driven release pipeline with per-stage QA + auto-rollback deploys, and a
hardened devtool toolkit (cache, progress, parallel scans, `tool_runner.py`).
PHPStan/Larastan were dropped in favor of Pint/Prettier/Pest/arch-guard scanners.

### Added
- `feat(scan_spec_tests)`: module filtering and coverage score
- `feat(tools)`: `scan_violations` upgrade — progress, cache, dedup, parallel execution
- `feat(tools)`: `tool_runner.py` orchestrator + registry/docs updates
- `feat(tools)`: core infrastructure — caching, progress, parallel execution, rich reports

### Fixed
- `fix(core)`: `ActionFailedException` created; tests updated
- `fix(core)`: readonly class static property issue in `BaseData` (2b81a64a / 37acfd334)
- `fix(reports)`: complete `StudentReport` rename; handle `app/Modules` in `BaseAction`
- `fix(tools)`: missing `MODULES_DIR` constant added to affected scanners

### Changed
- `refactor(core)` (×8): P2 magic strings/numbers, hardcoded role strings, exception
  messages, `ModuleDiscoverCommand`, BaseEntity/BaseData, ActivityLog &
  CustomDatabaseChannel, P0/P1 code smells and architectural violations
- `refactor({module})` (×15): base model `#[Fillable]` + Entity alignment across
  academics, assignment, assessment, auth, certification, document, enrollment,
  evaluation, incident, journals, partners, program, reports, settings, sysadmin, user
- `ci(workflows)`: tag-driven `release.yml` pipeline replaces build-and-deploy +
  quality-assurance workflows (dev → beta → rc → production, tag-pushed)
- `ci(release)`: prerelease stage detection fixes; frontend devDeps installed before
  lint in all stages; Setup Environment (APP_KEY) added to dev stage
- `chore`: PHPStan + Larastan removed — only Pint/Prettier/Pest/audit remain
- `chore(tools)`: `scan_skills.py` meta-framework scanner removed

### Deprecated
- None

### Removed
- PHPStan / Larastan static analysis (Pint, Prettier, Pest, and `tools/scan_*`
  arch-guards remain the quality gates)
- `scan_skills.py` meta-framework scanner
- `.ai/` rules directory (migrated into shared `.agents/`)

### Documentation
- `docs(agents)`: reference AGENTS.md workflow/context instead of removed skills
- `docs(agents)`: snapshot translated to English, memory paths split
- `chore(agents)`: OpenCode config migrated to shared `.agents/` and docs synced
- `chore(rules)`: self-improvement memory capture made judgment-based (not per-summarize)

### Tests
- `test(settings)`: spec-traceable tests for Setup & Settings foundation; fixed
  `DeleteSettingAction` observer cache invalidation (FR-S5)

**Full Changelog**: https://github.com/reasvyn/internara/compare/v0.15.8...v0.15.9