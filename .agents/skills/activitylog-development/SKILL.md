---
name: activitylog-development
description: "SDLC Phase: IMPLEMENTATION (Sub-skill). Spatie Activity Log development — audit trails, SmartLogger, causer tracking, and log retrieval. 1:1 mapping for spatie/laravel-activitylog."
upstream:
  - feature-building
downstream:
  - sync-docs
---

# ActivityLog Development — Audit Trail

## When to Activate

Use this skill when logging activity, configuring SmartLogger dual-channel, tracking causer, or querying audit logs.

## Workflow

Follow `agent-workflow` pipeline (Understand → Plan → Implement → Verify → Summarize). This skill adds Spatie Activity Log guidance, spec-first doctrine (**governing spec** FR/NFR/UC IDs), and **Size Triage** (S/M/L session splitting).

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Logging & causer (activity(), log(), causer) | `.agents/rules/logging-causer.md` | Any audit log write or read |
| SmartLogger dual-channel (DB + file, event correlation) | `.agents/rules/smartlogger.md` | Configuring or using SmartLogger |

## References

| Topic | Doc |
|-------|-----|
| Spatie Activity Log docs | `search-docs` with `spatie/laravel-activitylog` |
| Logging pattern | `docs/guides/arch/logging-pattern.md` |
| SmartLogger ADR | `docs/adr/adr-smartlogger-dual-channel.md` |
