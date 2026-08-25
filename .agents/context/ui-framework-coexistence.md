# UI Framework Coexistence — TallstackUI Complete (DaisyUI/maryUI/PHPFlasher Removed)

> **Last updated:** 2026-08-25 **Changes:** sync — packages deleted (not just disabled): daisyui npm + mary + flasher removed, self-hosted palette + shims bridge the gap

## Description

Migration to TallstackUI is **complete** (`x-mary` = 0, `flash()->` = 0, see
`.agents/plans/tallstackui-migration.md` — marked COMPLETE 2026-08-24). Packages are **deleted**, not just disabled:

| Package | State | Replacement |
|---|---|---|
| `robsontenorio/mary` | **removed** from `composer.json`/`composer.lock`; `config/mary.php` + `app/Core/Support/Spotlight.php` deleted; `extra.laravel.dont-discover` now `[]` | TallstackUI `x-ts-*` |
| `php-flasher/flasher-laravel` | **removed**; `config/flasher.php` + `lang/vendor/flasher/{en,id}/messages.php` deleted | TallstackUI toast `toast()->success()` via Interactions |
| `daisyui` (npm) | **removed** (`npm uninstall daisyui`); `@plugin daisyui` + `@source mary` deleted from `resources/css/app.css` | Self-hosted `@theme` palette (`--color-base-100`, `--color-primary` etc.) + `@layer components` shims for 169 legacy tokens (`btn`, `badge`, `table-sm`…) — styling survives without plugin until `x-ts-*` fully replaces class usage |

## Theme Mechanism (fixed 2026-08-24, 0.15.0)

* SSR: `<html data-theme="cookie" [class="dark"]>` in `base.blade.php` (cookie mirrored client-side)
* JS: `applyTheme()` in `resources/js/app.js` sets **both** `data-theme` (semantic palette vars) and `.dark`
  class (Tailwind/TallstackUI `dark:` variant); `storedTheme()` reads `localStorage` `dark-theme` (`true`/`false` legacy or `light`/`dark`/`system`); document listens for TallstackUI `theme` CustomEvent.
* The old Livewire `ThemeSwitcher` (`app/Settings/Livewire/ThemeSwitcher.php`) + its view + `fl-dark` class + MutationObserver (all PHPFlasher-era) were removed; replaced by `core::ui.theme-switch` wrapping `<x-theme-switch>`.

## AI Agent Guides

| Situation | Do |
|---|---|
| New component/UI work | Use `<x-ts-*>` only (FB792 FR-TS6a). Never reintroduce `x-mary-*` or `flash()->`/`@flasher_render`. |
| Need dark-mode style | Use Tailwind `dark:` variant — it works via `.dark`. Do not use `data-theme` selectors in new code. |
| Migrating remaining Daisy tokens | 169 tokens remain as class names, shimmed locally; migrate incrementally to `x-ts-*` (audit 2026-08-24: reports-manager worst at 32). |
| Re-enabling mary/flasher | Not possible — packages removed; reinstall from scratch if truly needed (not recommended). |
