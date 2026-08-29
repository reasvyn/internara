# Dependencies — Per-Package Reference Index

## Description

One conceptual reference per runtime dependency: installed version, what the package delivers,
core concepts, and how Internara builds on it. Versions are reconciled against `composer.lock`
and `package.json` (check versions via `git log --follow -- <file>`).

---

## Core Framework

| Doc | Package | Installed |
|-----|---------|-----------|
| [laravel.md](laravel.md) | laravel/framework | v13.24.0 |

## Frontend Stack

| Doc | Package | Installed |
|-----|---------|-----------|
| [livewire.md](livewire.md) | livewire/livewire | v4.3.5 |
| [tallstackui.md](tallstackui.md) | tallstackui/tallstackui | v4.1.0 |
| [alpinejs.md](alpinejs.md) | Alpine.js (bundled via Livewire) | — |
| [tailwindcss.md](tailwindcss.md) | tailwindcss + @tailwindcss/* plugins | ^4.3.3 |
| [vite.md](vite.md) | vite + laravel-vite-plugin | ^8.1 / ^3.1 |

## Spatie Family

| Doc | Package | Installed |
|-----|---------|-----------|
| [spatie-laravel-permission.md](spatie-laravel-permission.md) | spatie/laravel-permission | 8.3.0 |
| [spatie-laravel-medialibrary.md](spatie-laravel-medialibrary.md) | spatie/laravel-medialibrary | 11.23.4 |
| [spatie-laravel-activitylog.md](spatie-laravel-activitylog.md) | spatie/laravel-activitylog | 5.0.0 |
| [spatie-laravel-model-status.md](spatie-laravel-model-status.md) | spatie/laravel-model-status | 1.20.0 — **deprecated (#419)** |

## Laravel Ecosystem

| Doc | Package | Installed |
|-----|---------|-----------|
| [laravel-dompdf.md](laravel-dompdf.md) | barryvdh/laravel-dompdf + dompdf/dompdf | v3.1.2 / v3.1.6 |
| [laravel-pulse.md](laravel-pulse.md) | laravel/pulse | v1.8.0 |
| [laravel-lang.md](laravel-lang.md) | laravel-lang/lang | 15.34.0 |

## JS Utilities

| Doc | Package | Installed |
|-----|---------|-----------|
| [flatpickr.md](flatpickr.md) | flatpickr | ^4.6.13 |
| [marked.md](marked.md) | marked + dompurify | ^18.0.7 / ^3.4.14 |
| [prettier.md](prettier.md) | prettier + blade/tailwind plugins | ^3.9.6 family |

## Quick References

- [dep-template.md](dep-template.md) — skeleton for adding a new dependency doc
- [`../../index.md`](../../index.md) — full documentation catalog
