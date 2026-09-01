---
name: permission-development
description: "SDLC Phase: IMPLEMENTATION (Sub-skill). Spatie Permission RBAC development — roles, permissions, Blade directives, and policy integration. 1:1 mapping for spatie/laravel-permission."
upstream:
  - feature-building
downstream:
  - sync-docs
---

# Permission Development — Spatie RBAC

## When to Activate

Use this skill when defining roles/permissions, seeding RBAC, checking roles in code/Blade, or integrating with policies. Covers `spatie/laravel-permission` only — use `activitylog-development` for audit trails.

## Workflow

Follow `AGENTS.md §Agent Workflow` pipeline. This skill adds Spatie Permission guidance.

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Roles & permissions (definition, seeding, guards) | `.agents/rules/roles-permissions.md` | Defining or seeding RBAC |
| Blade & code usage (@role, @hasrole, hasRole(), policies) | `.agents/rules/blade-usage.md` | Any role check in Blade or PHP |

## References

| Topic | Doc |
|-------|-----|
| Spatie Permission docs | `search-docs` with `spatie/laravel-permission` |
| Policy pattern | `docs/guides/arch/policy-pattern.md` |
| RBAC ADR | `docs/adr/adr-flat-rbac-with-functional-roles.md` |
