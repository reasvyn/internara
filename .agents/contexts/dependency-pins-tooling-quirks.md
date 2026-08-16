# Dependency Pins & Tooling Quirks — Environment Context

> **Last updated:** 2026-08-16 **Changes:** initial — moved from `docs/known-issues.md`

## Description

Known environmental constraints affecting dependency resolution and tooling on the developer's
machine and in the Docker build. Read this before updating composer/npm packages or touching the
toolchain. Each item has a **workaround** that must be used, not "fixed".

---

## `symfony/console` pinned at 7.4.16 (intentional downgrade)

- **Why:** `nunomaduro/collision 8.9.5` requires `symfony/console ^7.4.14 || ^8.1.1`. Upgrading
  `console` to 8.1.x cascades the whole `symfony/*` 8.0.x stack to 8.1.x (via
  `symfony/event-dispatcher <8.1` conflicts), so the lockfile **downgrades `symfony/console`
  8.0.15 → 7.4.16** — the same resolution Dependabot PR #378 produced.
- **Rule:** update it only when the surrounding symfony stack moves to 8.1.

## `codeload.github.com` unreachable from the dev machine

- **Symptom:** composer package downloads (GitHub zipballs) hang.
- **Workaround:** `composer update <pkg> --prefer-source` (clones via the git protocol, which works).
- **Unaffected:** Packagist metadata and `git fetch/push`.

## npm `ERESOLVE` peerOptional conflict

- **Why:** `prettier-plugin-blade@3.2.0` declares an optional peer `@prettier/plugin-php@^0.24.0`, but
  the root project uses `^0.25.0`. Plain `npm install`/`npm update` re-resolution fails with
  `ERESOLVE`.
- **Workaround:** `npm update --legacy-peer-deps`. The Dockerfile already runs
  `npm ci --legacy-peer-deps`.

---

## AI Agent Guides

| If you need to... | Do this |
| ----------------- | ------- |
| Update a composer package on the dev machine | Add `--prefer-source` (codeload unreachable) |
| Update `symfony/console` | Do NOT — it is intentionally pinned to 7.4.16; only move when the symfony stack moves to 8.1 |
| Run `npm update` on the dev machine | Add `--legacy-peer-deps` |
| Touch `prettier-plugin-blade` / `@prettier/plugin-php` | Keep the `--legacy-peer-deps` workaround; reconcile the peer range if you change versions |

---

## Quick References

- `composer.json` / `composer.lock` — `require-dev` pins and lockfile resolution
- `package.json` — prettier + blade plugin versions
- `Dockerfile` — `npm ci --legacy-peer-deps` in the build stage
