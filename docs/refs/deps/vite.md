# Vite — Dependency Reference

> **Last updated:** 2026-08-25 **Changes:** feat — initial dependency reference for vite ^8.1 + laravel-vite-plugin ^3.1

## Description

Conceptual reference for the frontend build pipeline: **Vite 8** (`vite ^8.1`) with the
**Laravel Vite plugin** (`laravel-vite-plugin ^3.1`) bridging builds into Blade.

---

## Installed & Role

| Package | Version | Role |
|---------|---------|------|
| `vite` | `^8.1` | Bundler + dev server |
| `laravel-vite-plugin` | `^3.1` | Blade integration (`@vite` directive, hot-module detection, entry resolution) |

---

## What Vite 8 Delivers

- **Rolldown unification** — single Rust-based bundler replacing the old esbuild (dev) /
  Rollup (production) split; 10–30× faster builds in benchmarks with Rollup-compatible plugin API
- **Dev/prod parity** — one transformation pipeline eliminates dev-vs-build inconsistencies
- **Devtools & console forwarding** — `devtools` option and `server.forwardConsole` pipe browser
  logs to the terminal (auto-activates when coding agents are detected)
- Built-in tsconfig `paths` resolution, Wasm SSR imports, Oxc-powered transforms

---

## How Internara Uses It

- Entry points wired through `vite.config.js` with the Laravel plugin; Blade loads assets via the
  `@vite` directive with hot-reload awareness in local dev
- `composer run dev` runs Vite concurrently with the app server, queue worker, and Pail log tail;
  production assets built with `npm run build`
- Tailwind integrates via `@tailwindcss/vite` ([`tailwindcss.md`](tailwindcss.md))
- Build pipeline details: [`infrastructure/ci-cd.md`](../../infrastructure/ci-cd.md)

## Quick References

- [Vite 8 announcement](https://vite.dev/blog/announcing-vite8) — Rolldown migration rationale
- [Laravel Vite docs](https://laravel.com/docs/vite) — plugin behavior and Blade integration
- [`docs/infrastructure/ci-cd.md`](../../infrastructure/ci-cd.md) — build in CI
