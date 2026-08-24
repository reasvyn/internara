# TallstackUI Migration Plan — DaisyUI/MaryUI/PHPFlasher → TallstackUI v4

> **Last updated:** 2026-08-24 **Changes:** initial mapping + impact-to-effort + phased GitHub issues
> **Governing specs:** FB792 (FR-TS6, DD-4), J68GZ (FR-D10a), 8XMYS (DD-4), 52O1I (FR-T2/FR-L5, DD-6)
> **Principle:** TallstackUI-first — always check `<x-ts-*>` before `maryUI`/`DaisyUI`/custom (FB792 FR-TS6a, NFR-DEP5)

## Mapping — Areas to Migrate

| Area | Current (legacy) | Count | TallstackUI Replacement | Files | Impact | Effort |
|------|------------------|-------|-------------------------|-------|--------|--------|
| **Flash/Toast** | `php-flasher/flasher-laravel` `flash()->success/error/warning` + `@flasher_render` | 235 `flash()` calls in `app/**/Livewire/*.php` + 1 `@flasher_render` in `base.blade.php` | `TallStackUi::toast()->send()->success/error` / `<x-ts-toast />` + `<x-ts-alert />` (Interactions/Toast, UI/Alert) | `app/**`, `resources/views/core/layouts/base.blade.php` | High — every action feedback | Low — 1:1 replace, no UI layout change | **High** |
| **Theming** | DaisyUI `data-theme` (`light`/`dark`/`system`) + `Theme::cssVariables()` + `ThemeSwitcher` mary dropdown + Alpine `theme-changed` | 42 theme hits, `ThemeSwitcher` 1 component, `base.blade.php` 2 CSS blocks | `x-ts-theme-switch` + `TallStackUi::darkTheme()` helper + `config/tallstackui.php` `darkTheme` | `app/Settings/Livewire/ThemeSwitcher.php`, `settings.livewire.theme-switcher`, `base.blade.php`, `resources/js/app.js` | Medium — per-browser pref, visible everywhere | Low — single component, CSS vars remain | **High** |
| **Localization** | `LangSwitcher` mary dropdown + `Locale::set()` + `SetLocaleMiddleware` (EN/ID) | 34 locale hits, `LangSwitcher` 1 component | `x-ts-dropdown` / `x-ts-select.styled` for EN/ID (TallstackUI-first, fallback mary during coexistence) | `app/Settings/Livewire/LangSwitcher.php`, `settings.livewire.lang-switcher` | Medium — bilingual, per-browser | Low — single component | **High** |
| **Layout** | DaisyUI `drawer` (`drawer-toggle`, `drawer-side`, `drawer-content`, `lg:drawer-open`), `navbar`, `sidebar` 7 files | 14 layout hits, 7 layout files (`app.blade.php`, `sidebar`, `header`, `base`, `guest`) | `x-ts-layout` + `x-ts-side-bar` + `x-ts-side-bar.item` + `x-ts-layout.header` (UI/Layout, SideBar) | `resources/views/core/layouts/**` | High — chrome on every page | Medium — layout refactor, needs visual regression | **Medium-High** |
| **Accessibility** | DaisyUI `focus:ring`, `drawer-overlay` aria, `skip-link`, `focus:not-sr-only` in `base.blade.php` | ~30 a11y tokens, 4 shell files | TallstackUI built-in WCAG (focus, ARIA, `x-ts-*` a11y) + `ThemeSwitch`/`Layout` a11y; replace custom `focus:` with TallstackUI tokens | `base.blade.php`, `app.blade.php`, `sidebar`, `header` + `tailwindcss` skill `accessibility-wcag` | High — WCAG 2.1 AA | Medium — audit + token swap, no logic | **Medium** |
| **Forms** | `maryUI` form: `x-mary-input`, `select`, `checkbox`, `toggle`, `textarea`, `file`, `date` + DaisyUI `btn`, `card`, `input` classes | 322 form hits, 1222 mary components total | `x-ts-input`, `select.styled`, `checkbox`, `toggle`, `textarea`, `upload`, `date`, `button` (Form/*, UI/Button) | `resources/views/**/*.blade.php`, `app/**/Livewire/Forms/*.php` | High — 18 modules, 300+ forms | High — many files, validation `invalidate` handling | **Medium** |
| **Data Display** | `maryUI` `table`, `card`, `badge`, `modal`, `dropdown`, `tabs` + DaisyUI `card`, `badge`, `modal`, `dropdown` | ~400 display hits (subset of 1222) | `x-ts-table`, `card`, `badge`, `modal`, `dropdown`, `tab` (UI/*, Interactions/Dialog) | `resources/views/**`, `app/**/Livewire/*Manager.php` | High — every manager/index | High — 1222 components, per-module | **Low-Medium** |

Counts via: `grep -r "flash()->" app | wc -l` 235, `grep -r "<x-mary" resources | wc -l` 1222, `grep -r "btn \|drawer" resources | wc -l` 331, `grep -r "x-mary-input\|select" resources | wc -l` 322.

## Impact-to-Effort Scoring (ratio = Impact / Effort, higher first)

| Rank | Area | Impact (1-5) | Effort (1-5) | Ratio | Rationale |
|------|------|--------------|--------------|-------|-----------|
| 1 | **Flash/Toast** | 5 | 1 | 5.0 | Touches 235 calls but 1 pattern (`flash()->` → `toast()->send()`), no layout, immediate user-visible win, unblocks PHPFlasher removal |
| 2 | **Theming** | 4 | 1 | 4.0 | Single component, CSS vars already exist, TallstackUI `ThemeSwitch` drop-in, low risk |
| 3 | **Localization** | 3 | 1 | 3.0 | Single component, same pattern as theming, EN/ID only |
| 4 | **Layout** | 5 | 3 | 1.67 | Every page chrome, needs drawer→`x-ts-layout` refactor + responsive test, but isolated to 7 files |
| 5 | **Accessibility** | 5 | 3 | 1.67 | WCAG critical, but largely token swap + audit, follows layout |
| 6 | **Forms** | 5 | 4 | 1.25 | 322 hits across 18 modules, validation `invalidate` + `wire:model` coupling, per-module PRs |
| 7 | **Data Display** | 5 | 5 | 1.0 | Largest surface (400+), per-manager, should be last after forms/layout proven |

**Order:** Flash → Theming → Localization → Layout → Accessibility → Forms → Data Display. Each is one PR, coexistence allows `daisyui`/`mary`/`flasher` to stay until its area is clean.

## Phasing (gradual, no break — FB792 DD-4)

1. **Phase 0 — Spec & Install (done):** FB792/J68GZ/8XMYS/52O1I amended, `tallstackui/tallstackui ^4.0` + `@tailwindcss/forms` installed, `app.css`/`head.blade.php` configured, `config/tallstackui.php` published, coexistence (all 3 stacks live).
2. **Phase 1 — Quick wins (Rank 1-3):** Toast → Theme → Locale (1 PR each, 1-2 days, no breaking).
3. **Phase 2 — Chrome (Rank 4-5):** Layout → Accessibility (1-2 PRs, visual regression).
4. **Phase 3 — Bulk (Rank 6-7):** Forms → Data Display (per-module PRs, 2-3 weeks, `FindComponent` to track).
5. **Phase 4 — Removal:** `grep -R "daisyui\|mary\|php-flasher"` clean → remove from `composer.json`/`package.json`/`app.css` imports.

## Verification per PR

- `composer audit && npm audit` clean, `vendor/bin/pint --dirty --test`, `npm run build` success, `python3 scripts/scan_doc_links.py` `broken 0`, visual `data-theme`/`drawer`/`toast` manual check, `php artisan test --filter={Module}` for affected Livewire.
- Use `php artisan tallstackui:find-component --help` to audit remaining `x-mary`/`daisyui` usage.

## Testing — Spec-Driven Livewire Tests (while migrating, write tests)

> **Principle:** `tester` subagent owns this — `pest-testing` + `test-writing` per `AGENTS.md` Verification Strategy. Every test traces to `FR-*`/`NFR-*`/`UC-*` in `FB792`/`8XMYS`/`52O1I`; no orphan tests.

- **Format:** `describe("{SpecID}: Component")` + `it("{ReqID}: renders TallstackUI ...")`; e.g., `it("FB792-FR-TS6a: ThemeSwitcher renders x-ts-theme-switch and not mary dropdown")`, `it("52O1I-FR-T2: ThemeSwitcher dispatches theme-changed and persists cookie")`, `it("8XMYS-FR-R1: layout renders x-ts-side-bar and table is accessible")`.
- **Tooling:** `Livewire::test(ThemeSwitcher::class)->assertSeeHtml('<x-ts-theme-switch')`, `->call('setTheme','dark')->assertDispatched('theme-changed')`, `->assertCookie('theme','dark')`, `->assertDontSee('<x-mary-dropdown')` for migrated module; `->assertSeeLivewire()` for chrome.
- **Per-area examples:**
  - **Flash/Toast:** `it("J68GZ-FR-D10a: success action shows x-ts-toast and not @flasher_render")` — call `flash` replacement `Toast::success()` then `Livewire::test(SchoolEditor::class)->call('save')->assertSeeHtml('x-ts-toast')`.
  - **Theming:** `it("FB792-FR-TS6a: setTheme updates cookie and dispatches")` + `it("52O1I-NFR-A4: theme switch announces via aria-live")`.
  - **Localization:** `it("52O1I-FR-L5: LangSwitcher renders x-ts-dropdown for EN/ID")`.
  - **Layout:** `it("8XMYS-FR-L2: app layout uses x-ts-layout and x-ts-side-bar")` + `it("8XMYS-FR-A3: drawer overlay closes on Escape")`.
  - **Accessibility:** `it("8XMYS-NFR-A1: skip-link and focus:ring preserved via TallstackUI")`.
  - **Forms:** `it("FB792-NFR-DEP5: DepartmentManager form renders x-ts-input with invalidate")` — `->set('form.name','x')->call('save')->assertHasErrors('form.name')` + `assertSeeHtml('x-ts-input')`.
  - **Data Display:** `it("FB792-FR-TS6a: RubricManager table renders x-ts-table and not x-mary-table")`.
- **Coverage:** Spec coverage, not line coverage — one test per FR trace, remove orphan; run `vendor/bin/pest --testsuite={Module}` per PR, full suite only on demand.
- **When:** Write test **in same PR** as migration (tester co-owns PR with builder); builder migrates Blade, tester adds `tests/**/Livewire/*Test.php` with spec IDs, both in one `git diff` for atomic review.

## GitHub Issues

Epic + 7 area issues filed (see `gh issue list --label tallstackui`). Each issue traces to FB792 FR-TS6/DD-4 and 8XMYS/52O1I, with acceptance criteria, files, verification, and **spec-driven Livewire test checklist** (see above).

## References

- FB792-tech-stack.md (FR-TS5 deprecated, FR-TS6 TallstackUI v4, NFR-DEP5, DD-4)
- 52O1I-branding-theme-locale.md (FR-T2/FR-L5 TallstackUI-first, DD-6)
- 8XMYS-layout-and-ui-system.md (DD-4 drawer deprecated)
- tallstackui.com/docs (Layout, Theme Switch, Toast, Dropdown, Accessibility)
