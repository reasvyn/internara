# Homepage & Global Theming — Public Landing & Semantic Token Compliance

> **Spec ID:** K8HP1

## Description

Specification of Internara's public homepage (`/`) and the global semantic-theming compliance contract that extends the existing branding/theme system (52O1I) to every page. The homepage is the sole guest entry point — unauthenticated visitors see registration availability and login; authenticated users are redirected to their dashboard, uninstalled instances to setup. Theming is already built (52O1I `Theme::cssVariables()`, `base.blade.php` injection, `data-theme` + `.dark` dual signals) — this spec makes its application **mandatory and verifiable** on the homepage and on every other page chrome.

---

## 1. Problem Statements

### PS-1 — Homepage Has No Spec

The homepage (`App\Modules\User\Livewire\HomePage` → `resources/views/livewire/user/home-page.blade.php`, route `GET /` name `home` in `routes/web/user.php`) is implementation-only. No spec defines its behavior, states, or theming. Without a requirement ID, audits flag it as orphan, tests lack traceability, and future changes lack an SSOT.

### PS-2 — Theming Already Built but Not Mandated on Homepage or Global Pages

`52O1I-branding-theme-locale.md` builds the palette pipeline (4 brand colors, 6 presets, `Theme::cssVariables()` cached 1h, `base.blade.php` inline `<style>` for `light`/`dark`). `8XMYS-layout-and-ui-system.md` shells all pages through `core::layouts.base/guest/app`. Yet neither spec **requires** the homepage or arbitrary feature pages to use only semantic tokens (`--color-primary/secondary/accent`, `--color-base-100/200/300/content`, `--color-info/success/warning/error` via `bg-primary`, `bg-base-200`, `text-base-content`, etc.). Hardcoded Tailwind palette literals (`bg-white`, `bg-gray-50`, `text-gray-500`, `bg-blue-50`, `text-blue-600`, `ring-white/10`) bypass the palette and break dark mode / brand recoloring.

### PS-3 — Inconsistent Dark-Mode & Brand Recoloring

Pages that bypass semantic tokens render white surfaces on dark (`bg-white` stays white) or ignore brand preset changes (`bg-blue-50` never follows `primary_color`). Users who switch presets or dark mode see a fractured UI — homepage hero correct, cards/modals/pulse widgets not.

### PS-4 — Guest Chrome Must Stay Theme-Aware

The guest shell (`resources/views/ui/layouts/guest.blade.php`) hosts homepage + auth pages. Its header/footer/divider must track the same `data-theme`/`.dark` contract as the authenticated shell; otherwise public pages diverge from app pages.

---

## 2. Goals & Non-Goals

### Goals

| ID | Goal |
|----|------|
| G1 | Spec homepage fully (route, redirects, states, layout, i18n) |
| G2 | Require homepage to use **only** semantic tokens (no hardcoded Tailwind palette) and respect `data-theme` + `.dark` |
| G3 | Require **every** page (guest, auth, app, setup) to use only semantic tokens — extend 52O1I/8XMYS with an auditable invariant |
| G4 | Keep dark-mode and brand-preset changes reflected on homepage without rebuild (inline `Theme::cssVariables()` already does — verify) |
| G5 | Keep homepage accessible (WCAG 2.1 AA) — tagline gradient, card contrast, focus order, i18n |

### Non-Goals

| ID | Non-Goal |
|----|----------|
| NG1 | New palette computation — reuse `Theme`, `Color`, `base.blade.php` pipeline from 52O1I |
| NG2 | New theme switcher — reuse `core::ui.theme-switch` + `app.js applyTheme()` from 52O1I |
| NG3 | CMS-editable homepage content — tagline comes from `brand('tagline')` / `common.app_tagline`, copy from `lang/{en,id}/user.php` |
| NG4 | SEO / OpenGraph beyond existing `<head>` |
| NG5 | Visual regression harness (manual + `npm run build` only) |

---

## 3. User Stories / Use Cases

### UC-1 — Visitor Opens Homepage (Unauthenticated, Installed)

**Actor:** Unauthenticated visitor on installed instance
**Preconditions:** `SetupEntity::get()->isInstalled() === true`, `auth()->check() === false`, `GET /`
**Flow:**
1. `HomePage::mount(ReadRegistrationAvailabilityAction)` executes (no redirect)
2. `ReadRegistrationAvailabilityAction::execute()` returns `['status' => 'open|upcoming|closed|not_configured', 'start_date'?, 'end_date'?]`
3. `HomePage::render()` returns `view('livewire.user.home-page')->layout('ui::layouts.guest', ['title' => __('user.home.page_title')])`
4. Guest shell renders sticky header (brand + theme-switch + lang-switcher), hero (brand mark + pills + tagline gradient + description), wave divider (`text-base-200`), cards section (`bg-base-200`) with registration card and login card, and feature highlights (3 cards)
**Postconditions:** Homepage visible without auth; theme switcher and lang switcher work; no full reload on internal links (`wire:navigate`)

### UC-2 — Authenticated User Hits Homepage

**Actor:** Authenticated user
**Preconditions:** `auth()->check() === true`
**Flow:**
1. `HomePage::mount()` calls `$this->redirectRoute('dashboard')` and returns
**Postconditions:** User redirected to `GET /dashboard` (which then role-routes); homepage not rendered

### UC-3 — Uninstalled Instance Hits Homepage

**Actor:** Any visitor on fresh install
**Preconditions:** `SetupEntity::get()->isInstalled() === false`
**Flow:**
1. `HomePage::mount()` calls `$this->redirectRoute('setup')` and returns
**Postconditions:** Redirected to setup wizard; homepage not rendered

### UC-4 — Registration Status Variants

**Actor:** Visitor on homepage
**Flow:** Registration card switches on `$registration['status']`:
- `open`: badge `success` + period (`j F Y`) + `x-ts-button` to `route('apply')`
- `upcoming`: badge `info` + period + `x-ts-alert` info `registration_not_open_yet`
- `closed`: badge `warning` + `x-ts-alert` warning `registration_closed_desc`
- default (`not_configured`/unknown): neutral badge + neutral alert
**Postconditions:** Correct CTA per enrollment window; translated strings in EN/ID

### UC-5 — Visitor Switches Theme on Homepage

**Actor:** Visitor on homepage
**Flow:**
1. Clicks `x-ui::components.theme-switch` (light/dark/system)
2. `app.js applyTheme(mode)` sets `data-theme` + `.dark` + `theme` cookie + `localStorage dark-theme`; `base.blade.php` inline style swaps `light`/`dark` variable maps
3. Hero gradient (`from-primary/8 via-base-100 to-secondary/8`), blobs (`bg-primary/10` etc.), cards (`bg-base-100`, `border-base-content/10`), wave (`text-base-200`), icon wells (`from-primary/15 to-primary/5` etc.) all recolor via CSS variables — no rebuild
**Postconditions:** Theme applied <100ms, persists across reloads, no DB write

### UC-6 — Admin Changes Brand Preset → Homepage Updates

**Actor:** Admin changes `primary_color` via Branding form
**Flow:** `SaveSystemSettingsAction` → `Settings::forget()` invalidates `theme_css_variables` cache (checks `theme_cache_keys`) → next `GET /` recomputes `Theme::cssVariables()` → homepage reflects new brand
**Postconditions:** Homepage palette updated on next render without `cache:clear` or deploy

### UC-7 — Any Page Uses Only Semantic Tokens (Global Invariant)

**Actor:** Developer adds/edits a page
**Flow:** Blade markup uses only: `base-100/200/300/content`, `primary/secondary/accent` (+ `-content`), `neutral/info/success/warning/error` (+ `-content`), `base-content` opacities (`/10`, `/50`, `/60`), and Daisy-shim classes (`.card`, `.btn`, `.badge`, `.alert` mapped to variables). No hardcoded Tailwind palette (`bg-white`, `bg-gray-50`, `text-gray-500`, `bg-blue-50`, `ring-white/10`, etc.) outside `resources/views/vendor/` and `bg-black/40` backdrop overlays.
**Postconditions:** Page tracks brand and dark mode; auditable via grep

---

## 4. Functional Requirements

### Homepage — Routing & Lifecycle

| ID | Requirement |
|----|-------------|
| FR-HM-01 | `GET /` must be defined in `routes/web/user.php` as `Route::livewire('/', HomePage::class)->name('home')` |
| FR-HM-02 | `HomePage` must be `final class HomePage extends Component` with `public array $registration = []` |
| FR-HM-03 | `HomePage::mount(ReadRegistrationAvailabilityAction $action)` must DI-inject the action; if `!SetupEntity::get()->isInstalled()` call `$this->redirectRoute('setup')` and return |
| FR-HM-04 | If `auth()->check()` call `$this->redirectRoute('dashboard')` and return (before fetching availability) |
| FR-HM-05 | Otherwise set `$this->registration = $action->execute()` |
| FR-HM-06 | `HomePage::render(): View` must return `view('livewire.user.home-page')->layout('ui::layouts.guest', ['title' => __('user.home.page_title')])` |
| FR-HM-07 | Homepage guest shell must include sticky header with `x-ui::components.brand` (link `wire:navigate href="/"`) + `x-ui::components.theme-switch` + `livewire:settings.lang-switcher`, `main#main-content`, and footer `x-ui::components.credits` |
| FR-HM-08 | Hero section must render brand mark (`x-ui::components.brand size=xl`), 3 feature pills (`x-ts-badge` primary/secondary/success), tagline (`brand('tagline') ?: __('common.app_tagline')`) with `bg-gradient-to-br from-base-content to-base-content/60 bg-clip-text text-transparent`, hero description `__('user.home.hero_desc')`, and wave SVG divider `text-base-200` |
| FR-HM-09 | Registration card must branch on `$registration['status']` exactly as UC-4 (open/upcoming/closed/default) with the specified badges/alerts/CTA and `Carbon::parse(...)->translatedFormat('j F Y')` |
| FR-HM-10 | Login card must render `__('user.home.login_title/desc/action/no_account')` and `x-ts-button` `wire:navigate href=route('login')` color secondary |
| FR-HM-11 | Feature highlights must render 3 cards (logbook/supervision/certificate) with icon wells `from-{primary,secondary,accent}/10 to-{primary,secondary,accent}/5` and texts `__('user.home.feature_*_title/desc')` and `__('user.home.features_title/subtitle')` |
| FR-HM-12 | All homepage strings must use `__()` and exist in both `lang/en/user.php` and `lang/id/user.php` |

### Homepage — Theming (Semantic Tokens Only)

| ID | Requirement |
|----|-------------|
| FR-HT-01 | Hero wrapper must use `from-primary/8 via-base-100 to-secondary/8 bg-gradient-to-br` and blobs `bg-primary/10`, `bg-secondary/10`, `bg-accent/5` — no hardcoded hex or `bg-blue-*`/`bg-gray-*` |
| FR-HT-02 | Wave divider SVG must use `text-base-200 fill="currentColor"` |
| FR-HT-03 | Cards section must use `bg-base-200`; cards `bg-base-100 border-base-content/10 hover:border-{primary,secondary}/30 shadow-lg`, card wells `from-{primary,secondary,accent}/15 to-{primary,secondary,accent}/5` + `ring-base-content/5` (or `ring-base-300/50`) — not `ring-white/10` |
| FR-HT-04 | Icon + heading colors must be `text-primary` / `text-secondary` / `text-accent` (never `text-blue-*` / `text-gray-*`) |
| FR-HT-05 | Body copy must be `text-base-content/60` or `/55` / `/50` / `/40`; badges/alerts/buttons must use TallstackUI semantic `color="primary|secondary|success|info|warning"` — never hardcoded palette |
| FR-HT-06 | Header/footer/divider must be `bg-base-100/80`, `border-base-content/10`, `text-base-content` — not `bg-white` / `border-gray-*` / `text-gray-*` |
| FR-HT-07 | Skip-link (in `base.blade.php`) must be `focus:bg-base-100 focus:text-base-content dark:focus:bg-base-200 dark:focus:text-base-content` — not `focus:bg-white focus:text-gray-900` |

### Global Theming — All Pages

| ID | Requirement |
|----|-------------|
| FR-GT-01 | Every non-vendor Blade view (`resources/views/**/*.blade.php` excluding `resources/views/vendor/**`) must use **only** semantic color tokens: `base-100/200/300/content`, `primary/secondary/accent` (+ `-content`), `neutral/info/success/warning/error` (+ `-content`), `base-content` opacities, and Daisy-shim component classes (`.card`, `.btn`, `.badge`, `.alert`, `.table` mapped in `app.css @layer components`) |
| FR-GT-02 | Forbidden literals outside `vendor/` and `bg-black/30–40` backdrop overlays: `bg-white`, `bg-black`, `bg-gray-*`, `bg-slate-*`, `bg-zinc-*`, `bg-blue-*`, `bg-green-*`, `bg-red-*`, `text-white` (except `text-white` inside `bg-primary` buttons where `text-primary-content` is equivalent), `text-gray-*`, `text-blue-*`, `border-gray-*`, `ring-white/*`, `from-gray-*`, `to-gray-*`, `dark:bg-gray-*`, `dark:text-gray-*`, `focus:bg-white`, `focus:text-gray-*` |
| FR-GT-03 | `bg-black/30` / `bg-black/40` backdrop overlays for modal/guide dismiss layers are allowed (neutral scrim, not a palette choice) |
| FR-GT-04 | `resources/css/app.css` remains SSOT for `@theme` tokens and `[data-theme='dark']` overrides; `Theme::cssVariables()` inline `<style>` in `base.blade.php` remains the dynamic brand bridge (cached 1h, invalidated via `theme_cache_keys`) — no ad-hoc `<style>` per page |
| FR-GT-05 | `data-theme` + `.dark` dual signals (52O1I FR-T1/FR-T3) must stay in sync via `app.js applyTheme()` + `base.blade.php` cookie read; no page may set its own theme signal |
| FR-GT-06 | Guest, auth (`auth::layouts.auth`), setup (`setup::layouts.setup`), and app (`ui::layouts.app`) shells must all inherit `ui::layouts.base` so the CSS-variable `<style>` and dual signals apply uniformly |

---

## 5. Non-Functional Requirements

| ID | Requirement |
|----|-------------|
| NFR-P1 | Homepage `GET /` TTFB < 200 ms cache-hit (availability query cached; theme vars cached 1h) |
| NFR-P2 | Theme toggle → repaint < 100 ms (client-side `applyTheme`, no server roundtrip) |
| NFR-P3 | Brand preset change → homepage reflects on next `GET /` without `cache:clear` (cache invalidation via `theme_cache_keys`) |
| NFR-A1 | Homepage must meet WCAG 2.1 AA: tagline gradient contrast via `from-base-content`, badge/CTA not color-only (icon + text), skip-link operable, `aria-hidden` on decorative blobs/wave |
| NFR-A2 | Homepage must be keyboard-navigable: header links, CTA buttons, theme/lang switchers reachable in logical order |
| NFR-L1 | Every `__('user.home.*')` key exists in EN and ID; `Carbon::translatedFormat('j F Y')` respects `app()->getLocale()` |
| NFR-M1 | Grep invariant: `grep -R "bg-white\|text-gray-\|border-gray-\|bg-gray-\|bg-blue-\|ring-white" resources/views --exclude-dir=vendor` returns no matches except allowed `bg-black/30`/`bg-black/40` scrims |
| NFR-M2 | `npm run build` must pass (Tailwind v4 + Vite); `vendor/bin/pint --dirty --test` must pass for any PHP touch |

---

## 6. API / Data Contracts

### 6.1 Route

```php
// routes/web/user.php
use App\Modules\User\Livewire\HomePage;

Route::livewire('/', HomePage::class)->name('home'); // GET /
```

### 6.2 HomePage Livewire

```php
// app/Modules/User/Livewire/HomePage.php
declare(strict_types=1);

namespace App\Modules\User\Livewire;

use App\Modules\Enrollment\Domain\Registration\Actions\ReadRegistrationAvailabilityAction;
use App\Modules\Setup\Entities\SetupEntity;
use Illuminate\View\View;
use Livewire\Component;

final class HomePage extends Component
{
    public array $registration = [];

    public function mount(ReadRegistrationAvailabilityAction $action): void;
    public function render(): View; // view('livewire.user.home-page')->layout('ui::layouts.guest', ['title' => __('user.home.page_title')])
}
```

### 6.3 ReadRegistrationAvailabilityAction Return

```php
// already defined in 52O1I / enrollment — consumed as:
[
  'status' => 'open' | 'upcoming' | 'closed' | 'not_configured',
  'start_date' => ?string|Carbon, // when open/upcoming
  'end_date' => ?string|Carbon,
]
```

### 6.4 Semantic Token Inventory (from 52O1I + app.css)

```
Dynamic (Theme::cssVariables() → html[data-theme]): --color-primary/--color-primary-content/--brand-primary,
  --color-secondary/--color-secondary-content/--brand-secondary,
  --color-accent/--color-accent-content/--brand-accent,
  --color-base-100/200/300/content
Static (@theme + [data-theme='dark']): --color-neutral, --color-info/success/warning/error (+ -content)
Utilities: bg-primary, text-primary, border-primary, from-primary/8, ring-base-content/10, etc.
Forbidden: bg-white, bg-gray-*, text-gray-*, border-gray-*, bg-blue-*, text-blue-*, ring-white/*
Allowed exception: bg-black/30, bg-black/40 (modal/guide scrim)
```

### 6.5 Guest Shell (inherits base)

```blade
{{-- resources/views/ui/layouts/guest.blade.php --}}
<x-ui::layouts.base :$title>
  <div class="bg-base-200 flex min-h-screen flex-col">
    <header class="bg-base-100/80 border-base-content/10 sticky top-0 z-50 border-b backdrop-blur-sm">
      {{-- brand (wire:navigate /) + theme-switch + lang-switcher --}}
    </header>
    <main id="main-content" class="flex flex-1 flex-col">{{ $slot }}</main>
    <footer class="border-base-content/10 mt-auto border-t py-8"><x-ui::components.credits /></footer>
  </div>
</x-ui::layouts.base>

{{-- base.blade.php injects Theme::cssVariables() --}}
<html lang="{{ app()->getLocale() }}" data-theme="{{ cookie('theme','system') }}" @if(cookie==='dark') class="dark" @endif>
  <style>html[data-theme='light']{--color-primary:…} html[data-theme='dark']{--color-primary: lightened 40%}</style>
```

---

## 7. Design Decisions

### DD-1 — New Spec Instead of Amending 52O1I/8XMYS

**Decision:** Create K8HP1 for homepage + global invariant; keep 52O1I as palette pipeline SSOT and 8XMYS as shell SSOT. K8HP1 cross-references both.
**Rationale:** Homepage was orphan (no spec). Global invariant is audit-facing (grep-enforceable) and benefits from its own FR-GT-* IDs rather than scattering across two shipped specs.
**Alternatives rejected:** Patch FRs into 52O1I (§4 Theme) — would hide homepage lifecycle; patch into 8XMYS §4 Layout — would conflate shell with content.

### DD-2 — Grep-Enforceable Invariant Over Visual Tooling

**Decision:** FR-GT-01/FR-GT-02 enforced by a literal grep for forbidden Tailwind palette literals (vendor/backdrop excluded).
**Rationale:** No visual regression harness exists (NG5). Grep is zero-cost, CI-friendly, and maps 1:1 to the semantic-token contract.
**Trade-off:** Grep misses semantic misuse (e.g., `bg-primary` on wrong element) — acceptable; caught in review.

### DD-3 — Keep `bg-black/40` Scrim as Allowed Exception

**Decision:** Modal/guide backdrop `bg-black/40` (and `bg-black/30` drawer overlay) remain allowed.
**Rationale:** Scrims are view-obscuring overlays, not palette choices; semantic `bg-base-300/50` would be too weak. They do not track brand and should not.

### DD-4 — No New Palette Computation

**Decision:** Reuse `Theme::cssVariables()` / `Color::computeBaseShades/computeDarkShades/lighten` / `base.blade.php` injection exactly.
**Rationale:** Pipeline already solves brand + dark mode + caching + invalidation. K8HP1 only mandates its consumption.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Homepage spec exists and traces to code | 100% | `docs/specs/K8HP1-homepage-and-theming.md` FR-HM-* ↔ `HomePage.php` + `home-page.blade.php` + `routes/web/user.php` |
| Homepage uses only semantic tokens | 0 forbidden literals | `grep` NFR-M1 |
| All pages use only semantic tokens | 0 forbidden literals outside vendor/scrim | `grep` NFR-M1 repo-wide |
| Dark-mode repaint | < 100 ms | manual `applyTheme('dark')` → no white flash |
| Brand preset change reflects on homepage next load | < 1 request | change `primary_color` → `GET /` shows new hero/card colors |
| Homepage a11y Lighthouse | ≥ 95 | `lighthouse --only-categories=accessibility` |
| `npm run build` + `pint --dirty --test` | pass | CI gates |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|------------------|
| [branding-theme-locale.md](52O1I-branding-theme-locale.md) | `Brand`, `Theme`, `Color`, presets, `Theme::cssVariables()` (cached 1h), `theme_cache_keys`, `theme-switch`, `applyTheme()` |
| [layout-and-ui-system.md](8XMYS-layout-and-ui-system.md) | `ui::layouts.base/guest/app`, `ui::components.brand/theme-switch`, `wire:navigate`, a11y chrome |
| [installation.md](8NZAU-installation.md) + [setup-wizard.md](VEJCX-setup-wizard.md) | `SetupEntity::isInstalled()` gate |
| [registration.md](MBB5R-registration.md) | `ReadRegistrationAvailabilityAction` |
| [settings-infrastructure.md](YB22J-settings-infrastructure.md) | `Settings::forget()` cache invalidation |

### Build Guide

1. Register spec in `docs/specs/index.md` (Phase 3 row).
2. Fix `livewire/user/home-page.blade.php` ring + any semantic drift.
3. Fix `ui/layouts/base.blade.php` skip-link tokens + `ui/layouts/guest.blade.php` if drifted.
4. Sweep `resources/views/**/*.blade.php` (minus `vendor/`) for forbidden literals; replace with semantic equivalents (`bg-gray-50→bg-base-200`, `text-gray-500→text-base-content/50`, `bg-blue-50→bg-info/10`, `text-blue-600→text-info`, `ring-white/10→ring-base-content/10`).
5. `npm run build` + `vendor/bin/pint --dirty --test` + manual toggle (light/dark/system + preset change).

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [authentication.md](YB7RG-authentication.md) | `auth::layouts.auth` inherits `ui::layouts.base` — already themed; login page `route('login')` CTA from homepage |
| 2 | [dashboard.md](CKKZC-dashboard.md) | `HomePage::mount` redirects authed users to `dashboard`; dashboards render in `ui::layouts.app` (same theme pipeline) |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
|----|-----------------------------------|--------|-------|----------|
| A-1 | Until a visual regression harness exists, `bg-black/40` scrims stay literal — not semantic — by intentional exception (DD-3). | Accepted | Maintainer | — |
| R-1 | If a feature page must use a literal (e.g., `bg-white` for print/PDF), it must be scoped under `resources/views/pdf/` or `resources/views/vendor/` or carry an inline `<!-- spec:exception FR-GT-02 -->` comment and be allow-listed in the grep. | Open | Maintainer | — |
| OQ-1 | Should TallstackUI components that still emit `bg-gray-50` internally (e.g., `vendor/tallstackui`) be shimmed at `@layer components` or left as vendor-excluded? Currently excluded. | Open | Maintainer | — |

## Quick References

- `app/Modules/User/Livewire/HomePage.php` — homepage Livewire (mount redirects + availability)
- `resources/views/livewire/user/home-page.blade.php` — homepage Blade (hero + cards + features)
- `routes/web/user.php` — `GET /` → `HomePage::class` name `home`
- `resources/views/ui/layouts/base.blade.php` — dual signals + `Theme::cssVariables()` injection
- `resources/views/ui/layouts/guest.blade.php` — public shell for homepage/auth
- `resources/views/ui/components/theme-switch.blade.php` — theme switcher (light/dark/system)
- `resources/js/app.js` — `applyTheme()` dual-signal sync + cookie/localStorage
- `app/Modules/Settings/Domain/Theme/Support/Theme.php` — `cssVariables()`, `all()`, `base()` (cached 1h)
- `app/Modules/Core/Support/Color.php` — `computeBaseShades`, `computeDarkShades`, `lighten`, `contrastColor`
- `resources/css/app.css` — `@theme` semantic tokens + `[data-theme='dark']` overrides + `@layer components` shims
- `lang/en/user.php` + `lang/id/user.php` — `user.home.*` keys
- **Related specs:** [branding-theme-locale.md](52O1I-branding-theme-locale.md) — palette pipeline; [layout-and-ui-system.md](8XMYS-layout-and-ui-system.md) — shells; [registration.md](MBB5R-registration.md) — availability action
