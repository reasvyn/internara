# UI Framework Coexistence — TallstackUI First, DaisyUI/maryUI/PHPFlasher Disabled

> **Last updated:** 2026-08-24 **Changes:** created — records dont-discover decoupling, theme
> `.dark` fix, and what still depends on DaisyUI CSS during coexistence

## Description

Migration to TallstackUI is **complete** (`x-mary` = 0 in app+views, see
`.agents/plans/tallstackui-migration.md`). Packages are **still installed** (FB792 DD-4) but their
runtime wiring is disabled:

| Package | Runtime state | How |
|---|---|---|
| `robsontenorio/mary` | **not discovered** — no SP, no `/mary/*` routes; `config/mary.php` + `app/Core/Support/Spotlight.php` deleted | `composer.json` `extra.laravel.dont-discover` |
| `php-flasher/flasher-laravel` | **not discovered** — SP off; zero code calls; `config/flasher.php` + `lang/vendor/flasher` left inert on disk | same |
| `daisyui` (npm) | **plugin ACTIVE** in `resources/css/app.css` — still powers ~1.708 color utilities (`bg-base-100`, `text-primary`, …) + 169 component-class tokens (`btn`, `badge`, `table-sm`…) in 79 views | kept until token migration |

## Theme Mechanism (fixed 2026-08-24)

* SSR: `<html data-theme="cookie" [class="dark"]>` in `base.blade.php`
* JS: `applyTheme()` in `resources/js/app.js` sets **both** `data-theme` (DaisyUI vars) and `.dark`
  class (Tailwind/TallstackUI `dark:` variant); Livewire `theme-changed` uses the same fn.
* The old `fl-dark` class + MutationObserver existed only for PHPFlasher and were removed.

## AI Agent Guides

| Situation | Do |
|---|---|
| New component/UI work | Use `<x-ts-*>` only (FB792 FR-TS6a). Never reintroduce `x-mary-*` or `flash()->`. |
| Need dark-mode style | Use Tailwind `dark:` variant — it works now via `.dark`. Do not use `data-theme` selectors in new code. |
| Before removing daisyui plugin | Bridge palette into `@theme` (--color-base-100 etc.) and migrate 169 component tokens (audit 2026-08-24: reports-manager worst at 32). |
| Re-enabling mary/flasher | Remove entry from `dont-discover`, run `php artisan package:discover`. Not recommended. |
