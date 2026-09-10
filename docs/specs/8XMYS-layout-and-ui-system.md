# Layout & UI System — Cross-Cutting Presentation Shell & Component Library

> **Spec ID:** 8XMYS
> **Status:** Partial
> **Owner:** Core
> **Depends on:** SE5Q9, I1BCV, YB22J, 52O1I

## Description

Specification of Internara's cross-cutting layout and UI component system: the authenticated and
guest page shells, the config-driven role-filtered sidebar navigation, the shared `core::ui.*`
component library, CRUD list pages built on `BaseRecordManager`, SPA navigation via `wire:navigate`,
responsive behavior, guide pages for non-trivial workflows, and the accessibility contract for all
page chrome. New interactive elements use TallStackUI components first. Theming (dark/light/system,
CSS variables, brand colors) lives in [branding-theme-locale](52O1I-branding-theme-locale.md);
locale switching and settings storage live in that spec and
[settings-infrastructure](YB22J-settings-infrastructure.md).

---

## 1. Problem Statements

### PS-1 — Page Shell Drift Across Modules

Every authenticated page needs the same skeleton: sidebar, header, breadcrumb, content container,
footer. Without a canonical shell, modules re-implement chrome independently, producing divergent
headers, inconsistent containers, and duplicated skip-link/landmark code.
**→ Requirement:** FR-UI-001/002/003 (canonical shells), FR-UI-030/031 (single-implementation chrome).

### PS-2 — Navigation Sprawl and Role Leakage

Menus must be visible only to the roles that may use them. Hardcoding menu entries inside each
module's Blade view scatters authorization logic, makes menu changes multi-file edits, and risks
role leakage (a route listed but not gated, or gated but still listed). Navigation must be a
single config-driven source with role filtering applied centrally.
**→ Requirement:** FR-UI-006/007/008 (menu from config), FR-UI-034 (role-filtered rendering).

### PS-3 — Repeated CRUD UI Patterns

Record management pages repeat the same structure: title/subtitle, header actions, stat cards,
search + filters, selection bar, table, empty state, and modals. Copy-pasting this scaffold per
module produces inconsistent search/debounce behavior, divergent empty states, and duplicated
localization.
**→ Requirement:** FR-UI-012–019 (shared library), FR-UI-020 (BaseRecordManager tables),
FR-UI-021 (TallStackUI-first).

### PS-4 — Uncoordinated Navigation Model

Full page reloads on every navigation are slow and break focus and scroll state. A consistent SPA
navigation model (`wire:navigate`) with correct focus management is required so that every internal
link behaves identically and accessibly.
**→ Requirement:** FR-UI-026/027/028 (SPA swaps, focus reset, announced toasts).

---

## 2. Goals & Non-Goals

### Goals

- **One canonical authenticated shell** — every module renders inside `core::layouts.app`. *Why:* a single skeleton ends header/container/footer drift across modules.
- **One guest shell** — public pages share `core::layouts.guest`. *Why:* login, landing, and apply flows read as one product.
- **Config-driven navigation** — menu groups render from `config/menu.php` with central role filtering. *Why:* menu changes stay single-file edits with no role leakage.
- **Shared `core::ui.*` component library** — page-header, record-manager, display-field, confirm, navbar-actions, brand, avatar, credit. *Why:* the CRUD scaffold is written once and behaves identically everywhere.
- **CRUD tables on `BaseRecordManager`** — every record list inherits search, filter, sort, and pagination. *Why:* the base-class mandate reaches the presentation layer so the fortieth table behaves like the first.
- **TallStackUI-first interactive elements** — buttons, tables, badges, alerts, and icons come from TallStackUI before any custom markup. *Why:* one component dialect means theming and dark mode propagate without per-page fixes.
- **SPA navigation via `wire:navigate`** — content swaps without full reloads, with focus reset. *Why:* preserves scroll, history, and back-button semantics at Livewire cost.
- **Responsive drawer** — sidebar hidden below 1024px behind a toggle, persistent at and above. *Why:* school computers and phones share one layout that degrades gracefully.
- **WCAG 2.1 AA shell chrome** — skip link, landmarks, aria-live regions, keyboard reachability. *Why:* accessibility baked into the shell covers every page at once.
- **Guide page per non-trivial workflow** — each complex flow ships `guides/{feature}-guide.blade.php`. *Why:* QLHDO NFR-UX-002 requires guided help where the UI alone is not self-evident.

### Non-Goals

- **Theme switching, dark/light/system, CSS variables, brand colors**. *Why:* owned by [branding-theme-locale](52O1I-branding-theme-locale.md); this spec only consumes the pipeline.
- **Locale switching and `lang/` file management**. *Why:* owned by [branding-theme-locale](52O1I-branding-theme-locale.md); chrome strings only reference `__()` keys.
- **Settings key-value store, type system, caching**. *Why:* owned by [settings-infrastructure](YB22J-settings-infrastructure.md).
- **Dashboard widgets and role-based stats**. *Why:* owned by [dashboard](CKKZC-dashboard.md).
- **Notification bell behavior and notification center**. *Why:* owned by [notification-infrastructure](TXR2H-notification-infrastructure.md).
- **Module-specific page content and feature forms**. *Why:* each module owns its content slot; the shell owns only chrome.
- **A full design-token framework or visual regression harness**. *Why:* post-MVP depth per the [mvp-spec-trim ADR](../adr/adr-mvp-spec-trim.md); semantic tokens plus manual checks suffice for MVP.

---

## 3. User Stories / Use Cases

Use Cases are **optional** to test, like Design Decisions (§7). `Layer` / `Status` are filled here
because each journey below has a code-verifiable consequence at this spec's scope.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-UI-001 | Authenticated user navigates the sidebar and lands on the linked page without a full reload | P0 | F | Partial |
| UC-UI-002 | Mobile user opens the sidebar drawer with one tap and closes it with Escape | P1 | B | Partial |
| UC-UI-003 | Developer adds a menu item through config only and it appears for permitted roles | P1 | F | Partial |
| UC-UI-004 | Developer renders a record management page from shared components with consistent search and empty state | P1 | F | Partial |
| UC-UI-005 | Keyboard and screen-reader user traverses any core-shell page with landmarks, skip link, and focus reset | P0 | B | Partial |

### 3.1 Navigation Journeys

#### UC-UI-001 — Authenticated User Navigates the Sidebar

On a busy Monday morning an admin opens the app, scans the sidebar, and clicks the placement
entry. The sidebar had already filtered itself against her roles, the placement row carried the
active highlight because the current route matched, and the click swapped only the content area
while the header and sidebar stayed put. She never noticed the machinery — which is exactly the
point, since a navigation model that draws attention to itself has already failed.

#### UC-UI-002 — Mobile User Opens the Sidebar

When the viewport narrows below the large breakpoint the sidebar disappears behind a hamburger
button in the sticky header, and the whole navigation contract compresses into a single drawer
gesture. Tapping the button slides the sidebar in as an overlay whose backdrop carries an
accessible name and yields to the Escape key, so a teacher on a phone reaches any section in at
most two taps and never gets trapped behind an overlay she cannot dismiss.

#### UC-UI-003 — Module Adds a Menu Item

A developer shipping a new feature branch used to dread the menu: one edit in Blade, one in a
service provider, one more for the icon. Now she appends a single entry — route, icon, and a
`__()` label — to the group's items array in `config/menu.php`, confirms the route resolves, and
moves on. No Blade file changes, no duplicate role checks, and an item pointing at a route that
does not exist yet degrades to a harmless placeholder instead of a 500 page.

### 3.2 Content and Access Journeys

#### UC-UI-004 — Module Renders a Record Management Page

Before the shared scaffold existed, every CRUD page was a snowflake: one debounced search at
300ms, another firing on every keystroke, three different empty states for the same "no data"
condition. The record-manager component ended that era by fixing the page shape once — title and
header actions on top, statistics grid, search with debounce plus per-page selector and filters,
selection bar, the table itself, an empty state, and a modal slot — so a new module composes
slots instead of inventing layout.

#### UC-UI-005 — Keyboard and Screen-Reader User Traverses the Shell

Tab from the top of any core-shell page and the first stop is always the skip-to-content link,
followed by announced landmarks for navigation, main content, header, and footer. After every
SPA transition focus lands on the page heading rather than being stranded on the clicked link,
and every toast or validation message speaks through a live region. If any of these regress, the
browser journey covering this story fails loudly rather than leaving keyboard users stranded.

---

## 4. Functional Requirements

A Functional Requirement is a verifiable behavior the system must support. `Priority` ranks
criticality on a P0–P3 scale. `Layer` declares the test layer (`U` Unit · `F` Feature ·
`B` Browser · `A` Arch). `Status` tracks implementation of the requirement itself.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-UI-001 | `core::layouts.base` is the root HTML shell: `<html lang>` from locale, `data-theme`, head assets, skip link, toast container, scripts stack | P0 | F | Partial |
| FR-UI-002 | `core::layouts.app` composes drawer sidebar, sticky header, breadcrumb when context is given, `max-w-7xl` container, and footer | P0 | F | Partial |
| FR-UI-003 | `core::layouts.guest` renders a centered public shell with brand header, theme and language switchers, content slot, and credits footer | P0 | F | Partial |
| FR-UI-004 | Layouts shared by multiple modules live in `resources/views/ui/layouts/`; single-module layouts live under `resources/views/{module}/layouts/` | P0 | A | Partial |
| FR-UI-005 | Livewire pages select the shell via the `#[Layout]` attribute, never by embedding chrome markup inline | P0 | A | Partial |
| FR-UI-006 | `config/menu.php` defines groups with roles, a `__()` title, and items carrying route, icon, `__()` label, optional role override, and optional disabled flag | P0 | F | Partial |
| FR-UI-007 | The sidebar renders only groups and items whose roles match the authenticated user | P0 | F | Partial |
| FR-UI-008 | The active item is detected via route matching and highlighted beyond color alone | P1 | F | Partial |
| FR-UI-009 | Disabled items render as non-interactive muted spans, never as links | P2 | F | Partial |
| FR-UI-010 | Items pointing at missing routes degrade to `#` without throwing | P1 | F | Partial |
| FR-UI-011 | Menu labels use `__()` keys and icons use TallstackUI `x-ts-icon`; no raw text or inline SVG in menu definitions | P0 | A | Partial |
| FR-UI-012 | `x-core::ui.page-header` renders title, optional description, and an actions slot | P1 | F | Partial |
| FR-UI-013 | `x-core::ui.record-manager` scaffolds the CRUD list page with header actions, stats, debounced search, per-page selector, filters, selection bar, table slot, empty state, and modal slot | P0 | F | Partial |
| FR-UI-014 | `x-core::ui.display-field` renders a labeled read-only value with optional icon | P2 | F | Partial |
| FR-UI-015 | `x-core::ui.confirm` wraps destructive actions in a modal bound to visibility and confirm state | P0 | F | Partial |
| FR-UI-016 | `x-core::ui.navbar-actions` renders theme, language, notification, and user controls, each toggleable by prop | P1 | F | Partial |
| FR-UI-017 | `x-core::ui.brand` and `x-core::ui.logo` render the brand mark with size and invert props | P1 | F | Partial |
| FR-UI-018 | `x-core::ui.avatar` renders a user avatar with configurable size | P2 | F | Partial |
| FR-UI-019 | `x-core::ui.credit` renders footer attribution with version visible in local environments only | P2 | F | Partial |
| FR-UI-020 | CRUD list pages build their tables on `BaseRecordManager` for search, filter, sort, pagination, and bulk actions | P0 | A | Partial |
| FR-UI-021 | New interactive elements use TallStackUI `x-ts-*` components first; custom markup only where no component exists | P0 | A | Partial |
| FR-UI-022 | The sidebar is hidden below the large breakpoint and opened via a drawer toggle | P1 | B | Partial |
| FR-UI-023 | The sidebar is persistently visible at the large breakpoint and above | P1 | B | Partial |
| FR-UI-024 | Theme and language switchers appear in the sidebar on small screens and in the header on larger screens | P2 | B | Partial |
| FR-UI-025 | Content uses a centered constrained container with responsive padding; tables scroll horizontally at small viewports | P1 | B | Partial |
| FR-UI-026 | Internal navigation links use `wire:navigate` for partial swaps without full reloads | P0 | F | Partial |
| FR-UI-027 | After a `wire:navigate` transition focus resets to the page heading or first interactive element | P0 | B | Partial |
| FR-UI-028 | Toast feedback renders through the TallStackUI toast container and is announced to screen readers | P1 | B | Partial |
| FR-UI-029 | Every core layout includes a skip-to-content link as the first focusable element targeting main content | P0 | B | Partial |
| FR-UI-030 | The shell uses semantic landmarks for sidebar, content, top bar, and footer | P0 | B | Partial |
| FR-UI-031 | The drawer overlay exposes an accessible name and closes on Escape | P1 | B | Partial |
| FR-UI-032 | Icon-only buttons carry accessible names and active navigation state never relies on color alone | P1 | B | Partial |
| FR-UI-033 | Dynamic content regions are wrapped in live containers so updates are announced | P1 | B | Partial |
| FR-UI-034 | The sidebar never renders an item the current user's roles forbid, verified by a browser assertion | P0 | B | Partial |

### 4.1 Layout Shell

#### FR-UI-001 — Root HTML shell

Early in the project two modules each shipped their own `<head>` handling and the favicon, locale
attribute, and toast container quietly diverged — one page announced toasts, the next swallowed
them. Collapsing everything into a single root shell meant the locale, theme signal, skip link,
and toast container could only ever be defined once, and every page since has inherited the same
correct chrome without thinking about it.

#### FR-UI-002 — Authenticated shell composition

When an authenticated page renders, the shell assembles the drawer sidebar, the sticky header,
an optional breadcrumb trail, the constrained content container, and the footer in a fixed order
that no module is allowed to rearrange. A placement list and a certificate report therefore share
identical chrome, and a reviewer checking layout consistency has exactly one file to read instead
of forty.

#### FR-UI-003 — Guest shell for public pages

A visitor who has never logged in should still feel the product: brand mark up top, theme and
language controls within reach, the content centered, and the credits footer anchoring the
bottom. The guest shell provides that single public face so the landing, login, and application
pages never drift into three separate visual dialects.

#### FR-UI-004 — Layout placement rule

Shared layouts live in one directory and module-private layouts live with their module — a rule
that sounds bureaucratic until the week two teams each created a "custom" app shell and QA had
to test both. The placement check runs at the architecture layer, so a misplaced layout file
fails the scan instead of surviving until someone notices the fork in production.

#### FR-UI-005 — Shell selection via attribute

The one-line layout attribute on a Livewire component is the entire contract between a page and
its chrome. If a component ever embedded sidebar markup directly, it would silently opt out of
every future shell fix — drawer behavior, landmarks, focus reset — which is why inline chrome is
forbidden and the attribute is scan-enforced.

### 4.2 Config-Driven Navigation

#### FR-UI-006 — Menu groups from config

All navigation truth lives in `config/menu.php`: named groups with role lists, translatable
titles, and items carrying route names, icon keys, labels, and optional overrides. Centralizing
this shape turned menu review into reading one file, and adding a section to the product became a
config edit rather than a multi-file expedition.

#### FR-UI-007 — Central role filtering

Filtering happens once, at render, against the authenticated user's roles — first at group level,
then per item where an override exists. The incident this prevents is the classic leak in the
other direction: a link visible to students that 403s on click, eroding trust with every
embarrassing dead end.

#### FR-UI-008 — Active item beyond color alone

Route matching decides which item is current, and the highlight pairs background tint with font
weight so the state survives color-vision differences and monochrome displays. A navigation that
communicates "you are here" only through hue is guessing about its users' eyes.

#### FR-UI-009 — Disabled items as muted spans

A feature flagged off mid-development still deserves a visible placeholder, but a placeholder
that behaves like a link is a trap. Disabled entries render as inert muted text, signaling "this
exists, not yet" without inviting a click that leads nowhere.

#### FR-UI-010 — Missing routes degrade gracefully

During phased rollouts the menu occasionally references a route whose page ships a sprint later.
Rather than crashing the entire sidebar — and with it every authenticated page — the renderer
falls back to a harmless anchor. The menu stays up, the missing page stays missing, and nobody's
Monday is ruined by someone else's merge order.

#### FR-UI-011 — No raw text or inline SVG in menus

Every label flows through the translation helper and every icon through the shared icon
component, which keeps two scans green at once: the duality check for language coverage and the
component-first rule for icon consistency. The day someone inlines an SVG for "just this one
icon" is the day dark mode gains its first unthemed glyph.

### 4.3 Shared Component Library

#### FR-UI-012 — Page header block

The title-description-actions trio appears atop nearly every page, and standardizing it ended
the era where one module's heading sat at 24px and another's at 30px. Pages now declare their
header in three attributes and spend their energy on content.

#### FR-UI-013 — Record manager scaffold

Picture the enrollment officer's busiest week: hundreds of registrations to triage, searching by
name, filtering by status, selecting rows in bulk. The record-manager scaffold gives her the same
debounced search, per-page control, selection bar, and empty state on every list page, because
all of those behaviors were implemented once, tested once, and reused everywhere since.

#### FR-UI-014 — Labeled read-only values

Detail pages are full of small facts — a phone number here, a company address there — and without
a shared labeled-value component each one invents its own spacing and icon treatment. The
display-field component absorbs that trivia so detail views stay visually quiet.

#### FR-UI-015 — Destructive-action confirmation

Nobody should delete a record with a single misclicked button. The confirm wrapper forces a
modal beat — title, message, explicit confirm and cancel — bound to component state, which means
the dangerous action always pauses for intent and every such pause looks and behaves the same.

#### FR-UI-016 — Header action cluster

Theme switching, language switching, notifications, and the user menu arrive as one cluster with
per-slot visibility props. A minimal public-facing shell can hide everything but the language
switcher, while the full authenticated header shows all four, and neither configuration requires
forking the component.

#### FR-UI-017 — Brand marks with size control

The logo appears at a dozen sizes across shells, emails, and documents, and each hard-coded
`<img>` was once a separate maintenance burden when the mark changed. Size and invert props
collapse all of that into one component whose tagline variant toggles with a flag.

#### FR-UI-018 — User avatars

An avatar with a configurable size sounds trivial until three modules render three different
fallback behaviors for users without photos. One component owns the sizing and the fallback, and
every face in the product is round in the same way.

#### FR-UI-019 — Footer attribution

The credits line carries the product attribution everywhere and the build version only in local
environments — a small discretion that keeps production footers clean while giving developers
the version signal where they actually need it.

### 4.4 CRUD Tables on BaseRecordManager

#### FR-UI-020 — Record tables inherit the base manager

The base-class mandate does not stop at Actions and Models: any Livewire table managing records
extends `BaseRecordManager` and receives search, filtering, sorting, pagination, and bulk
selection without reimplementation. Before this rule, each new admin table re-solved debouncing
and pagination slightly differently; after it, the fortieth table behaves exactly like the
first, and a single base-class fix improves every list in the product at once.

### 4.5 TallStackUI-First Elements

#### FR-UI-021 — Shared component dialect before custom markup

When a developer needs a button, table, badge, alert, or icon, the search starts in the
TallStackUI catalog and only falls back to custom markup when nothing fits. This ordering is
what lets a brand-preset change or a dark-mode toggle propagate across the product untouched:
every shared component already speaks the theme's language, while each bespoke element is a
future theming bug waiting for its sprint.

### 4.6 Responsive Drawer

#### FR-UI-022 — Sidebar hides below the large breakpoint

On narrow screens the sidebar yields to a drawer behind a header toggle, because a persistent
220px column on a 360px phone leaves no room for actual content. The breakpoint is fixed and
shared, so responsive behavior never becomes a per-page negotiation.

#### FR-UI-023 — Sidebar persists at large widths

Above the breakpoint the sidebar simply stays visible — no toggle state to manage, no overlay to
dismiss. Desktop users navigating between sections get one-click reachability at all times,
which is the quiet baseline the drawer pattern exists to preserve.

#### FR-UI-024 — Switchers relocate by viewport

Theme and language controls live in the sidebar on phones and in the header on larger screens,
so they remain reachable in both layouts without duplicating state. A teacher switching to
Indonesian on her phone finds the control exactly where the layout promises it.

#### FR-UI-025 — Constrained content with scrollable tables

The content container centers at a maximum width with responsive padding while wide tables gain
horizontal scroll instead of breaking the layout. This pair of rules is what keeps a
data-dense admin table usable on a school computer from 2019 without horizontal page scroll.

### 4.7 SPA Navigation and Feedback

#### FR-UI-026 — Partial swaps for internal links

Every internal link carries the SPA navigation attribute, turning full document reloads into
content swaps that preserve header state, scroll position, and history. The performance win is
real, but the deeper win is behavioral: navigation stops resetting the user's context on every
click.

#### FR-UI-027 — Focus reset after transitions

A partial swap that leaves focus on a now-detached link strands keyboard and screen-reader
users in the void. Resetting focus to the page heading after each transition closes the
accessibility gap that SPA navigation would otherwise open, and the browser journey for this
behavior fails if any transition forgets it.

#### FR-UI-028 — Announced toast feedback

Success and error toasts render through the shared toast container and speak through a live
region, so a placement approval confirms itself identically for sighted and screen-reader
users. Feedback that only flashes pixels is feedback half delivered.

### 4.8 Accessible Chrome

#### FR-UI-029 — Skip link first in tab order

The skip-to-content link sits before everything else in the tab order, invisible until focused,
landing directly on the main content region. For keyboard users on content-heavy admin pages it
is the difference between one keystroke and thirty to reach the actual work.

#### FR-UI-030 — Semantic landmarks in the shell

Sidebar, main content, top bar, and footer map to their landmark elements once, in the shell,
rather than per page. Assistive technology can then offer "jump to main content" on every
screen in the product, because every screen agrees on what its regions are.

#### FR-UI-031 — Dismissible drawer overlay

The mobile drawer's overlay carries an accessible name and yields to Escape, which matters most
to the users who cannot simply click outside it. An overlay without a keyboard exit is a trap;
this one was built with the exit first.

#### FR-UI-032 — Names and state beyond color

Icon-only buttons speak their purpose through accessible names, and the current-page indicator
never trusts hue alone. These two habits together mean the interface survives screen readers,
monochrome displays, and color-vision differences without losing meaning.

#### FR-UI-033 — Live regions for dynamic content

Toasts, validation messages, and Livewire-driven updates all render inside announced
containers. Without this, a form that rejects input silently for a screen-reader user looks
broken rather than merely strict — the live region is what turns rejection into guidance.

#### FR-UI-034 — Role-forbidden items never render

Beyond unit-level filtering logic, a browser test logs in as a restricted role and asserts the
forbidden entries are absent from the rendered sidebar entirely. This is the fail-closed proof:
not "clicking is denied" but "the option was never offered," which no policy test alone can
demonstrate.

---

## 5. Non-Functional Requirements

Project-level chrome constraints. `Target` holds the concrete SLO; `N/A` means enforcement is
architectural and verified via scans or tests rather than a runtime number.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-UI-001 | Mobile navigation reachable in at most two taps; full keyboard traversal with no dead ends | ≤ 2 taps | P1 | B | Partial |
| NFR-UI-002* | Shell chrome contrast values meet WCAG 2.1 AA minimums on interactive elements | AA ratios | P1 | B | Full |
| NFR-UI-003 | Every chrome string exists in both `lang/en/` and `lang/id/` through `__()` keys | 0 missing keys | P0 | A | Partial |
| NFR-UI-004 | Layout and UI components colocated in Core with no per-module duplication; a menu change is one config file | 1 file | P1 | A | Partial |
| NFR-UI-005 | Every non-trivial workflow ships a guide page at `guides/{feature}-guide.blade.php` | 100% coverage | P1 | A | Partial |

### 5.1 Experience

#### NFR-UI-001 — Two taps and full keyboard reach

A supervisor checking attendance between factory rounds does it on a phone, one hand free, and
gives the navigation exactly two taps of patience. The same chrome must also survive a
keyboard-only pass with no focus traps, because the operator who navigates by Tab is often the
same person filing fifty records before lunch.

#### NFR-UI-002* — Contrast minimums on chrome

The exact ratio numbers on interactive chrome are a visual property no automated test asserts —
hence the non-testable marker — but the commitment stands: text and control contrast meets the
AA minimums, verified by manual spot-check during UI review rather than by the traceability
scanner.

### 5.2 Localization and Structure

#### NFR-UI-003 — Dual-language chrome strings

Indonesian primary, English secondary, with no hardcoded chrome text anywhere in the shell: menu
titles, item labels, skip link, drawer labels, search and filter captions all resolve through
the translation helper in both locales. A missing key in either language file fails the duality
scan, which is how a monolingual slip gets caught before it reaches a bilingual school.

#### NFR-UI-004 — Single home for chrome

All shell and component files live in Core's view namespace, and the proof is operational: add
a menu entry to the config and the sidebar changes with zero Blade edits. The moment a module
forks a layout "temporarily," the colocation scan and review practice conspire to surface it.

#### NFR-UI-005 — Guide page per complex workflow

Workflows that cannot explain themselves — multi-step wizards, bulk imports, assessment flows —
each ship a guide page under the guides namespace. Presence is checked structurally, since a
missing guide is a documentation gap no unit test would otherwise catch, and the schools that
need guidance most are the ones least likely to file an issue about it.

---

## 6. API / Data Contracts

### Menu Config (`config/menu.php`)

```php
// config/menu.php — groups array
return [
    'groups' => [
        'dashboard' => [
            'roles' => ['super_admin', 'admin', 'teacher', 'supervisor', 'student'],
            'title' => 'common.sidebar.navigation',
            'items' => [
                ['route' => 'dashboard', 'icon' => 'o-home', 'label' => 'dashboard.title'],
                // optional: 'roles' => ['super_admin'],  // item-level override
                // optional: 'disabled' => true,          // render as muted span
            ],
        ],
    ],
];
```

### Layout Components

| Component | Props | Purpose |
| --------- | ----- | ------- |
| `x-core::layouts.base` | `title`, `bodyClass` | Root HTML shell |
| `x-core::layouts.app` | `title`, `header`, `footer`, `context` (breadcrumb) | Authenticated shell |
| `x-core::layouts.guest` | `title`, `header`, `footer` | Public shell |
| `x-core::layouts.sidebar` | `items` (default from `config('menu.groups')`) | Role-filtered navigation |
| `x-core::layouts.header` | `header` (desktop title) | Sticky top bar |
| `x-core::layouts.base.head` | `title` | Head block: meta, favicon, Vite |
| `x-core::layouts.base.footer` | `fullWidth` | Footer with credit |

### UI Components (`x-core::ui.*`)

| Component | Props | Purpose |
| --------- | ----- | ------- |
| `page-header` | `title`, `description`, actions slot | Page title block |
| `record-manager` | `title`, `subtitle`, slots for header actions, stats, filters, selection bar, empty state, modal, default table | CRUD list scaffold |
| `display-field` | `label`, `value`, `icon` | Labeled read-only value |
| `confirm` | `title`, `message`, `icon`, confirm/cancel text, confirm class | Destructive-action modal |
| `navbar-actions` | `showTheme`, `showLanguage`, `showNotifications`, `showUser` | Header action cluster |
| `brand` / `logo` | `size`, `invert`, tagline toggle | Brand mark |
| `avatar` | `user`, `size` | User avatar |
| `credit` / `credits` | `showVersion`, `class` | Footer attribution |

### Livewire Selection

```php
#[Layout('core::layouts.app')] // or 'core::layouts.guest' for public pages
public function render(): View { ... }
```

---

## 7. Design Decisions

Design Decisions are **optional** to test, like Use Cases (§3). `Layer` / `Status` stay `—` unless
a decision has a code-testable consequence.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-UI-001 | Navigation lives in `config/menu.php` as static role-tagged config, resolved at render | P0 | — | — |
| DD-UI-002 | Pure-presentation chrome ships as anonymous Blade components, not Livewire components | P1 | — | — |
| DD-UI-003 | Internal links use `wire:navigate` partial swaps with focus reset | P0 | — | — |
| DD-UI-004 | Responsive sidebar uses the drawer pattern with the large breakpoint as the persistence line | P1 | — | — |
| DD-UI-005 | Accessibility chrome is implemented once in the shell rather than repeated per page | P0 | — | — |

### 7.1 Navigation and Components

#### DD-UI-001 — Config file over database menu

Storing navigation in the database sounds flexible until someone asks who audits menu edits and
how a fresh install seeds them. Menu entries are code-level concerns — route names, icon keys —
so they live in versioned config, change in single-file edits, and cost zero queries at render.
The concession is real but small: adding an item needs a config change and cache clear, which
fits navigation's low frequency and developer ownership.

#### DD-UI-002 — Anonymous Blade for stateless chrome

The header block, record scaffold, and confirmation modal carry no server state; they compose
slots and shared components. Making them Livewire components would pay reactivity overhead on
every render for zero benefit, so they stay anonymous Blade while reactivity lives with the
parent Livewire component that owns the state — a split that has held without exception.

### 7.2 Motion and Access

#### DD-UI-003 — Partial swaps with focus repair

Full reloads were discarding scroll position and focus on every click, and the SPA navigation
attribute fixed that while keeping browser history and back-button semantics for free. The one
genuine cost — focus stranded on detached nodes — is repaired by the reset rule, and pages
without scripting still degrade to ordinary full loads.

#### DD-UI-004 — Drawer with a fixed persistence line

The drawer pattern arrived with keyboard and screen-reader behavior already solved — Escape to
close, a focusable overlay — which meant the responsive sidebar satisfied its accessibility
requirements with no custom scripting. The large breakpoint draws the line between overlay and
persistent modes, and everything else in the product aligns to that same line.

#### DD-UI-005 — Shell-owned accessibility

Skip links, landmarks, and focus reset implemented once cover every page that uses the shell,
while per-page implementations would guarantee drift within a quarter. The only pages allowed
outside the core shell inherit the same base, so even the exceptions keep the contract.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|----------------|
| Authenticated pages using the canonical shell | 100% of modules | Layout scan + review |
| Mobile sidebar reachable from any page | ≤ 2 taps | Manual + browser journey |
| Internal links navigating without full reload | 100% | Browser journey |
| Menu item added for a new feature | 1 file (`config/menu.php`) | Change review |
| Keyboard-only full navigation | Pass, no dead ends | Browser journey |
| Shell server render time | < 100 ms | Manual profiling |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|------------------|
| [base-classes](SE5Q9-base-classes.md) | `BaseModel`, `BaseRecordManager`, and the base architecture shell pages render against |
| [module-discovery](I1BCV-module-discovery.md) | Blade namespace registration (`x-core::`) and module view discovery |
| [settings-infrastructure](YB22J-settings-infrastructure.md) | Settings store and keys read by the shell |
| [branding-theme-locale](52O1I-branding-theme-locale.md) | Brand helper, CSS variables, theme and locale switchers consumed by base, guest, and navbar-actions |

### Build Guide

After implementing this spec, the system has a canonical authenticated and guest shell, a
config-driven role-filtered sidebar, a reusable `core::ui.*` library with `BaseRecordManager`
tables, and WCAG 2.1 AA chrome. Build the notification infrastructure next (the bell slot hosts
its Livewire component), then the dashboard, which renders inside the authenticated shell.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [notification-infrastructure](TXR2H-notification-infrastructure.md) | `navbar-actions` bell slot renders the notification center trigger |
| 2 | [dashboard](CKKZC-dashboard.md) | Role dashboards render inside `core::layouts.app` with role-specific widgets |
| 3 | [authentication](YB7RG-authentication.md) | Auth pages use the guest and base shells with localized chrome |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all feature specs grouped in 12 phases
- [Architecture](D2FT3-architecture.md) — module-first 4-layer model and the Action Triad
- [Branding, theme & locale](52O1I-branding-theme-locale.md) — theming pipeline this spec consumes
- [Settings infrastructure](YB22J-settings-infrastructure.md) — settings store read by the shell
- [Dashboard](CKKZC-dashboard.md) — role widgets rendered inside the authenticated shell
- [Base-class mandate ADR](../adr/adr-base-class-mandate.md) — why tables extend `BaseRecordManager`
- [MVP trim ADR](../adr/adr-mvp-spec-trim.md) — what stays post-MVP and why
