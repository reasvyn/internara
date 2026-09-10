# Public Landing Page

> **Spec ID:** K8HP1
> **Status:** Full
> **Owner:** User
> **Depends on:** 52O1I, 8XMYS, MBB5R

## Description

Public landing page at `GET /` serving unauthenticated visitors on an installed instance: hero
branding, live registration availability, login entry, and feature highlights. Setup and
authenticated states redirect before render, the page inherits the guest shell, and it consumes
the existing branding, theme, and locale pipeline without redefining any of it.

---

## 1. Problem Statements

### PS-1 — No Public Entry Spec

`GET /` shipped without specifying its branching, copy, or layout, leaving the page an orphan
in audits with no requirement IDs for tests to trace. Future edits lacked a source of truth
for setup-versus-auth branching, marketing copy, and shell inheritance.
**→ Requirement:** FR-LAND-001–006 (routing and component), UC-LAND-001–003 (branching journeys).

### PS-2 — Visitors Need a Clear Next Step

Unauthenticated visitors arrive with two intents — register for the program or sign in — and
registration is time-boxed, so the landing must communicate open, upcoming, closed, or
unconfigured states with the correct call to action or explanatory message rather than a
static link.
**→ Requirement:** FR-LAND-014–017 (registration and login cards), UC-LAND-004 (status branching).

### PS-3 — Setup and Auth Must Not Leak the Landing

Fresh-install instances must force the setup flow and authenticated users must reach their
dashboard; rendering marketing chrome for either state breaks installation and confuses signed-in
users.
**→ Requirement:** FR-LAND-003/004 (mount-time redirects), UC-LAND-002/003 (redirect journeys).

### PS-4 — Public Page Must Feel Like the Product

The landing shares the brand, locale switcher, theme pipeline, responsive breakpoints,
accessibility chrome, and visual language of the rest of the app. A one-off static page would
diverge in styling, translations, and dark-mode behavior within a release.
**→ Requirement:** FR-LAND-007/008 (guest shell), FR-LAND-020 (semantic tokens only),
FR-LAND-021/022 (localized copy and dates).

---

## 2. Goals & Non-Goals

### Goals

- **One landing guiding visitors to the next step** — registration or sign-in based on the live window. *Why:* unauthenticated arrivals should never guess URLs.
- **Correct branching for setup and authenticated states** — mount-time redirects before any other work. *Why:* install flow and signed-in users must never see marketing chrome.
- **Hero, primary cards, and feature highlights** — brand-led hero with status-aware cards and marketing overview. *Why:* the page both converts and informs without requiring auth.
- **Indonesian and English throughout** — translated copy with locale-aware period dates. *Why:* the landing is the product's first impression in both languages.
- **Consume the shared theme pipeline** — semantic tokens only, so brand and dark-mode changes reflect without rebuild. *Why:* the landing recolors with the product instead of forking it.
- **WCAG 2.1 AA for a public page** — heading order, focus behavior, decorated regions hidden from assistive technology, keyboard reachability. *Why:* the most-visited page sets the accessibility bar.

### Non-Goals

- **CMS-editable homepage**. *Why:* copy lives in language files with the tagline from brand settings; no content-management surface at MVP.
- **Redefining the global theming contract**. *Why:* owned by [branding-theme-locale](52O1I-branding-theme-locale.md); the homepage only consumes it.
- **New palette computation or theme switcher**. *Why:* reuses the existing variables, injection, and client-side applier from the theming spec.
- **SEO and OpenGraph beyond the base head block**. *Why:* title, meta, and locale come from the shared head; deeper SEO is post-MVP.
- **Visual regression harness**. *Why:* post-MVP depth per the [mvp-spec-trim ADR](../adr/adr-mvp-spec-trim.md); build checks plus manual review suffice.

---

## 3. User Stories / Use Cases

Use Cases are **optional** to test, like Design Decisions (§7). `Layer` / `Status` are filled here
because each journey below has a code-verifiable consequence at this spec's scope.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-LAND-001 | Unauthenticated visitor on an installed instance sees the landing with live registration state | P0 | F | Full |
| UC-LAND-002 | Authenticated user hitting `/` is redirected to the dashboard without rendering the landing | P0 | F | Full |
| UC-LAND-003 | Visitor on a fresh install hitting `/` is redirected to setup without querying availability | P0 | F | Full |
| UC-LAND-004 | Registration card branches across open, upcoming, closed, and unconfigured states | P0 | F | Full |
| UC-LAND-005 | Visitor surveys feature highlights without needing authentication | P2 | F | Full |
| UC-LAND-006 | Visitor switches locale or theme in place with persisted, reactive updates | P1 | B | Full |

### 3.1 Entry and Branching

#### UC-LAND-001 — Visitor Opens the Homepage

A parent hearing about the program types the school's URL into a phone browser and lands on a
page that already knows whether registration is open. The mount ran no redirects, fetched the
availability shape, and rendered inside the guest shell — sticky header with brand and
switchers, hero with tagline and pills, status-aware cards, feature highlights — with every
internal link gliding through SPA navigation. No login wall, no guessing, just the next step.

#### UC-LAND-002 — Authenticated User Hits the Root

A student with a bookmarked root URL opens it mid-session and never sees marketing content:
the mount notices the session before touching availability data and redirects to the
role-routed dashboard. Skipping the availability fetch is deliberate — signed-in users have no
use for registration marketing, and the redirect keeps their navigation history clean.

#### UC-LAND-003 — Fresh Install Hits the Root

On a just-deployed instance the very first visit must become the setup wizard, not a landing
page advertising a school that is not configured yet. The installation check runs first,
takes precedence over the auth check, and redirects without issuing the availability query
that would read settings which do not exist.

### 3.2 Content Journeys

#### UC-LAND-004 — Registration Card Branches on Status

The registration card is four cards wearing one layout: open shows a success badge, the
formatted period, and a register call to action; upcoming shows an informational badge with
the future window and a waiting notice; closed shows a warning badge with the closure
explanation; unconfigured shows a neutral badge with the unavailable notice. Each state
carries exactly one honest message — a call to action or an explanation, never a dead button.

#### UC-LAND-005 — Visitor Explores Feature Highlights

Scrolling past the cards, a curious teacher finds three compact stories — daily logbooks,
guidance tooling, certificates — each with an icon, a title, and a line of description in a
responsive grid. The section asks nothing of her: no account, no redirect, just enough
understanding to decide whether the login card above is worth her time.

#### UC-LAND-006 — Visitor Changes Locale or Theme

Tapping the language switcher re-renders every string in place, including the period dates
which reformat to the new locale's conventions; toggling the theme repaints hero, cards, and
wave through CSS variables without a reload. Both choices persist, so a parent who prefers
Indonesian and dark mode finds the whole visit — and the next one — already arranged.

---

## 4. Functional Requirements

A Functional Requirement is a verifiable behavior the system must support. `Priority` ranks
criticality on a P0–P3 scale. `Layer` declares the test layer (`U` Unit · `F` Feature ·
`B` Browser · `A` Arch). `Status` tracks implementation of the requirement itself.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-LAND-001 | `GET /` is a Livewire route named `home` in the user routes file | P0 | A | Full |
| FR-LAND-002 | The homepage component is a final Livewire class holding the availability shape as a public array | P0 | A | Full |
| FR-LAND-003 | Mount redirects to setup when the instance is not installed, taking precedence over the auth check | P0 | F | Full |
| FR-LAND-004 | Mount redirects to the dashboard for authenticated users before fetching availability | P0 | F | Full |
| FR-LAND-005 | Mount otherwise stores the executed availability result for the view | P0 | F | Full |
| FR-LAND-006 | Render returns the homepage view inside the guest layout with the translated page title | P0 | F | Full |
| FR-LAND-007 | The guest shell provides the sticky branded header with navigation brand link and switchers, the main content region, and the credits footer | P0 | F | Full |
| FR-LAND-008 | The base shell provides locale attribute, theme signals, injected theme variables, head block, and SPA focus chrome | P0 | F | Full |
| FR-LAND-009 | The hero section renders the branded gradient backdrop with hidden decorative blobs | P1 | F | Full |
| FR-LAND-010 | The hero inner container centers the brand mark with responsive spacing | P1 | F | Full |
| FR-LAND-011 | The pills row renders three status badges with icons and translated labels | P1 | F | Full |
| FR-LAND-012 | The tagline renders as the page heading with gradient text and fallback copy; the description follows as supporting paragraph | P0 | F | Full |
| FR-LAND-013 | The wave divider renders the section-transition SVG marked hidden from assistive technology | P1 | F | Full |
| FR-LAND-014 | The cards section lays out registration and login cards in a responsive grid | P0 | F | Full |
| FR-LAND-015 | The registration card renders the elevated interactive card with icon well, title, and description | P1 | F | Full |
| FR-LAND-016 | The registration card body branches exactly across the four availability states with matching badge, period, notice, or call to action | P0 | F | Full |
| FR-LAND-017 | The login card renders the secondary interactive card with title, description, sign-in action, and account footer | P0 | F | Full |
| FR-LAND-018 | The feature section renders the header and responsive three-column card grid | P1 | F | Full |
| FR-LAND-019 | The three feature cards cover logbook, guidance, and certificate stories with distinct accents | P1 | F | Full |
| FR-LAND-020 | All homepage visual tokens are semantic through the existing theme pipeline; hardcoded colors and per-page style blocks are forbidden | P1 | A | Full |
| FR-LAND-021 | All user-facing copy resolves through translation keys present in both locale files with brand-tagline fallback | P0 | A | Full |
| FR-LAND-022 | Registration period dates render locale-aware through translated formatting | P1 | F | Full |

### 4.1 Routing and Component

#### FR-LAND-001 — Root route registration

The root URL maps to the homepage Livewire component under its stable route name, so every
redirect target, menu entry, and test in the product points at one canonical home. Renaming
or duplicating this route would silently fork the product's front door.

#### FR-LAND-002 — Component shape

A final component class with a single public array for availability keeps the view contract
obvious: whatever the availability Action returns is what the Blade branches on. There is no
second state channel to drift out of sync, and reviewers can see the entire data surface in
one property declaration.

#### FR-LAND-003 — Setup redirect takes precedence

When the instance is not installed, the mount redirects to setup before anything else —
before the auth check, before the availability query. Ordering matters here because the
availability query reads settings that do not exist yet, and the auth check answers a
question nobody asked on a fresh install.

#### FR-LAND-004 — Auth redirect skips the fetch

Signed-in visitors hitting the root go straight to their dashboard without the component
ever asking about registration windows. Beyond saving a query, the ordering guarantees the
marketing view never flashes for authenticated users on slow connections.

#### FR-LAND-005 — Availability stored for guests

For the remaining case — unauthenticated on an installed instance — the mount executes the
availability Action once and stores the result. One execution per visit keeps the view pure
branching logic with no queries hiding in Blade.

#### FR-LAND-006 — Guest layout with translated title

Rendering wraps the homepage view in the guest layout with the translated page title, which
is what puts the correct `<title>` in the tab and the correct chrome around the content.
A homepage rendered outside its layout would lose header, footer, and theme in one stroke.

### 4.2 Guest Shell Consumption

#### FR-LAND-007 — Sticky header, main region, credits footer

The guest shell frames the page with a blurred sticky header — brand link home plus theme and
language switchers — a main content region owning the page background, and a footer carrying
credits. Because the shell owns the background, the hero-to-cards transition reads as one
continuous surface rather than stacked boxes.

#### FR-LAND-008 — Base chrome inherited wholesale

Locale attribute, theme signals, injected brand variables, the shared head block, and SPA
focus management all arrive through the base layout the guest shell extends. The homepage
declares none of this itself, which is precisely why a theming fix lands on the landing page
without anyone touching the landing page.

### 4.3 Hero

#### FR-LAND-009 — Gradient backdrop with hidden décor

The hero opens with a soft brand-tinted gradient and three blurred, gently pulsing blobs that
exist purely for atmosphere. Marking them hidden from assistive technology and inert to
pointers keeps decoration from becoming noise for screen readers or an obstacle for touch.

#### FR-LAND-010 — Centered brand presentation

Responsive inner spacing centers the brand mark above the copy, growing airier as the
viewport widens. The progression from tight phone padding to generous desktop whitespace is
what makes the same hero feel composed at 360 pixels and at 1440.

#### FR-LAND-011 — Trust pills with icons and labels

Three badges — security, academic purpose, global reach — pair an icon with a translated
phrase so each signal survives color-vision differences. They answer the visitor's first
unspoken question, "is this legitimate," before she has read a single paragraph.

#### FR-LAND-012 — Tagline heading with fallback

The brand tagline renders as the page's single top-level heading in gradient text, falling
back to the shared application tagline when no custom tagline is set. One heading keeps the
document outline honest, and the fallback keeps fresh installs from showing an empty hero.

#### FR-LAND-013 — Wave transition to cards

The wave SVG eases the hero into the cards section with a seamless color handoff, drawn from
the surface token so it recolors with the theme automatically. Like the blobs it is hidden
from assistive technology — pure seam, no semantics.

### 4.4 Primary Cards

#### FR-LAND-014 — Two-card responsive grid

Registration and login cards share a grid that stacks on phones and sits side by side on
wider screens, overlapped slightly onto the hero for a layered feel. The grid is the page's
decision point, so its two options always appear together, never one without the other.

#### FR-LAND-015 — Registration card treatment

The registration card carries the primary accent: elevated surface, hover lift, icon well
that scales on hover, title, and description. Its visual weight declares it the page's main
action, which is why the hover choreography exists — affordance you can feel before reading.

#### FR-LAND-016 — Four-state card body

Open shows the live period with a register action; upcoming shows the future window with a
waiting notice; closed shows the closure explanation; unconfigured shows the unavailable
notice. The Blade switches on the exact status value, so adding a fifth state in future
would fail visibly in review rather than rendering a blank card in production.

#### FR-LAND-017 — Login card with account footer

The login card mirrors the registration card in the secondary accent with its own icon well,
title, description, and sign-in action, plus a quiet footer for visitors without accounts.
That footer line closes the loop: lost users find registration from the login card just as
easily as the reverse.

### 4.5 Feature Highlights

#### FR-LAND-018 — Section header with card grid

A centered header introduces the highlights above a grid that stacks on phones and opens to
three columns on larger screens. The section's job is orientation, not conversion, so it sits
below the decision cards where it informs without competing.

#### FR-LAND-019 — Three product stories

Logbook discipline, supervision guidance, and certification each get a card with a distinct
accent, icon, title, and description. Three is a deliberate limit: enough to convey the
lifecycle's shape, few enough that a scrolling visitor actually reads them.

### 4.6 Theming Consumption

#### FR-LAND-020 — Semantic tokens only

Every visual token on the page — hero gradient, blobs, wave, card surfaces, borders, wells,
text tones, badge and button colors — resolves through the shared theme pipeline's semantic
names. The prohibition on hardcoded hex values and per-page style blocks is what lets a
brand-preset change recolor the landing on the next load, and the architecture check enforces
it so no well-meaning one-off ever forks the palette.

### 4.7 Localization and Content

#### FR-LAND-021 — Translated copy with tagline fallback

All visible strings resolve through namespaced translation keys mirrored in both locale
files, with the brand tagline preferred and the shared tagline as fallback. A key missing in
either language fails the duality check, which keeps the landing bilingual by construction
rather than by translator heroics.

#### FR-LAND-022 — Locale-aware period dates

Registration windows render through translated date formatting under the active locale, so
the same period reads naturally in Indonesian and in English. Dates formatted in only one
convention would quietly tell half the visitors the wrong month name.

---

## 5. Non-Functional Requirements

Landing constraints with measurable targets. `*` marks rows whose property is visual or
manual and therefore exempt from the spec↔test traceability check.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-LAND-001 | Brand preset change reflects on the homepage on the next load without cache clearing | 1 request | P1 | F | Full |
| NFR-LAND-002* | Homepage renders without horizontal scroll at 320px with scaling spacing and grids | Manual 320px | P1 | B | Full |
| NFR-LAND-003* | Hover and interaction polish present on blobs, cards, and icon wells | Manual | P2 | B | Full |
| NFR-LAND-004* | WCAG 2.1 AA holds on the homepage with contrast-safe heading, paired icon-text signals, hidden décor, ordered tabs, visible focus, and focus reset | Lighthouse ≥ 95 | P1 | B | Full |
| NFR-LAND-005 | All homepage translation keys resolve in both locales with locale-aware date formatting | 0 missing | P0 | A | Full |
| NFR-LAND-006 | Asset build passes; PHP style check passes when PHP is touched | Pass | P1 | A | Full |

### 5.1 Freshness and Build

#### NFR-LAND-001 — Preset changes visible next load

An admin who picks a new brand preset and reloads the landing sees the new palette
immediately, because theme invalidation flows through the settings pipeline rather than
requiring a cache clear. The single-request target is what makes branding feel live instead
of batched.

#### NFR-LAND-006 — Build and style gates

The asset build must pass for any markup or style change, and the PHP style check must pass
whenever component code is touched. These gates catch the Tailwind class typo and the
formatting slip long before they reach a visitor's browser.

### 5.2 Visual and Access Properties

#### NFR-LAND-002* — Small-screen integrity

At the narrowest supported width the page must show no horizontal scroll, with padding,
grids, and type scales stepping down gracefully. Marked non-testable because only eyes and
fingers confirm it, verified by manual passes at phone, tablet, and desktop widths.

#### NFR-LAND-003* — Interaction polish

Pulsing blobs, lifting cards, and scaling icon wells give the page its tactile quality. Also
marked non-testable — no assertion captures "feels alive" — and likewise confirmed by manual
review rather than the traceability scanner.

#### NFR-LAND-004* — Public-page accessibility bar

Contrast-safe heading tones, icon-plus-text badges, hidden décor, logical tab order, visible
focus, and SPA focus reset together hold the AA bar with a high lighthouse target. Manual and
tool-assisted rather than unit-tested, hence the marker, but reviewed on the same cadence as
any functional gate.

#### NFR-LAND-005 — Bilingual completeness

Every homepage key resolves in both locales and period dates obey the active locale's
formatting. Unlike its visual neighbors this row is fully checkable — key presence scans and
locale-switch tests — so it carries no marker.

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
final class HomePage extends Component
{
    public array $registration = [];

    public function mount(ReadRegistrationAvailabilityAction $action): void;
    // Setup check → redirectRoute('setup') (precedence)
    // Auth check → redirectRoute('dashboard')
    // Else → $this->registration = $action->execute()
    public function render(): View;
}
```

### 6.3 ReadRegistrationAvailabilityAction Contract

```php
final class ReadRegistrationAvailabilityAction extends BaseReadAction
{
    public function execute(): array;
    // ['status' => 'not_configured']
    // ['status' => 'open',     'start_date' => Carbon, 'end_date' => Carbon]
    // ['status' => 'upcoming', 'start_date' => Carbon, 'end_date' => Carbon]
    // ['status' => 'closed',   'start_date' => Carbon, 'end_date' => Carbon]
}
```

### 6.4 Blade View Contract

```
resources/views/livewire/user/home-page.blade.php
Expects public $registration from the component.
Uses brand, badge, button, alert, and icon components with
brand('tagline'), namespaced translations, locale-aware dates,
and the apply/login routes. Branches on $registration['status'].
Layout: guest shell inheriting the base shell.
```

### 6.5 Guest Shell Injected Variables (Referenced)

```
HTML lang from the active locale; theme signals from cookie plus client applier.
CSS variables injected from the theme pipeline with hourly caching and
settings-driven invalidation. Client script syncs theme attribute, dark class,
cookie, and stored preference.
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
```

---

## 7. Design Decisions

Design Decisions are **optional** to test, like Use Cases (§3). `Layer` / `Status` stay `—` unless
a decision has a code-testable consequence.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-LAND-001 | Mount-time redirects own setup and auth gating instead of route middleware | P0 | — | — |
| DD-LAND-002 | Rendering inherits the guest shell over the base shell instead of page-local chrome | P0 | — | — |
| DD-LAND-003 | Window logic delegates to the availability Read Action instead of inline date math | P0 | — | — |
| DD-LAND-004 | Hero uses gradient, blobs, and wave with no image assets | P1 | — | — |

### 7.1 Gating and Shell

#### DD-LAND-001 — Redirects in mount over middleware split

Owning all three branches in the component keeps one route, one Blade, and one precedence
story, and it rides SPA redirects naturally. Splitting guest and authenticated roots across
middleware would duplicate the view and tangle setup precedence into middleware ordering —
complexity paid on every future edit for no runtime gain.

#### DD-LAND-002 — Inherited guest chrome over local header

Sharing the guest shell with sibling public pages means theme, locale, and accessibility
fixes land everywhere at once. A page-local header would start identical and end divergent;
inheritance removes the second outcome from the possibility space.

### 7.2 Content and Presentation

#### DD-LAND-003 — Availability Action over inline dates

The four-state window rule is domain logic with boundary subtleties — inclusive ranges,
month-ahead upcoming detection, null handling — that deserves isolated tests and reuse by the
registration module. Inline date math in the component would bury that rule where neither
tests nor siblings could reach it.

#### DD-LAND-004 — Code-drawn hero over image assets

Gradients, blurred blobs, and the wave SVG recolor with brand presets and dark mode for
free, weigh nothing on first paint, and scale to any viewport. A photographic hero would
need per-theme variants, add load weight, and fight the palette instead of wearing it.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|----------------|
| Registration status branching correct with matching CTA, notice, and period | 4/4 states | Manual pass plus availability Action coverage |
| Responsive integrity with matching interactive polish | Pass | Manual 320/768/1024px plus hover review |
| Theme preset visible on next load | ≤ 1 request | Change preset via branding form, reload `/` |
| Translation keys present in both locales with locale-aware dates | All keys | Key scan in both language files |
| Build and style gates | Pass | CI |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|------------------|
| [branding-theme-locale](52O1I-branding-theme-locale.md) | Brand fields, theme variables with caching and invalidation, theme and locale switchers — the theming source of truth |
| [layout-and-ui-system](8XMYS-layout-and-ui-system.md) | Base and guest shells, brand and credits components, container and navigation chrome |
| [installation](8NZAU-installation.md) + [setup-wizard](VEJCX-setup-wizard.md) | Installation state and the setup route |
| [registration](MBB5R-registration.md) | Availability Action and the registration period settings keys |
| [settings-infrastructure](YB22J-settings-infrastructure.md) | Settings access with theme-cache invalidation |

### Build Guide

1. Register this spec's route and component per §6.1–§6.2; verify the availability Action's four-state return.
2. Implement the Blade per FR-LAND-009–019 with guest header and footer wiring and namespaced language keys.
3. Verify the asset build and PHP style check; manually exercise unauthenticated, authenticated, and fresh-install visits, all four registration states, small-screen rendering, and theme plus preset changes.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [authentication](YB7RG-authentication.md) | Login call to action target; auth layouts share the same base shell |
| 2 | [registration](MBB5R-registration.md) | Apply call to action target opening the enrollment flow |
| 3 | [dashboard](CKKZC-dashboard.md) | Authenticated redirect destination with role routing |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all feature specs grouped in 12 phases
- [Layout & UI system](8XMYS-layout-and-ui-system.md) — shells and components this page inherits
- [Branding, theme & locale](52O1I-branding-theme-locale.md) — theming source of truth consumed here
- [Registration](MBB5R-registration.md) — availability Action and period settings
- [Dashboard](CKKZC-dashboard.md) — authenticated redirect destination
- [MVP trim ADR](../adr/adr-mvp-spec-trim.md) — why visual harnesses and SEO depth stay out
