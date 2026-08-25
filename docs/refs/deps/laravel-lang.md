# laravel-lang/lang — Dependency Reference

> **Last updated:** 2026-08-25 **Changes:** feat — initial dependency reference for laravel-lang/lang 15.34.0

## Description

Conceptual reference for **laravel-lang/lang 15** (`laravel-lang/lang ^15.26`) — the community
translation pack providing framework-level language files for Laravel and its first-party packages.

---

## Installed & Role

| | |
|---|---|
| Installed | `15.34.0` (`composer.json`: `^15.26`) |
| Role | Ready-made translations (validation errors, pagination, password resets, …) for ~70+ locales |

---

## Core Concepts

| Concept | What it is |
|---------|-----------|
| **Framework strings** | Complete translations of Laravel's built-in strings — validation messages, auth failures, pagination verbs — which ship English-only by default |
| **Package coverage** | Optional translation sets for common ecosystem packages |
| **Publishing model** | Language files are published/copied into the application's `lang/` directory, where they become app-owned and editable |

---

## How Internara Uses It

- Supplies the Indonesian (`lang/id/`) baseline that government-facing UI requires; Indonesian is
  the preferred UI locale, toggled via Settings ([`infrastructure/localization.md`](../../infrastructure/localization.md))
- App-owned overrides live in `lang/en/` and `lang/id/` — every user-facing string flows through
  `__()` (D3)
- Localization rules: `docs/conventions.md` §Localization

## Quick References

- [Project page](https://laravel-lang.com) — locale coverage and publishing commands
- [`docs/infrastructure/localization.md`](../../infrastructure/localization.md) — locale resolution
