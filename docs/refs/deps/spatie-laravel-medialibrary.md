# spatie/laravel-medialibrary — Dependency Reference

> **Last updated:** 2026-08-25 **Changes:** feat — initial dependency reference for spatie/laravel-medialibrary 11.23.4

## Description

Conceptual reference for **Spatie Media Library 11** (`spatie/laravel-medialibrary 11.23.4`) —
the file-attachment layer associating uploaded files with Eloquent models.

---

## Installed & Role

| | |
|---|---|
| Installed | `11.23.4` (`composer.json`: `^11.17`) |
| Role | Model-scoped uploads, image conversions, storage abstraction |

---

## Core Concepts

| Concept | What it is |
|---------|-----------|
| **HasMedia + collections** | Models declare `implements HasMedia`; uploads attach to named collections (e.g. `avatar`, `submission`) with per-collection rules |
| **Conversions** | Derived images (thumbnails, resized variants) generated via queued jobs; responsive images emit `srcset` |
| **Storage abstraction** | Files land on any Laravel filesystem disk (local → S3-compatible) without call-site changes |
| **Single entry point** | All writes go through the library (`addMedia(...)`) — direct `Storage::put()` bypassing it is forbidden in this repo |

---

## How Internara Uses It

- Attachments on student submissions (`Assignment/.../Submission`), logbooks
  (`Journals/.../Logbook`), and system settings/branding assets (`Settings/Setting`)
- Upload governance rules: [`infrastructure/media-library.md`](../../guides/infra/media-library.md)
- Package conventions in the `medialibrary-development` skill

## Quick References

- [Official docs](https://spatie.be/docs/laravel-medialibrary) — full package documentation
- [`docs/guides/infra/media-library.md`](../../guides/infra/media-library.md) — collections and governance
- [`docs/guides/infra/filesystem.md`](../../guides/infra/filesystem.md) — storage layout
