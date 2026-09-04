# Public Landing Page

> **Spec ID:** K8HP1

## Description

Public landing page at `GET /` (`App\Modules\User\Livewire\HomePage`). The single entry point for unauthenticated visitors on an installed instance — shows hero branding, registration availability (open/upcoming/closed/not_configured), login CTA, and feature highlights (logbook, supervision, certificate). Handles setup-not-installed → `setup` and authenticated → `dashboard` redirects, renders in the guest shell (`ui::layouts.guest` → `base`), and consumes the existing branding/theme/locale pipeline (52O1I) via semantic tokens among its other requirements.

---

## 1. Problem Statements

### PS-1 — No Public Entry Spec

`GET /` (`HomePage` Livewire + `home-page.blade.php`) shipped without a spec. Without FR/NFR/UC IDs, the page is orphan in audits, untestable traceably, and future edits lack an SSOT for branching (setup vs auth vs guest), copy, and layout.

### PS-2 — Visitors Need Clear Next Step

Unauthenticated visitors arrive with two distinct intents — register for PKL or sign in. Without a dedicated landing, they must guess URLs (`/apply`, `/login`). Registration is also time-boxed (`registration_period_start`/`_end`); the landing must communicate `open`/`upcoming`/`closed`/`not_configured` with correct CTA or explanatory alert — not a static link.

### PS-3 — Setup and Auth Must Not Leak the Landing

Fresh-install instances must force `/setup`; authenticated users must not see the public marketing view. If `GET /` renders for these states, it leaks the wrong chrome (guest header instead of wizard or dashboard) and breaks install flow.

### PS-4 — Public Page Must Feel Like the Product

The landing shares the same brand (logo, `brand('tagline')` fallback), locale switcher (EN/ID), theme switcher (light/dark/system via `52O1I` dual signals), responsive breakpoints, WCAG AA chrome, and visual language (gradient hero, card hover, blur blobs) as the rest of the app. A one-off static page would diverge in styling, i18n, and dark-mode behavior.

---

## 2. Goals & Non-Goals

### Goals

| ID | Goal |
|----|------|
| G1 | Provide a single `GET /` landing that guides unauthenticated visitors to the correct next step (register vs sign in) based on live registration window |
| G2 | Branch `GET /` correctly for setup-not-installed and authenticated states via Livewire mount redirects |
| G3 | Present hero (brand, tagline, pills, description, wave), two primary cards (registration + login), and three feature highlights with responsive and interactive polish that matches the app |
| G4 | Support EN/ID via `__()` and `Carbon::translatedFormat('j F Y')` for the period |
| G5 | Consume the existing semantic theme pipeline (52O1I) — gradient, blobs, cards, wave, borders — so brand preset and dark-mode changes reflect without rebuild (one of several requirements, not the sole focus) |
| G6 | Meet WCAG 2.1 AA for a public page (heading order, focus, `aria-hidden` décor, keyboard reachability) |

### Non-Goals

| ID | Non-Goal |
|----|----------|
| NG1 | CMS-editable homepage — copy is in `lang/{en,id}/user.php` (`user.home.*`), tagline is `brand('tagline') ?: __('common.app_tagline')` |
| NG2 | Redefining the global theming contract — owned by [branding-theme-locale.md](52O1I-branding-theme-locale.md); homepage only consumes it |
| NG3 | New palette computation or theme switcher — reuses `Theme::cssVariables()`, `Color`, `base.blade.php` injection, `app.js applyTheme()` from 52O1I |
| NG4 | SEO/OpenGraph beyond `base/head` (`<title>`, meta, `app()->getLocale()`) |
| NG5 | Visual regression harness — verified via `npm run build` + manual toggle |

---

## 3. User Stories / Use Cases

### UC-1 — Visitor Opens Homepage (Unauthenticated, Installed)

**Actor:** Unauthenticated visitor on installed instance
**Preconditions:** `SetupEntity::get()->isInstalled() === true`, `auth()->check() === false`, `GET /`
**Flow:**
1. `HomePage::mount(ReadRegistrationAvailabilityAction)` runs — no redirect, sets `$registration = $action->execute()`
2. `render()` returns `view('livewire.user.home-page')->layout('ui::layouts.guest', ['title' => __('user.home.page_title')])`
3. Guest shell renders: sticky header (brand `wire:navigate /` + `theme-switch` + `lang-switcher`), `main#main-content`, footer `credits`
4. Hero renders: brand `size=xl`, 3 pills, tagline `brand('tagline') ?: __('common.app_tagline')` as gradient `h1`, `hero_desc`, wave divider
5. Cards section (`bg-base-200`) shows registration card (branch per UC-4) + login card + 3 feature highlights
**Postconditions:** Landing visible without auth; internal links use `wire:navigate` (SPA); theme/lang switchers functional

### UC-2 — Authenticated User Hits `/`

**Actor:** Any `auth()->check() === true`
**Preconditions:** User logged in
**Flow:** `HomePage::mount()` calls `$this->redirectRoute('dashboard')` and returns — before availability fetch
**Postconditions:** Lands on `GET /dashboard` (role-routed); homepage Blade never rendered

### UC-3 — Fresh Install Hits `/`

**Actor:** Visitor on `isInstalled() === false`
**Preconditions:** `setup.is_installed` falsy
**Flow:** `HomePage::mount()` calls `$this->redirectRoute('setup')` and returns — takes precedence over auth check
**Postconditions:** Redirected to setup wizard; no availability query

### UC-4 — Registration Card Branches on `status`

**Actor:** Visitor on homepage (UC-1)
**Flow:** `home-page.blade.php` switches on `$registration['status']`:
- `open`: `x-ts-badge` success + `registration_open` + period `j F Y` (`start`→`end`) + info alert not shown + `x-ts-button` `wire:navigate href=route('apply')` primary `register_now`
- `upcoming`: badge info + `registration_upcoming` + upcoming period + `x-ts-alert` info `registration_not_open_yet`
- `closed`: badge warning + `registration_closed` + `x-ts-alert` warning `registration_closed_desc`
- default (`not_configured`/unknown): neutral badge `registration_unavailable` + alert `registration_unavailable_desc`
**Postconditions:** Single CTA or explanatory alert; translated in EN/ID; dates via `Carbon::parse()->translatedFormat('j F Y')`

### UC-5 — Visitor Explores Feature Highlights

**Actor:** Visitor scrolling past cards
**Flow:** Section `features_title`/`features_subtitle` + grid `1 → 3` cols shows 3 cards: Logbook (`book-open`, `primary`), Guidance (`users`, `secondary`), Certificate (`identification`, `accent`) with `feature_*_title/desc`
**Postconditions:** Marketing overview of platform value without requiring auth

### UC-6 — Visitor Changes Locale or Theme on Homepage

**Actor:** Visitor on homepage
**Flow:** Clicks `livewire:settings.lang-switcher` (EN↔ID, cookie `locale` via `SetLocale`) or `x-ui::components.theme-switch` (light/dark/system → `app.js applyTheme()` sets `data-theme`+`.dark`+cookie)
**Postconditions:** Locale/theme updates in-place; hero/cards/wave recolor via CSS variables; persists on reload

---

## 4. Functional Requirements

### Homepage — Routing & Component

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-HP-01 | `GET /` must be `Route::livewire('/', HomePage::class)->name('home')` in `routes/web/user.php` | P0 |
| FR-HP-02 | `HomePage` must be `final class HomePage extends Component` with `public array $registration = []` | P0 |
| FR-HP-03 | `mount(ReadRegistrationAvailabilityAction $action)` must DI-inject the action; if `!SetupEntity::get()->isInstalled()` call `$this->redirectRoute('setup')` and return (takes precedence over auth) | P0 |
| FR-HP-04 | If `auth()->check()` call `$this->redirectRoute('dashboard')` and return — before `$action->execute()` | P0 |
| FR-HP-05 | Otherwise set `$this->registration = $action->execute()` | P0 |
| FR-HP-06 | `render(): View` must return `view('livewire.user.home-page')->layout('ui::layouts.guest', ['title' => __('user.home.page_title')])` | P0 |

### Homepage — Shell (Guest Layout)

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-HP-07 | Guest shell (`ui::layouts.guest` → `ui::layouts.base`) must be `bg-base-200 flex min-h-screen flex-col` with: sticky header `bg-base-100/80 border-base-content/10 backdrop-blur-sm` containing brand link `wire:navigate href="/"` (`x-ui::components.brand size=sm`) + `x-ui::components.theme-switch` + `livewire:settings.lang-switcher`; `main#main-content.flex.flex-1.flex-col` for `$slot`; footer `border-base-content/10 mt-auto border-t py-8` with `x-ui::components.credits` (fallback) | P0 |
| FR-HP-08 | `ui::layouts.base` must provide `<html lang="{{ app()->getLocale() }}" data-theme>` + `.dark` class, injected `Theme::cssVariables()` `<style>`, `<head>` via `base/head`, and `wire:navigate` focus-reset chrome per 8XMYS | P0 |

### Homepage — Hero

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-HP-09 | Hero `section` must be `from-primary/8 via-base-100 to-secondary/8 bg-gradient-to-br relative overflow-hidden` with decorative blobs `pointer-events-none absolute inset-0` (`aria-hidden="true"`) — `bg-primary/10 size-[36rem] -top-32 -right-32 blur-3xl animate-pulse`, `bg-secondary/10 -bottom-32 -left-32 blur-3xl animate-pulse delay 1.5s`, `bg-accent/5 size-64 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 blur-2xl` | P1 |
| FR-HP-10 | Hero inner must be `container mx-auto px-4 pt-20 pb-16 sm:px-6 sm:pt-24 sm:pb-20 lg:px-12 lg:pt-32 lg:pb-24` + `max-w-3xl text-center`; brand row `mb-8 flex justify-center` with `x-ui::components.brand size=xl withTagline=false` | P1 |
| FR-HP-11 | Pills row `mb-6 flex flex-wrap justify-center gap-2` must render 3 `x-ts-badge`: `primary shield-check hero_secure`, `secondary academic-cap hero_academic`, `success globe-alt hero_global` | P1 |
| FR-HP-12 | Tagline `h1` must be `from-base-content to-base-content/60 bg-gradient-to-br bg-clip-text text-transparent text-3xl font-black tracking-tight sm:text-4xl lg:text-5xl mb-5` with `{{ brand('tagline') ?: __('common.app_tagline') }}`; desc `p` `text-base-content/60 max-w-xl mx-auto sm:text-lg` with `__('user.home.hero_desc')` | P0 |
| FR-HP-13 | Wave divider `div.h-16.sm:h-20[aria-hidden]` must contain `svg.text-base-200.w-full.h-16.sm:h-20[viewBox="0 0 1440 80" preserveAspectRatio="none" fill="none"] > path[d="M0 80C240 80 480 20 720 20C960 20 1200 80 1440 80V0H0V80Z" fill="currentColor"]` | P1 |

### Homepage — Primary Cards

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-HP-14 | Cards `section` must be `bg-base-200 flex-1 pb-16 sm:pb-20 lg:pb-24` with inner `container mx-auto -mt-8 sm:-mt-10 px-4 sm:px-6 lg:px-12`; grid `mx-auto max-w-5xl grid gap-6 sm:gap-8 lg:grid-cols-2 grid-cols-1` | P0 |
| FR-HP-15 | Registration card must be `group card bg-base-100 border-base-content/10 border shadow-lg hover:border-primary/30 hover:-translate-y-1 hover:shadow-xl transition-all duration-300`; `card-body items-center text-center p-6 sm:p-8 lg:p-10`; icon well `from-primary/15 to-primary/5 ring-base-content/10 size-16 sm:size-20 rounded-2xl ring-1 ring-inset group-hover:scale-110` with `x-ts-icon clipboard-document-list text-primary size-8 sm:size-10`; title `card-title text-xl sm:text-2xl font-bold`, desc `text-base-content/60 max-w-sm sm:text-base` | P1 |
| FR-HP-16 | Registration card body must branch exactly as UC-4 (badge + period + alert/CTA per `status`); `open` CTA is `x-ts-button wire:navigate href=route('apply') color=primary text=register_now icon=arrow-right icon-right w-full sm:w-auto` | P0 |
| FR-HP-17 | Login card must be `group card bg-base-100 border-base-content/10 hover:border-secondary/30` with matching `card-body`/`icon well from-secondary/15 to-secondary/5`; title `login_title`, desc `login_desc`, `x-ts-button wire:navigate href=route('login') color=secondary login_action`, footer divider `border-base-content/10 mt-6 border-t pt-5` with `no_account text-base-content/40 text-xs` | P0 |

### Homepage — Feature Highlights

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-HP-18 | Feature section `mx-auto max-w-5xl mt-16 sm:mt-20` with header `text-center mb-8 sm:mb-12`: `h2 features_title text-xl sm:text-2xl lg:text-3xl font-bold text-base-content mb-2` + `p features_subtitle text-base-content/50 max-w-2xl mx-auto sm:text-base`; grid `grid-cols-1 sm:grid-cols-3 gap-5 sm:gap-6`; each card `card bg-base-100 border-base-content/10 border shadow-sm hover:shadow-md transition-all` with `card-body p-6 sm:p-8 text-center` | P1 |
| FR-HP-19 | Three cards: Logbook (`from-primary/10 to-primary/5` well `size-12 rounded-xl` + `book-open text-primary size-6` + `feature_logbook_title/desc text-base-content/55`), Guidance (`from-secondary/10 secondary` + `users` + `feature_guidance_*`), Certificate (`from-accent/10 accent` + `identification` + `feature_certificate_*`) | P1 |

### Homepage — Theming (One Requirement Among Several)

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-HP-20 | Homepage visual tokens must be semantic only via the existing pipeline — hero `from-primary/8 via-base-100 to-secondary/8` + blobs `bg-primary/10 secondary/10 accent/5`, wave `text-base-200`, cards `bg-base-100 border-base-content/10 hover:border-primary/30 secondary/30`, icon wells `from-{primary,secondary,accent}/15→/5` + `ring-base-content/10`, text `text-base-content/40–60`/`text-primary/secondary/accent`, badges/buttons/alerts via TallstackUI `color="primary secondary success info warning"` — reusing `Theme::cssVariables()` inline `<style>` in `ui::layouts.base` (cached 1h, invalidated via `theme_cache_keys`) and `app.js applyTheme()` dual `data-theme`+`.dark` per 52O1I FR-T1/FR-T3; no hardcoded hex/`bg-white`/`bg-gray-*`/`ring-white` or per-page `<style>` | P1 |

### Homepage — i18n & Content

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-HP-21 | All user-facing copy must be `__()` with keys existing in both `lang/en/user.php` and `lang/id/user.php` under `user.home.*` (page_title, hero_desc, hero_secure/academic/global, registration_*, login_*, no_account, features_*, feature_logbook/guidance/certificate_*) — tagline fallback is `brand('tagline') ?: __('common.app_tagline')` | P0 |
| FR-HP-22 | Registration period `p` must be `__('user.home.registration_period'/'registration_upcoming_period', ['start' => Carbon::parse(start_date)->translatedFormat('j F Y'), 'end' => ...])` respecting `app()->getLocale()` | P1 |

---

## 5. Non-Functional Requirements

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-HP-01 | Homepage `GET /` TTFB cache-hit < 200 ms (availability + theme vars cached; no N+1) | 200 ms |
| NFR-HP-02 | Theme toggle repaint on homepage < 100 ms (client `applyTheme`, no roundtrip) | 100 ms |
| NFR-HP-03 | Brand preset change reflects on homepage next load without `cache:clear` (52O1I `theme_cache_keys` invalidation) | 1 request |
| NFR-HP-04* | Homepage is responsive without horizontal scroll at 320 px — hero padding scales `px-4→6→12`, `pt-20→24→32`, cards `grid-cols-1 → lg:grid-cols-2`, features `1 → sm:grid-cols-3`, text `text-3xl→4xl→5xl` | Manual 320 px |
| NFR-HP-05* | Hover/interaction polish — blobs `animate-pulse blur-3xl`, cards `hover:-translate-y-1 hover:shadow-xl duration-300`, icon wells `group-hover:scale-110 duration-500` | Manual |
| NFR-HP-06* | WCAG 2.1 AA on homepage: `h1` tagline gradient uses `from-base-content` for contrast, badges/CTAs pair icon+text (not color alone), `aria-hidden="true"` on blobs/wave, `pointer-events-none` on décor, tab order header→hero→cards→features, all focusable elements have visible focus, `wire:navigate` retains focus-reset | Lighthouse ≥ 95 |
| NFR-HP-07 | All `__('user.home.*')` keys resolve in EN and ID; `translatedFormat('j F Y')` respects locale | — |
| NFR-HP-08 | `npm run build` passes (Tailwind v4 + Vite); `vendor/bin/pint --dirty --test` passes if PHP touched | — |

`*` = non-testable visual/manual — excluded from `scan_spec_tests` gap.

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
    // Setup check → redirectRoute('setup') (precedence)
    // Auth check → redirectRoute('dashboard')
    // Else → $this->registration = $action->execute()
    public function render(): View; // view('livewire.user.home-page')->layout('ui::layouts.guest', ['title' => __('user.home.page_title')])
}
```

### 6.3 ReadRegistrationAvailabilityAction Contract

```php
// App\Modules\Enrollment\Domain\Registration\Actions\ReadRegistrationAvailabilityAction
// final class extends BaseReadAction
final class ReadRegistrationAvailabilityAction extends BaseReadAction
{
    public function execute(): array;
    // Reads setting('registration_period_start'), setting('registration_period_end') (Y-m-d or null)
    // Returns one of:
    // ['status' => 'not_configured']                                  // start or end null
    // ['status' => 'open',       'start_date' => Carbon, 'end_date' => Carbon] // now between start/end
    // ['status' => 'upcoming',   'start_date' => Carbon, 'end_date' => Carbon] // start within now+1 month, not open
    // ['status' => 'closed',     'start_date' => Carbon, 'end_date' => Carbon] // otherwise, start & end set
}
```

### 6.4 Blade View Contract

```php
// resources/views/livewire/user/home-page.blade.php
// Expects public $registration from Component
// Uses: x-ui::components.brand, x-ts-badge, x-ts-button, x-ts-alert, x-ts-icon
//       brand('tagline'), __('user.home.*'), __('common.app_tagline'),
//       Carbon::parse()->translatedFormat('j F Y'), route('apply'), route('login')
//       @if ($registration['status'] === 'open'|'upcoming'|'closed') @else (not_configured)
// Layout: ui::layouts.guest → ui::layouts.base
```

### 6.5 Guest Shell Injected Variables (52O1I, referenced)

```
HTML: <html lang="{{ app()->getLocale() }}" data-theme="{{ cookie('theme','system') }}" class="dark?">
CSS vars: html[data-theme='light'] { --color-primary: Theme::cssVariables()['light'] } + html[data-theme='dark'] { --color-primary: lightened 40% }
         Cached 1h under config('cache-keys.theme_css_variables'), invalidated via config('settings.theme_cache_keys')
JS: resources/js/app.js applyTheme(mode) syncs data-theme + .dark + theme cookie + localStorage dark-theme
```

### 6.6 Translations

```php
// lang/en/user.php + lang/id/user.php 'home' => [
//   page_title, hero_desc, hero_secure/academic/global,
//   registration_title/desc, registration_open/period/register_now,
//   registration_upcoming/upcoming_period/not_open_yet,
//   registration_closed/closed_desc, registration_unavailable/unavailable_desc,
//   login_title/desc/action, no_account,
//   features_title/subtitle, feature_logbook/guidance/certificate_title/desc
// ]
// 2 locales × 20+ keys, all required
```

---

## 7. Design Decisions

### DD-1 — Mount-Time Redirects for Setup & Auth Gating

**Decision:** `HomePage::mount()` performs `SetupEntity::get()->isInstalled() → redirectRoute('setup')` and `auth()->check() → redirectRoute('dashboard')` before availability fetch, instead of route middleware.
**Rationale:** Keeps `GET /` as a single Livewire route owning its guest/unauth vs setup vs authed branching; matches other public pages (`/apply`, `/login`) that gate in component and benefits from Livewire `wire:navigate` redirects without extra middleware ordering.
**Alternatives rejected:** `guest`/`auth` middleware split into two routes for `/` — would duplicate the Blade and complicate `setup` precedence.

### DD-2 — Guest Shell Inheritance

**Decision:** `render()->layout('ui::layouts.guest')` which inherits `ui::layouts.base` (sticky header, `main#main-content`, footer credits, `data-theme`/`.dark`, `Theme::cssVariables()` injection, `wire:navigate` focus-reset).
**Rationale:** Public pages (homepage, `/login`, `/apply`) share identical chrome; homepage intro hero → `bg-base-200` cards → footer `credits` reads as continuous surface because guest shell's `bg-base-200 flex min-h-screen flex-col` owns the page background. Standalone shell would duplicate theme/locale/a11y wiring.
**Alternatives rejected:** Page-local header/footer — diverges from guest pages and requires duplicating theme/locale switchers.

### DD-3 — Availability via `ReadRegistrationAvailabilityAction`

**Decision:** Homepage delegates window logic to `ReadRegistrationAvailabilityAction::execute()` (reads `setting('registration_period_start/_end')`, Carbon `between`/`addMonth` branching) instead of inline Carbon in the component.
**Rationale:** Window rule (`open` if `now∈[start,end]`, `upcoming` if `start∈[now,now+1m]`, `closed` otherwise, `not_configured` if null) is domain logic with 4 states; action is unit-testable, cache-friendly, and reused by the registration module.
**Alternatives rejected:** Inline `Carbon::parse(setting(...))` in `HomePage::mount` — mixes domain rule with presentation and hinders reuse.

### DD-4 — Hero with Gradient Tagline + Blobs + Wave (No Image)

**Decision:** Hero uses `from-primary/8 via-base-100 to-secondary/8` gradient background, three `blur-3xl`/`blur-2xl` blobs with `animate-pulse`, `h1` `from-base-content to-base-content/60 bg-clip-text`, and `text-base-200` SVG wave `M0 80C240...` (`preserveAspectRatio="none"`), rather than a hero image.
**Rationale:** Reacts to brand `primary`/`secondary` and dark mode via CSS variables without asset swaps; lightweight (no image load), responsive via `container`/`px-4→12`/`pt-20→32`, and wave creates a seamless `via-base-100` → `bg-base-200` cards transition.
**Alternatives rejected:** Static hero image — would not recolor with brand preset, adds LCP weight, and requires dark-mode variant.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Homepage spec traces 1:1 to code | 100% | FR-HP-* ↔ `HomePage.php` + `home-page.blade.php` + `user.php` + `guest.blade.php` + `ReadRegistrationAvailabilityAction` |
| Unauthenticated visitor sees hero + 2 cards + 3 features; no auth/setup leak | 100% | Manual `GET /` unauth / authed / fresh-install |
| Registration status branching correct (open/upcoming/closed/not_configured) with correct CTA/alert/period | 4/4 states | Manual + any existing `ReadRegistrationAvailabilityAction` coverage |
| Responsive no horizontal scroll at 320 px; hover/blur/pulse polish matches design | Pass | Manual 320/768/1024 px + hover |
| Theme preset reflects on homepage next load (primary/secondary recolor) ≤ 1 request | ≤ 1 | Change `primary_color` via Branding form → reload `/` |
| i18n keys exist in EN & ID; `j F Y` respects locale | 20+ keys | `grep user.home` in both `lang/` |
| `npm run build` + `pint --dirty --test` | Pass | CI |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|------------------|
| [branding-theme-locale.md](52O1I-branding-theme-locale.md) | `Brand::('logo','name','tagline')`, `Theme::cssVariables()` (1h cache, `theme_cache_keys`), `theme-switch` + `applyTheme()` dual signals — **SSOT for theming; homepage only consumes it (FR-HP-20)** |
| [layout-and-ui-system.md](8XMYS-layout-and-ui-system.md) | `ui::layouts.base/guest/app`, `ui::components.brand/credits/theme-switch`, `container`/`wire:navigate`/a11y chrome |
| [installation.md](8NZAU-installation.md) + [setup-wizard.md](VEJCX-setup-wizard.md) | `SetupEntity::isInstalled()` + `setup` route |
| [registration.md](MBB5R-registration.md) | `ReadRegistrationAvailabilityAction` + `setting('registration_period_*')` keys |
| [settings-infrastructure.md](YB22J-settings-infrastructure.md) | `Settings::get/forget()` + `theme_cache_keys` invalidation |

### Build Guide

1. Register K8HP1 in `docs/specs/index.md` Phase 3.
2. Implement `HomePage` per §6.2; `ReadRegistrationAvailabilityAction` already exists — verify 4-state return.
3. Implement `home-page.blade.php` per FR-HP-09..19; wire `guest.blade.php` header/footer; add `lang/*/user.php home.*` keys.
4. Verify `npm run build` + `vendor/bin/pint --dirty --test`; manual checks on `/` unauth / authed / fresh-install, each registration status, `320px` responsive, light/dark/system + preset recolor.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [authentication.md](YB7RG-authentication.md) | `route('login')` CTA from FR-HP-17; `auth::layouts.auth` also inherits `base` but separate flow |
| 2 | [registration.md](MBB5R-registration.md) | `route('apply')` CTA from FR-HP-16 opens the enrollment flow |
| 3 | [dashboard.md](CKKZC-dashboard.md) | `HomePage::mount` authed → `dashboard` role router |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
|----|-----------------------------------|--------|-------|----------|
| A-1 | Global theming (tokens, scrim `bg-black/40`, grep invariant) remains owned by 52O1I; K8HP1 only consumes — no token SSOT duplication. | Accepted | Maintainer | — |
| A-2 | We assume `setting('registration_period_start/_end')` stored as `Y-m-d` strings parsable by `Carbon::parse` (as used in blade). If format changes, homepage period display drifts. | Accepted | Maintainer | — |
| R-1 | If `brand('tagline')` is empty, homepage falls back to `__('common.app_tagline')`. Until a tagline CMS exists, fallback may repeat the common tagline verbatim — acceptable (NG1). | Accepted | Maintainer | — |

## Quick References

- `app/Modules/User/Livewire/HomePage.php` — homepage Livewire (38 lines; mount redirects + availability)
- `resources/views/livewire/user/home-page.blade.php` — homepage Blade (218 lines; hero + wave + 2 cards + 3 features)
- `routes/web/user.php` — `GET /` → `HomePage::class` name `home`
- `resources/views/ui/layouts/guest.blade.php` — guest shell (header brand + theme/lang, `main#main-content`, footer credits)
- `resources/views/ui/components/brand.blade.php` — brand component (`size xl` on hero, `size sm` in header)
- `resources/views/ui/components/credits.blade.php` — footer credits
- `lang/en/user.php` + `lang/id/user.php` `user.home.*` — 20+ keys (page_title, hero_*, registration_*, login_*, features_*)
- `app/Modules/Enrollment/Domain/Registration/Actions/ReadRegistrationAvailabilityAction.php` — 4-state window (50 lines)
- `app/Modules/Setup/Entities/SetupEntity.php` — `isInstalled()` gate
- `resources/views/ui/layouts/base.blade.php` + `resources/js/app.js` + `app/Modules/Settings/Domain/Theme/Support/Theme.php` + `resources/css/app.css` — theming pipeline per 52O1I (referenced, not redefined)
- **Related specs:** [branding-theme-locale.md](52O1I-branding-theme-locale.md) — theming SSOT (consumed via FR-HP-20); [layout-and-ui-system.md](8XMYS-layout-and-ui-system.md) — shell SSOT; [registration.md](MBB5R-registration.md) — availability action; [settings-infrastructure.md](YB22J-settings-infrastructure.md) — settings cache
