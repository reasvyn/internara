# Dependencies — Per-Package Reference Index

## Description

One conceptual reference per runtime dependency: installed version, what the package delivers,
core concepts, and how Internara builds on it. Versions are reconciled against `composer.lock`
and `package.json` (check versions via `git log --follow -- <file>`).

---

## Installed & Role

Sixteen runtime dependencies, grouped below by the role they play. Two principles govern the
selection:

- **Nothing that requires a paid service.** Every package here runs on the school's own
  infrastructure. There is no external API in the critical path, because a school cannot be asked
  to depend on someone else's uptime.
- **Nothing that duplicates the framework.** Where Laravel already solves a problem well, no
  package is added for it — Activities and Notifications handle events and mail, Cache and Queue
  handle runtime services.

The exceptions are the packages Laravel deliberately leaves open: file handling, PDF rendering,
permission storage, and observability. Each is documented in a sibling file covering what the
package delivers, what Internara uses, and where the boundary between the package and our own
code sits.

---

## Core Framework

| Doc | Package | Installed |
|-----|---------|-----------|
| [laravel.md](laravel.md) | laravel/framework | v13.29.0 |

## Frontend Stack

| Doc | Package | Installed |
|-----|---------|-----------|
| [livewire.md](livewire.md) | livewire/livewire | v4.4.3 |
| [tallstackui.md](tallstackui.md) | tallstackui/tallstackui | v4.1.0 |
| [alpinejs.md](alpinejs.md) | Alpine.js (bundled via Livewire) | — |
| [tailwindcss.md](tailwindcss.md) | tailwindcss + @tailwindcss/* plugins | ^4.3.3 |
| [vite.md](vite.md) | vite + laravel-vite-plugin | ^8.1 / ^3.1 |

## Spatie Family

| Doc | Package | Installed |
|-----|---------|-----------|
| [spatie-laravel-permission.md](spatie-laravel-permission.md) | spatie/laravel-permission | 8.3.0 |
| [spatie-laravel-medialibrary.md](spatie-laravel-medialibrary.md) | spatie/laravel-medialibrary | 11.23.6 |
| [spatie-laravel-activitylog.md](spatie-laravel-activitylog.md) | spatie/laravel-activitylog | 5.1.0 |
| [spatie-laravel-model-status.md](spatie-laravel-model-status.md) | spatie/laravel-model-status | 1.20.0 — **deprecated (#419)** |

## Laravel Ecosystem

| Doc | Package | Installed |
|-----|---------|-----------|
| [laravel-dompdf.md](laravel-dompdf.md) | barryvdh/laravel-dompdf + dompdf/dompdf | v3.1.2 / v3.1.6 |
| [laravel-pulse.md](laravel-pulse.md) | laravel/pulse | v1.8.1 |
| [laravel-lang.md](laravel-lang.md) | laravel-lang/lang | 15.34.6 |

## JS Utilities

| Doc | Package | Installed |
|-----|---------|-----------|
| [flatpickr.md](flatpickr.md) | flatpickr | ^4.6.13 |
| [marked.md](marked.md) | marked + dompurify | ^18.0.7 / ^3.4.14 |
| [prettier.md](prettier.md) | prettier + blade/tailwind plugins | ^3.9.6 family |

---

## How Internara Uses It

Per-package usage is documented in each sibling dep doc rather than restated here, to keep one
source of truth per package. The short version of how the stack divides up:

| Concern | Package | Note |
| ------- | ------- | ---- |
| Application skeleton, ORM, queues, cache, mail | `laravel/framework` | The substrate; see [Architecture](../../architecture.md) for the layering built on top |
| Interactive UI | `livewire/livewire` | All screens are Livewire components; no SPA, no JSON API |
| UI primitives | `tallstackui/tallstackui` | Wrapped by the UI module, never used directly by feature modules |
| Client behaviour | `alpinejs` | Bundled with Livewire |
| Styling | `tailwindcss` | CSS-first `@theme` tokens; see [UI Pattern](../../guides/arch/ui-pattern.md) |
| Asset pipeline | `vite` | Build only — no dev-server dependency in production |
| RBAC | `spatie/laravel-permission` | 5 flat roles; see [RBAC ADR](../../adr/adr-flat-rbac-with-functional-roles.md) |
| File uploads | `spatie/laravel-medialibrary` | Server-side MIME, slugged filenames, conversions |
| Audit trail | `spatie/laravel-activitylog` | Feeds SmartLogger's second channel |
| Model status | `spatie/laravel-model-status` | **Deprecated upstream (#419)** — replacement tracked, do not adopt in new code |
| PDF output | `barryvdh/laravel-dompdf` | Certificates and official documents |
| Monitoring | `laravel/pulse` | Health and performance metrics |
| Translations | `laravel-lang/lang` | Validation and date formats beyond the two shipped locales |
| Date picking | `flatpickr` | Date inputs |
| Markdown | `marked` + `dompurify` | Render-then-sanitize; never the reverse |
| Formatting | `prettier` + plugins | Blade and Tailwind class sorting |

---

## Quick References

- [dep-template.md](../../templates/dep-template.md) — skeleton for adding a new dependency doc
- [`../../index.md`](../../index.md) — full documentation catalog
