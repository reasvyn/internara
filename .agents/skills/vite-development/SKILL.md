---
name: vite-development
description: "SDLC Phase: IMPLEMENTATION (Sub-skill). Vite build development — entry points, plugins, HMR, and production builds. 1:1 mapping for vite + laravel-vite-plugin."
upstream:
  - ui-development
downstream:
  - sync-docs
---

# Vite Development — Build Pipeline

## When to Activate

Use this skill when configuring Vite entry points, adding plugins (@tailwindcss/vite, laravel-vite-plugin), handling HMR, or debugging production builds.

## Workflow

Follow `agent-workflow` pipeline. This skill adds Vite-specific guidance.

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Entry & plugins (vite.config.js, laravel-vite-plugin, @tailwindcss/vite) | `.agents/rules/entry-plugins.md` | Any Vite config change |
| Build & HMR (dev, build, manifest, asset versioning) | `.agents/rules/build-hmr.md` | Debugging builds or HMR |

## References

| Topic | Doc |
|-------|-----|
| Vite docs | `search-docs` with `vite` |
| Laravel Vite plugin | `search-docs` with `laravel-vite-plugin` |
| App entry | `resources/js/app.js`, `resources/css/app.css`, `vite.config.js` |
