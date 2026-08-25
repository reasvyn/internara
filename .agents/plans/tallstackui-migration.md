# TallstackUI Migration Plan — DaisyUI/MaryUI/PHPFlasher → TallstackUI v4

> **STATUS: MIGRATION COMPLETE (2026-08-24); PACKAGES REMOVED (0.15.0).** `x-mary` usage across app + views = **0**
> (`grep -rn x-mary resources app | grep -v vendor` → only intentional `not->toContain` test
> guards). Flash/Toast, Theming, Localization, Layout, Accessibility, Custom Core, Forms
> (incl. select/table/header/card/datepicker/stat/alert/file/collapse/choices/progress), Confirm
> Dialog (`x-ts-dialog`) all migrated. Published vendor views tracked under
> `resources/views/vendor/ts-ui` + `lang/vendor/ts-ui`. DaisyUI/mary/flasher packages **deleted**
> from manifests + `app.css` in 0.15.0 (commit 53413f295) — self-hosted palette + shims bridge the
> 169 remaining legacy class tokens until `x-ts-*` fully replaces them; optional command-palette
> backend if spotlight replacement is wanted is the only open follow-up.
>
> The tables below are the historical migration record — counts describe the pre-migration state.
>
> **Last updated:** 2026-08-25 **Changes:** sync — mark Phase 4 removal DONE (packages deleted 0.15.0); historical counts kept as record
> **Governing specs:** FB792 (FR-TS6, DD-4), J68GZ (FR-D10a), 8XMYS (DD-4), 52O1I (FR-T2/FR-L5, DD-6)
> **Principle:** TallstackUI-first — always use `<x-ts-*>` (FB792 FR-TS6a); legacy stacks are removed, not fallbacks

## Mapping — Areas to Migrate

| Area | Current (legacy) | Count | TallstackUI Replacement | Files | Impact | Effort |
|------|------------------|-------|-------------------------|-------|--------|--------|
| **Flash/Toast** | `php-flasher/flasher-laravel` `flash()->success/error/warning` + `@flasher_render` | 231 `flash()` calls in 64 Livewire files + 1 `@flasher_render` in `base.blade.php` (165 success/52 error/11 warning) | `TallStackUi::toast()->send()->success/error` / `<x-ts-toast />` + `<x-ts-alert />` (Interactions/Toast, UI/Alert) | `app/**`, `resources/views/core/layouts/base.blade.php`, `config/flasher.php` | High — every action feedback | Low — 1:1 replace, no UI layout change | **High** |
| **Theming** | DaisyUI `data-theme` (`light`/`dark`/`system`) + `Theme::cssVariables()` + `ThemeSwitcher` mary dropdown + Alpine `theme-changed` | 42 theme hits, `ThemeSwitcher` 1 component, `base.blade.php` 2 CSS blocks | `x-ts-theme-switch` + `TallStackUi::darkTheme()` helper + `config/tallstackui.php` `darkTheme` | `app/Settings/Livewire/ThemeSwitcher.php`, `settings.livewire.theme-switcher`, `base.blade.php`, `resources/js/app.js` | Medium — per-browser pref, visible everywhere | Low — single component, CSS vars remain | **High** |
| **Localization** | `LangSwitcher` mary dropdown + `Locale::set()` + `SetLocaleMiddleware` (EN/ID) | 34 locale hits, `LangSwitcher` 1 component | `x-ts-dropdown` / `x-ts-select.styled` for EN/ID (TallstackUI-first, fallback mary during coexistence) | `app/Settings/Livewire/LangSwitcher.php`, `settings.livewire.lang-switcher` | Medium — bilingual, per-browser | Low — single component | **High** |
| **Layout** | DaisyUI `drawer` (`drawer-toggle`, `drawer-side`, `drawer-content`, `lg:drawer-open`), `navbar`, `sidebar` 7 files | 14 layout hits, 7 layout files (`app.blade.php`, `sidebar`, `header`, `base`, `guest`) + 268 `bg-base` + 449 DaisyUI-like aggregate | `x-ts-layout` + `x-ts-side-bar` + `x-ts-side-bar.item` + `x-ts-layout.header` (UI/Layout, SideBar) | `resources/views/core/layouts/**` | High — chrome on every page | Medium — layout refactor, needs visual regression | **Medium-High** |
| **Accessibility** | DaisyUI `focus:ring`, `drawer-overlay` aria, `skip-link`, `focus:not-sr-only` in `base.blade.php` | ~30 a11y tokens, 4 shell files | TallstackUI built-in WCAG (focus, ARIA, `x-ts-*` a11y) + `ThemeSwitch`/`Layout` a11y; replace custom `focus:` with TallstackUI tokens | `base.blade.php`, `app.blade.php`, `sidebar`, `header` + `tailwindcss` skill `accessibility-wcag` | High — WCAG 2.1 AA | Medium — audit + token swap, no logic | **Medium** |
| **Forms** | `maryUI` form: `x-mary-input` 138 + `x-mary-select` 58 + `checkbox`/`toggle`/`textarea`/`file`/`datepicker` + DaisyUI `btn` 342 `btn-sm`/`btn-ghost` | 322 form hits, 1222 mary total (138 input, 58 select, 342 button) | `x-ts-input`, `select.styled`, `checkbox`, `toggle`, `textarea`, `upload`, `date`, `button` (Form/*, UI/Button) | `resources/views/**/*.blade.php` (top: `user-manager` 40, `system-setting` 38), `app/**/Livewire/Forms/*.php` | High — 18 modules, 300+ forms | High — many files, validation `invalidate` handling | **Medium** |
| **Data Display** | `maryUI` `table` 67 + `card` 98 + `badge` 3 + `modal` 45 + `dropdown` 22 + `tabs` 5 + DaisyUI `card` 81 + `badge` 181 | ~400 display hits (subset of 1222) | `x-ts-table`, `card`, `badge`, `modal`, `dropdown`, `tab` (UI/*, Interactions/Dialog) | `resources/views/**` (top: `logbook-manager` 31, `partnership-manager` 31), `app/**/Livewire/*Manager.php` | High — every manager/index | High — 1222 components, per-module | **Low-Medium** |
| **Custom Core** | `resources/views/core/ui/*` 13 + `widgets/*` 5 + `layouts/*` 7 = **25** custom Blade components (`<x-core::ui.*>`/`<x-core::widgets.*>`), 179 `<x-core` hits | 25 files, 179 hits (`record-manager` 92 lines, `stat-card`, `action-button` wraps `<x-mary-button>`) | `x-ts-*` wrappers (card, modal, badge, table via `x-ts-*` customization, `core/ui` → `x-ts` per TallstackUI-first) | `resources/views/core/**` (record-manager used by 14+ managers, stat-card by dashboards) | High — every manager/dashboard via wrapper, force-multiplier | Medium — 25 files, wrapper refactor, then 14+ managers auto-clean | **Medium** |

Counts via: `grep -r "flash()->" app | wc -l` **231** (64 files, 165 success/52 error/11 warning) + `@flasher_render` 1, `grep -r "<x-mary" resources | wc -l` **1222** across 129 files (342 button/201 icon/138 input/98 card/67 table), `grep -r "bg-base|badge|btn-" resources | wc -l` **449** DaisyUI-like, `grep -r "<x-core" resources | wc -l` **179** across 25 core files.

## Impact-to-Effort Scoring (ratio = Impact / Effort, higher first)

| Rank | Area | Impact (1-5) | Effort (1-5) | Ratio | Rationale |
|------|------|--------------|--------------|-------|-----------|
| 1 | **Flash/Toast** | 5 | 1 | 5.0 | 231 calls in 64 files but 1 pattern (`flash()->` → `toast()->send()`), no layout, immediate win, unblocks PHPFlasher removal; top file `UserManager` 15 calls |
| 2 | **Theming** | 4 | 1 | 4.0 | Single component, CSS vars already exist, TallstackUI `ThemeSwitch` drop-in, low risk |
| 3 | **Localization** | 3 | 1 | 3.0 | Single component, same pattern as theming, EN/ID only |
| 4 | **Layout** | 5 | 3 | 1.67 | Every page chrome, needs drawer→`x-ts-layout` refactor + responsive test, but isolated to 7 files + 268 `bg-base` |
| 5 | **Custom Core** | 5 | 3 | 1.67 | 25 files, 179 hits — force-multiplier: `record-manager` (92 lines) used by 14+ managers, `stat-card`/`action-button` by dashboards; one wrapper refactor cleans many managers |
| 6 | **Accessibility** | 5 | 3 | 1.67 | WCAG critical, but largely token swap + audit, follows layout |
| 7 | **Forms** | 5 | 4 | 1.25 | 322 hits (138 input/58 select/342 button), 18 modules, validation `invalidate` + `wire:model` coupling, per-module PRs; top `user-manager` 40, `system-setting` 38 |
| 8 | **Data Display** | 5 | 5 | 1.0 | Largest surface (~400 display + 67 table/98 card/45 modal), per-manager, should be last after forms/layout proven; top `logbook-manager` 31, `partnership-manager` 31 |

**Order:** Flash → Theming → Localization → Layout → **Custom Core** → Accessibility → Forms → Data Display. Each is one PR (Custom Core before Accessibility, as wrapper refactor unblocks many managers), coexistence allows `daisyui`/`mary`/`flasher`/`core` to stay until its area is clean.

## Phasing (gradual, no break — FB792 DD-4)

1. **Phase 0 — Spec & Install (done):** FB792/J68GZ/8XMYS/52O1I amended, `tallstackui/tallstackui ^4.0` + `@tailwindcss/forms` installed, `app.css`/`head.blade.php` configured, `config/tallstackui.php` published, coexistence (all 3 stacks live).
2. **Phase 1 — Quick wins (Rank 1-3):** Toast (pilot `SchoolEditor` done, 231→230 remaining) → Theme → Locale (1 PR each, 1-2 days, no breaking).
3. **Phase 2 — Chrome (Rank 4-6):** Layout → **Custom Core** (`record-manager` 92 lines, 14+ managers) → Accessibility (2-3 PRs, visual regression, wrapper refactor first).
4. **Phase 3 — Bulk (Rank 7-8):** Forms (322 hits, 138 input) → Data Display (~400 hits, 67 table) — per-module PRs, 2-3 weeks, `FindComponent` to track.
5. **Phase 4 — Removal (DONE 0.15.0):** ~~Do **not** remove `daisyui`/`mary`/`php-flasher` until explicitly ordered~~ — removal was explicitly approved and completed in 0.15.0 (commit 53413f295): packages deleted from manifests, `config/mary.php`/`config/flasher.php` removed, `@plugin daisyui`/`@source mary` stripped from `app.css`.

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

Epic + 8 area issues (Flash, Theming, Localization, Layout, **Custom Core**, Accessibility, Forms, Data Display) filed (see `gh issue list --search "tallstackui"`). Each traces to FB792 FR-TS6/DD-4 and 8XMYS/52O1I, with acceptance criteria, files, verification, and **spec-driven Livewire test checklist** (see above). Custom Core is rank 5, force-multiplier for 14+ managers.

## References

- FB792-tech-stack.md (FR-TS5 deprecated, FR-TS6 TallstackUI v4, NFR-DEP5, DD-4)
- 52O1I-branding-theme-locale.md (FR-T2/FR-L5 TallstackUI-first, DD-6)
- 8XMYS-layout-and-ui-system.md (DD-4 drawer deprecated)
- tallstackui.com/docs (Layout, Theme Switch, Toast, Dropdown, Accessibility)
