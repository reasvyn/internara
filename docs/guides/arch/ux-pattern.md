# UX Pattern — Theme, Accessibility, Localization, User Flow & Experience

## Description

This pattern governs how Internara delivers **theming, accessibility, localization, user flow, and overall user experience**. It synthesizes global industry standards — W3C WAI WCAG 2.2 Level AA + WAI-ARIA Authoring Practices (APG) 1.2, Nielsen Norman Group 10 Usability Heuristics, ISO 9241-110/210 (Human-centred design & dialogue principles), Material Design 3, Apple HIG, ICU/CLDR & W3C Internationalization (i18n) — into enforceable rules mapped to the project's stack: CSS-variable runtime theming (`Theme::cssVariables()`), TallStackUI a11y primitives, `SetLocaleMiddleware`, flat `lang/{en,id}/*.php` with `__()`, `wire:navigate` SPA, and the `*-guide.blade.php` help pattern.

Without it, theming fragments, accessibility is retrofitted, translations drift, flows confuse, and errors frustrate. With it, every journey is theme-coherent, accessible by construction, localizable by default, and humane.

---

## Non-Negotiable

Hard rules. Violations are architecture or accessibility violations.

1. **WCAG 2.2 Level AA is a design constraint, not a backlog item.** Every user-facing surface MUST meet WCAG 2.2 AA across the four principles — Perceivable, Operable, Understandable, Robust. 4.5:1 text contrast, 3:1 large/UI contrast, keyboard operability, visible focus, `lang` attribute, form labels, `aria-live` for dynamic content, semantic landmarks. No audit-after-ship. See §2.

2. **Dual-signal theming — no bare `bg-white` without `dark:`.** Theme is `data-theme="light|dark"` (palette variables) **plus** `.dark` class (Tailwind `dark:` variant). `resources/js/app.js` `applyTheme()` sets both; cookie `theme` mirrors for SSR to avoid FOUC. Every surface MUST have a dark counterpart — no bare `bg-white`/`text-black` without `dark:bg-base-100` or token equivalent. Use `<x-core::ui.theme-switch>` (`<x-ts-theme-switch>`), never hand-roll toggle.

3. **Semantic tokens only for theme.** New colors require entries in both `Theme::cssVariables()` `light` and `dark` scopes and `Color::isValid()` validation. Never hardcode hex in Blade/CSS. Always `bg-primary`, `text-success`, `border-warning`, `bg-info/10` — never `bg-blue-500` or `bg-[#059669]`.

4. **Every user string via `__()` — dual locale mandatory.** All visible text uses `__()` (or `LabelEnum::label()` / `StatusEnum::label()` which calls `__()`). Every key MUST exist in both `lang/en/{file}.php` and `lang/id/{file}.php`. No hardcoded English in Blade, PHP, or JS. `LangChecker` validates completeness. See §3.

5. **Blade & Livewire a11y contracts.** Every `x-ts-input`/`select`/`textarea` MUST have `label` prop (renders `<label for>`); `required` prop for required fields (not just visual asterisk); validation errors stay in TallStackUI `aria-live` regions. Icon-only controls MUST have `aria-label`. Dynamic regions (toasts, tables, validation) wrapped in `aria-live="polite"` or `assertive`. Modals trap focus; focus returns to trigger on close. `wire:key` on every `@foreach` outermost element.

6. **Guide on every non-trivial flow.** Every page with a non-trivial workflow MUST include `resources/views/{module}/components/{page-name}-guide.blade.php` — floating FAB `fixed right-6 bottom-6 z-50 size-12 rounded-full` + `<x-ts-modal separator blur size="lg">` with intro, numbered steps (title + desc), and tip. All strings via `__()`, keyboard-accessible, ARIA-announced. See `livewire-pattern.md` §11.

7. **Error prevention over error recovery.** Destructive actions use two-step `ask{Action}()` → `confirm{Action}()` with shared `core::ui.confirm` (`wire="showConfirm"`), never bare `wire:confirm`. Validate inline (`$this->validate()`) + authoritatively in Action; catch `RejectedException` → `$this->toast()->error($e->getMessage())->send()`. No silent failures, no `RuntimeException` for business rejection (C8).

---

## How to Apply

### 1. Theming — Design Tokens, Semantic Palette, Dark Mode

Source standards: **W3C Design Tokens (DTCG)**, **Material 3 Color Roles & Dynamic Color**, **Apple HIG Dark Mode**, **WCAG 1.4.3/1.4.11 Contrast**.

#### 1.1 Token Architecture

Three layers, single source of truth in `resources/css/app.css` + `Theme::cssVariables()`.

| Layer | Purpose | Where | Example |
|-------|---------|-------|---------|
| Base (primitive) | Raw values | `@theme` primitives | `--color-primary: #059669` |
| Semantic (purpose) | Intent — what components consume | `@theme` semantic + `Theme` runtime | `bg-primary`, `text-primary-content`, `bg-base-100`, `border-warning` |
| Component (variant) | Component API | TallStackUI props | `<x-ts-button color="primary">`, `.badge-success` |

Runtime branding: `Theme::cssVariables()` generates inline `<style>` in `core::layouts.base` scoped to `html[data-theme='light']` and `html[data-theme='dark']`. Changing Settings → next request reflects, no `npm run build`. Four configurable brand colors (`primary`, `secondary`, `accent`, `base` per `docs/guides/branding.md` §2) + `*-content` contrast (`Color::contrastColor()` luminance >0.5 → `#1a1a1a` else `#f0f0f0`), `base-200/300` shades, dark equivalents (light brand → `Color::lighten(40%)` for dark, base → `Color::computeDarkShades()` returns fixed black shades `#262626/#171717/#0a0a0a`). Cache key `theme_css_variables` TTL 3600 (`config/cache-keys.php`).

```css
/* resources/css/app.css — @theme is the SSOT */
@theme {
  --color-base-100: #ffffff;
  --color-primary: #059669; /* overridden at runtime by Theme::cssVariables() */
  --color-info: oklch(60% 0.55 250);
  --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
}
[data-theme='dark'] {
  --color-info: oklch(70% 0.38 250);
}
```

```blade
{{-- Consume via token — never brand() in CSS --}}
<div class="bg-primary text-primary-content border border-base-300 rounded-xl p-4">
  <x-ts-badge color="success" :text="__('common.verified')" />
</div>
{{-- Theme switch — always this component --}}
<x-core::ui.theme-switch />
```

#### 1.2 Dark Mode — Dual-Signal

`resources/js/app.js` `resolveTheme()` / `applyTheme()` sets `data-theme="light|dark"` **and** `.dark` class; syncs `theme` cookie for SSR (avoids FOUC — `base.blade.php` `class="dark"` server-side) and `localStorage 'dark-theme'` persistence; dispatches `theme` CustomEvent. Three modes: `light` / `dark` / `system` (`prefers-color-scheme` media query). Test at all three; toggle OS preference for `system`. Never ship a surface without `dark:` coverage.

Palette contrast pre-validated AA (4.5:1 normal, 3:1 large/UI) per `modular-pattern.md` §22.1 via `Color::relativeLuminance()` audit before merging preset. Avoid saturated colors on dark — dial down saturation (UX Design Institute: bright saturated on dark = jarring, harms readability).

DaisyUI shims in `@layer components` (`.btn`, `.badge`, `.table`, `.alert` with `color-mix(in oklch, var(--color-*) 10%, white)` tints) are legacy bridges. New code uses `x-ts-*` — do not author new DaisyUI usage.

### 2. Accessibility — WCAG 2.2 AA (W3C WAI), WAI-ARIA 1.2, ISO 9241-171

Commitment: **WCAG 2.2 Level AA** per `modular-pattern.md` §22 + `livewire-pattern.md` §13 + `ui-ux.md` §6. Four principles, each with concrete Internara rules. QuickRef: [W3C WCAG 2.2 QuickRef](https://www.w3.org/WAI/WCAG22/quickref/).

#### 2.1 Perceivable (WCAG Principle 1)

- **1.4.3 Contrast (Minimum) & 1.4.11 Non-text Contrast:** 4.5:1 normal text, 3:1 large (≥18pt / 14pt bold) and UI components. Palette pre-validated; never override with arbitrary Tailwind colors. Verify with contrast checker before introducing any new foreground/background pair. Tool: `Color::relativeLuminance()` + manual axe check.
- **1.4.1 Use of Color:** Color is never sole indicator. Status → `badge-success` + text "Verified" + optional icon, never color alone. Error → border + icon + `aria-live` text.
- **1.1.1 Non-text Content:** Every `<img>` has `alt` (decorative → `alt=""`). Icons paired with text must not duplicate `alt`. Heroicons are decorative when adjacent to label.
- **1.3.1 Info and Relationships & 1.3.3 Sensory Characteristics:** Tables use `scope` on headers (`x-ts-table` does this by default — never override). Do not instruct "click the green button" — use label + position.
- **1.4.4 Resize Text & 1.4.12 Text Spacing & 1.4.10 Reflow:** Text zooms to 200% without loss; no fixed `height` clipping. No horizontal scroll at 320px — responsive rule (see `ui-pattern.md` §2). `x-cloak` for Alpine hide avoids flash.
- **1.4.13 Content on Hover or Focus:** Tooltips/popovers dismissible via Esc, hoverable, persistent. TallStackUI handles — do not build custom tooltip without these.

#### 2.2 Operable (WCAG Principle 2)

- **2.1.1 Keyboard & 2.1.2 No Keyboard Trap & 2.4.3 Focus Order:** Every interactive element (button, link, input, modal, dropdown, tabs) reachable and operable via keyboard alone. Tab order follows logical reading order. `WithRecordSelection` / `WithSorting` concerns preserve order.
- **2.4.7 Focus Visible & 2.4.11/2.4.12 Focus Not Obscured & 2.4.13 Focus Appearance:** Every focusable has visible `focus:ring`. Never `outline-none` without replacement. TallStackUI provides `focus:ring` — do not suppress. Focused element never hidden behind sticky header/footer (check `z-50` FAB + modal `z-50` stacking).
- **2.4.1 Bypass Blocks:** Skip link as first focusable element (`<a href="#main-content" class="sr-only focus:not-sr-only">Skip to main content</a>` in `core::layouts.base`). `main` has `id="main-content"` and `tabindex="-1"` on `h1` for programmatic focus after `wire:navigate`.
- **2.5.8 Target Size (Minimum) — WCAG 2.2 new:** ≥24×24px minimum (AA), ≥44×44px preferred (AAA / Apple HIG 44pt). Enforce 44px for primary actions; 24px floor for dense tables (add `p-2` to `btn-square`).
- **Modals & dropdowns:** `x-ts-modal` traps focus, Esc closes, focus returns to trigger (`x-on:close.window`). Dropdown `x-trap` + `x-cloak`. Alpine `x-trap` for filter panels in `record-manager`.

```blade
{{-- Focus management — base layout --}}
<h1 tabindex="-1" class="text-2xl font-black">{{ $title }}</h1>

{{-- Livewire navigation — reset focus --}}
<script>document.addEventListener('livewire:navigated', () => {
  document.querySelector('h1,[autofocus]')?.focus();
});</script>

{{-- Dynamic region — announce to SR --}}
<div aria-live="polite" aria-busy="{{ $isLoading }}">
  <x-ts-table :headers="$headers" :rows="$rows" />
</div>

{{-- Icon-only — always aria-label --}}
<x-ts-button icon="trash" wire:click="askDelete('{{ $id }}')" aria-label="{{ __('common.delete') }}" />
```

#### 2.3 Understandable (WCAG Principle 3)

- **3.1.1 Language of Page & 3.1.2 Language of Parts:** `<html lang="{{ str_replace('_','-',app()->getLocale()) }}">` in `core::layouts.base`. If a passage is in a different language (e.g., Arabic quote), wrap with `lang="ar"`.
- **3.3.1 Error Identification & 3.3.3 Error Suggestion & 3.3.2 Labels or Instructions:** Every input has `<label>` via `x-ts-input label="..."` (never placeholder as label). Required via `required` prop. Errors announced via `aria-live` (TallStackUI built-in). Server validation in `Form` `rules()` + Action authoritative check; client `$this->validate()` for inline feedback only.
- **3.2.1 On Focus & 3.2.2 On Input & 3.2.3 Consistent Navigation & 3.2.4 Consistent Identification & 3.2.6 Consistent Help:** No context change on focus/input without user request. Sidebar `config/menu.php` order stable across pages; same control = same label everywhere (`common.actions.save` reused). Help (guide FAB) in same corner (fixed `right-6 bottom-6`) on every guide-bearing page.
- **3.3.7 Redundant Entry & 3.3.8 Accessible Authentication:** Do not ask for same info twice in a flow; remember prior answers. Auth supports paste for passwords, no cognitive test that blocks AT.

#### 2.4 Robust (WCAG Principle 4)

- **4.1.2 Name, Role, Value & 4.1.3 Status Messages:** Use semantic HTML (`<nav>`, `<main>`, `<header>`, `<footer>`) or ARIA landmarks (`role="navigation"`). Layout provides `<nav>` (sidebar) + `<main>` (content). Toasts use `<x-ts-toast/>` + `<x-ts-dialog/>` with `aria-live="polite"` / `assertive` — wrap `wire:ignore` toast container with `aria-live` if customizing. TallStackUI `layout`, `modal`, `dropdown`, `tabs` already include ARIA + keyboard — prefer over custom HTML.
- Livewire partial updates are invisible to SR — wrap dynamic output in `aria-live` containers (see example above). After validation failure, dispatch focus to first error: `$this->dispatch('focus-error')` + Alpine handler.

### 3. Localization — W3C i18n, ICU/CLDR, Lingoport 9 Best Practices, Better i18n 2026

Stack: flat `lang/{en,id}/*.php` + `lang/{en,id}.json` (short labels) + vendor `lang/vendor/ts-ui/*/messages.php`; `config/app.php` `locale`/`fallback_locale` from `APP_LOCALE`; `config/localization.php`; `app/Modules/Settings/Locale/Http/Middleware/SetLocaleMiddleware.php`; client `livewire:init` `language-changed` → reload; server `Carbon::locale(app()->getLocale())`, `Number::locale(...)`.

#### 3.1 Key Conventions (enforce by `LangChecker`)

| Scope | Pattern | File | Example |
|-------|---------|------|---------|
| Module | `{module}.key` | `lang/{locale}/auth.php` | `__('auth.login.title')` |
| Submodule | `{submodule}.key` (no module prefix) | `lang/{locale}/internship.php` | `__('internship.create_success')` |
| Shared global | `common.key` | `lang/{locale}/common.php` | `__('common.actions.save')` |
| Domain shared | `{domain}.key` | `activity.php`, `notifications.php`, `log.php` | `__('activity.login_success')` (dynamic DB lookup) |
| Framework | `validation.*`, `passwords.*`, `pagination.*` | `lang/{locale}/validation.php` | `__('validation.required')` |
| Guide | `{module}.guide.*` | `lang/{locale}/{module}.php` | `__('internship.guide.step1_title')` |

Params always `:param` — `__('user.welcome', ['name' => $user->name])`. Dynamic keys (`__("activity.{$activity->description}")`) must stay named as DB stores them — never rename without migration.

#### 3.2 Authoring Rules (Lingoport + Better i18n 2026)

1. Start early — externalize at authoring, never hardcode then extract. `__()` is required at point of writing, not retrofitted.
2. Use ICU MessageFormat for plurals/selects where needed: `trans_choice('common.items', $count)` or `__('common.items', ['count' => $n])` with pipe syntax in lang file; prefer explicit `one`/`other` keys for clarity.
3. Never concatenate: `__('common.hello') . ' ' . $name` → `__('common.hello_name', ['name' => $name])` (word order varies: English SVO vs Indonesian, future RTL).
4. Locale-aware formatting: dates via `Carbon::locale(app()->getLocale())->isoFormat('LL')` or `translatedFormat()`, numbers via `Number::format()` / `Number::currency()` with locale, not manual `number_format()`.
5. Encode once, render escaped: lang files return plain strings; Blade `{{ __('key') }}` escapes (never `{!! __() !!}` unless sanitized markdown via purifier with comment).
6. Vendor keys: TallStackUI translations under `lang/vendor/ts-ui/*` — do not duplicate; rely on `__('ts-ui::messages.*')` via component.
7. RTL readiness (future-proof): use logical properties (`ms-`, `me-`, `ps-`, `pe-`, `text-start`, `text-end`) and Tailwind logical utilities over `ml-`/`mr-`/`text-left` where direction matters. Current locales are LTR (`en`, `id`), but logical props prevent later breakage (W3C i18n, Material 3 RTL).

```php
// ✅ Good — placeholder, duality, locale-aware, parameterised
__('internship.guide.step1_title')                     // guide key
__('common.actions.bulk_action_done', ['count' => $n]) // count param
Carbon::parse($date)->locale(app()->getLocale())->isoFormat('D MMMM YYYY')
Number::currency($amount, 'IDR', locale: app()->getLocale())

// ❌ Bad — concatenation, hardcoded, manual format
'Created ' . $count . ' items'
date('d/m/Y', strtotime($date))
number_format($amount, 2)
```

#### 3.3 Process

- Add key to `lang/en/{file}.php` **and** `lang/id/{file}.php` in same commit. CI `LangChecker` fails otherwise.
- Switcher: `settings.livewire.lang-switcher` (`<x-ts-dropdown>` + `wire:click="setLocale('id'/'en')"` + `aria-label`); header also exposes `livewire:settings.lang-switcher`. Never hand-roll locale toggle.
- Switching dispatches `language-changed` → JS reload (see `resources/js/app.js` `livewire:init`).

### 4. User Flow & Information Architecture

Source standards: **NN/g Information Architecture & User Flows**, **Material 3 Navigation**, **Apple HIG Navigation**, **ISO 9241-110 Dialogue Principles** (suitability for task, self-descriptiveness, conformity with user expectations, suitability for learning, controllability, error tolerance, suitability for individualization).

#### 4.1 Navigation & IA

- Sidebar `x-ts-side-bar navigate smart collapsible` groups from `config/menu.php` role-filtered; consistent order per §2.3 (WCAG 3.2.3). Breadcrumb `nav[aria-label="Breadcrumb"]` under `max-w-7xl` header.
- SPA via `wire:navigate` (Livewire) — fast, preserves scroll, resets focus to `h1`. Do not use bare `<a>` for internal nav where `wire:navigate` applies (TallStackUI `navigate` prop).
- Depth ≤3 levels (sidebar group → item → sub-item). Card sorting validated mental model — avoid deep nesting; prefer flat with search/filter.

#### 4.2 Flows — Happy Path, Empty, Error, Loading, Success

Every **state** of a flow is designed, not just the happy path. Inspired by Material 3 states + ISO 9241 error tolerance.

| State | Pattern | Implementation |
|-------|---------|----------------|
| **Entry / empty** | `core::widgets.empty-state` with icon + title + description + primary CTA | `@include('core.widgets.empty-state', ['title' => __('module.empty_title'), 'action' => 'create'])` |
| **Loading** | `wire:loading` + `aria-busy`, skeleton or spinner, disable inputs `wire:loading.attr="disabled"` | Confirm modal uses `wire:loading.attr="disabled"`; table wrapper `aria-busy="{{ $isLoading }}"` |
| **Success** | `$this->toast()->success(__('{module}.{entity}.created'))->send()` (TallStackUI Interactions) | Never `flash()->` / maryUI `$this->success()` (removed) |
| **Error (validation)** | Inline field error via `x-ts-input` `aria-live`; summary focus to first error via `dispatch('focus-error')` | `RejectedException` → `$this->toast()->error($e->getMessage())->send()` |
| **Error (empty selection)** | `toast()->warning(__('common.no_selection'))` | `performBulkAction()` already warns |
| **Confirm** | Two-step `askDelete()` → `confirmDelete(DeleteAction)` + `core::ui.confirm` (`wire="showConfirm"`) | Never `wire:confirm` bare |
| **Help** | `*-guide.blade.php` FAB + modal (see §4.3) | One per non-trivial page |

#### 4.3 Guide Pattern (Help & Documentation — NN/g Heuristic #10)

Canonical: `resources/views/setup/components/setup-guide.blade.php` (now `<x-ts-modal>`). Every non-trivial workflow page MUST follow it.

- **Placement:** `resources/views/{module}/components/{page-name}-guide.blade.php`
- **Trigger:** Floating FAB `fixed right-6 bottom-6 z-50 size-12 rounded-full bg-primary text-primary-content shadow-xl` with `help-circle` icon + `aria-label="{{ __('{module}.guide.open') }}"`.
- **Modal:** `<x-ts-modal wire="showGuide" separator blur size="lg">` with intro sentence, numbered steps (title + desc per step), tip section (`exclamation-triangle` icon).
- **State:** `public bool $showGuide = false` in Livewire; include via `@include('{module}.components.{page-name}-guide')`.
- **Strings:** `'{module}.guide.*'` keys (`guide.title`, `guide.intro`, `guide.step{N}_title`, `guide.step{N}_desc`, `guide.tip_title`, `guide.tip_desc`) — no hardcode.
- **a11y:** Modal traps focus, Esc closes, focus returns to FAB; content announced via ARIA; keyboard operable.

### 5. User Experience — Principles, Heuristics, Perception

#### 5.1 Nielsen Norman Group 10 Usability Heuristics (1994, reviewed 2024) — Applied

| # | Heuristic | Internara Application |
|---|-----------|-----------------------|
| 1 | **Visibility of system status** | `wire:loading` spinners, `aria-busy`, toasts for every mutation, progress `x-ts-progress` for multi-step, `aria-live` for partial updates. Predictable → trust. |
| 2 | **Match with real world** | Speak user's language (PKL, internship, placement, logbook — not internal jargon). Follow real-world order (academic year → internship → enrollment). Icons map to concepts (`o-calendar` for dates). |
| 3 | **User control & freedom** | Undo via `performBulkAction` transaction rollback; cancel closes modal and resets form; `Escape` exits modal/dropdown; breadcrumb + sidebar allow exit from any flow. |
| 4 | **Consistency & standards** | Same component for same purpose everywhere (`x-ts-modal`, `x-ts-table`, `record-manager` scaffold). Follows Material 3 + WCAG conventions users expect. |
| 5 | **Error prevention** | Confirmation dialogs for destructive, inline validation before submit, disable submit `wire:loading.attr="disabled"`, DTO validation + Entity invariants (C1-C8, D4-D6). |
| 6 | **Recognition over recall** | Guide FAB, visible labels (never placeholder-only), status badges with text, search `debounce.300ms` with prior filters visible, breadcrumb shows location. |
| 7 | **Flexibility & efficiency** | `perPageOptions` (10/25/50/100), `search` + `filters` + `sortBy`, bulk/mass actions, keyboard shortcuts (`/` focus search where applicable). |
| 8 | **Aesthetic & minimalist** | Whitespace over ornament; border over heavy shadow; single brand accent; concise copy via `__()`; empty-state CTAs not walls of text. |
| 9 | **Help recognize/diagnose/recover from errors** | Plain language errors via `RejectedException` (already translated), field-level messages, `error` toast with actionable text, not codes. |
| 10 | **Help & documentation** | `*-guide.blade.php` on every non-trivial page; contextual tips; consistent help location (FAB right-6 bottom-6). |

#### 5.2 ISO 9241-110 Dialogue Principles (condensed)

- **Suitability for task** — UI mirrors the real task (teacher proxies supervisor after 48h inactivity — Cross-Role Proxy, not forced re-login).
- **Self-descriptiveness** — Each control's purpose is obvious from label + icon + affordance; no mystery meat.
- **Conformity with expectations** — Navigation, form, and table behaviors match platform conventions (Material/Apple/WCAG).
- **Suitability for learning** — Guides, empty states, and progressive disclosure teach without manual.
- **Controllability** — User initiates and controls pace (explicit confirm, cancel, perPage).
- **Error tolerance** — Forgiving (confirm, undo via transaction, non-destructive default).
- **Suitability for individualization** — Theme (`light/dark/system`) + locale (`en/id`) + perPage + column sort are user choices persisted.

#### 5.3 Cognitive Load & Feedback (NN/g + Material 3 Motion)

- **Reduce load:** Chunk forms (`space-y-4` groups, `grid md:grid-cols-2`), progressive disclosure (Alpine `filtersOpen` with `x-trap`+`x-cloak`, not all filters always visible), 5±2 visible actions (overflow to dropdown).
- **Immediate feedback:** `wire:model.live.debounce.300ms` for search, instant `toast()` after Action, `wire:loading` on submit, `Livewire` `aria-live` for updates. Never silent → every user consequence is communicated (Heuristic #1).
- **Motion:** TallStackUI transitions are subtle; avoid gratuitous animation. If adding, use Tailwind `transition duration-200 ease` — consistent motion token. Respect `prefers-reduced-motion` (future: `@media (prefers-reduced-motion: reduce)` guards).
- **Performance perception:** Skeleton over spinner for tables where possible; `wire:navigate` SPA keeps nav snappy; `npm run build` + `preconnect` + Vite HMR keep delivery fast. Memory: `chunk(200)` / `lazy(200)` for large datasets (see `conventions.md` §6.2).

---

## Anti-Patterns

| You see… | It should be… | Violation |
|----------|---------------|-----------|
| `bg-white text-black` without `dark:` counterpart | `bg-base-100 dark:bg-base-100 text-base-content` or semantic token pair | Dual-signal dark mode, FOUC / unreadable in dark |
| New hex `#123456` in Blade/CSS or `bg-blue-500` decorative | `@theme --color-*` + `Theme::cssVariables()` light+dark + `bg-info` semantic | Token drift, fails contrast gate |
| Hand-rolled theme toggle `onclick="toggleDark()"` | `<x-core::ui.theme-switch />` / `<x-ts-theme-switch>` | Breaks SSR cookie sync, `.dark` + `data-theme` dual-signal |
| `<input placeholder="Name">` without `label` | `<x-ts-input label="{{ __('common.name') }}" required />` | WCAG 3.3.2 Labels, 1.3.1 Info & Relationships |
| `style="outline:none"` without `focus:ring` | Keep TallStackUI `focus:ring` (default) | WCAG 2.4.7 Focus Visible |
| Icon-only `<button><svg>…</svg></button>` | `<x-ts-button icon="trash" aria-label="{{ __('common.delete') }}" />` | WCAG 4.1.2 Name/Role/Value |
| `wire:confirm="Delete?"` bare for destructive | `askDelete()` → `confirmDelete(DeleteAction)` + `core::ui.confirm wire="showConfirm"` + `RejectedException` catch | Error prevention (Heuristic #5, §1 Non-Negotiable #7) |
| Hardcoded `"Record created successfully"` | `$this->toast()->success(__('{module}.{entity}.created'))->send()` | i18n, dual locale missing |
| `__('module.title')` added only to `lang/en/` | Add to `lang/en/` **and** `lang/id/` same commit | `LangChecker` completeness |
| `'Hello ' . $name . ', you have ' . $n . ' items'` | `__('common.hello_items', ['name' => $name, 'count' => $n])` | Concatenation, breaks word order / ICU plural |
| `date('d/m/Y')` / `number_format($n,2)` | `Carbon::locale(app()->getLocale())->isoFormat('LL')` / `Number::currency()` | Locale-unaware formatting |
| `ml-4 text-left` for directional spacing | `ms-4 text-start` (logical properties) | RTL fragility (W3C i18n) |
| `{{ $userContent }}` unescaped `{!! $userContent !!}` | `{{ $userContent }}` (escaped) or `{!! Purifier::clean($markdown) !!}` with safety comment | XSS (conventions §3.1) |
| Page with 12-step workflow and no guide | `resources/views/{module}/components/{page}-guide.blade.php` + FAB + modal | Help & documentation (Heuristic #10, §1 Non-Negotiable #6) |
| Guide with hardcoded English steps | `__('{module}.guide.step1_title')` per step | i18n, guide localization |
| Toast via `flash()->success()` / `$this->success()` | `$this->toast()->success()->send()` / `->error()->send()` (TallStackUI Interactions) | Removed legacy API, breaks `aria-live` |
| Table without `aria-live` wrapper or missing `wire:key` in `@foreach` | `<div aria-live="polite"><x-ts-table /></div>` + `wire:key="row-{{ $id }}"` | WCAG 4.1.3 Status Messages, Livewire reconciliation |
| No empty/loading/error states — only happy path | `empty-state` + `aria-busy` + `toast()->warning/error` per §4.2 table | Error tolerance (ISO 9241), Heuristic #1/#9 |

---

## Quick References

- `ui-pattern.md` — Elegant, modern, responsive UI (visual hierarchy, Tailwind v4 tokens, component design, performance) — companion to this doc
- `livewire-pattern.md` §11 Guide Component, §13 Accessibility, §14 Localization — thin components, `*-guide.blade.php`, WCAG, `__()` in Livewire
- `../conventions.md` §13 Theming (CSS vars, form icons), §14 Frontend & Blade Presentation (Blade no-logic, `@hasrole`), §15 Localization (flat `lang/{locale}/*.php`, dual locale)
- `modular-pattern.md` §22 Accessibility (WCAG 2.1 AA Perceivable/Operable/Understandable/Robust) & §23 Localization — project-wide contracts
- `resources/css/app.css` — `@theme` semantic palette, `@custom-variant dark`, `@layer components` shims, `[data-theme='dark']` overrides
- `resources/views/ui/layouts/base.blade.php` / `app.blade.php` / `sidebar.blade.php` / `header.blade.php` — shell, `Theme::cssVariables()`, skip-link, `aria-live`, `wire:navigate` focus reset
- `resources/views/ui/components/record-manager.blade.php` / `page-header.blade.php` / `confirm.blade.php` / `theme-switch.blade.php` / `widgets/empty-state.blade.php` — canonical organisms/molecules
- `resources/js/app.js` — `resolveTheme()`/`applyTheme()` dual-signal + cookie/LS sync, `livewire:init` `language-changed`
- `app/Modules/Settings/Theme/Support/Theme.php` + `app/Modules/Core/Support/Color.php` — `cssVariables()`, `contrastColor()`, `computeDarkShades()`, `isValid()`
- `app/Modules/Settings/Locale/Http/Middleware/SetLocaleMiddleware.php` + `config/app.php` + `config/localization.php` — locale plumbing
- `lang/{en,id}/*.php` + `lang/{en,id}.json` + `lang/vendor/ts-ui/*/messages.php` — translation SSOT
- [WCAG 2.2 QuickRef](https://www.w3.org/WAI/WCAG22/quickref/) — Perceivable/Operable/Understandable/Robust, 1.4.3 Contrast, 2.5.8 Target Size, 4.1.3 Status Messages
- [WAI-ARIA Authoring Practices 1.2 (APG)](https://www.w3.org/WAI/ARIA/apg/) — modal, dropdown, tabs, focus trap patterns
- [W3C Accessibility Principles](https://www.w3.org/WAI/fundamentals/accessibility-principles/) — POUR overview
- [Nielsen Norman Group — 10 Usability Heuristics](https://www.nngroup.com/articles/ten-usability-heuristics/) — visibility, match, control, consistency, prevention, recognition, flexibility, aesthetic, error recovery, help
- [Material Design 3 — Color Roles](https://m3.material.io/styles/color/roles) & [Design Tokens](https://m3.material.io/foundations/design-tokens/overview) — semantic roles, dynamic color, token system
- [Apple HIG — Layout & Color](https://developer.apple.com/design/human-interface-guidelines/layout) — hierarchy, 44pt targets, Dark Mode
- [W3C i18n & ICU/CLDR](https://www.w3.org/International/) — internationalization, logical properties, locale-aware formatting
- [Tailwind CSS v4.0](https://tailwindcss.com/blog/tailwindcss-v4) — CSS-first `@theme`, `@source`, `@custom-variant`, `color-mix()`, container queries
- [MDN — Responsive Design](https://developer.mozilla.org/en-US/docs/Learn/CSS/CSS_layout/Responsive_Design) — mobile-first, fluid, reflow
