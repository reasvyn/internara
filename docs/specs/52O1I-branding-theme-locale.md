# 52O1I — Branding, Theme & Locale

> **Spec ID:** 52O1I
> **Status:** Full
> **Owner:** Settings
> **Depends on:** YB22J

## Description

Internara's visual identity and language layer: brand resolution with color presets and live
asset uploads, cookie-and-localStorage theme switching with generated CSS variables, and EN/ID
locale switching applied per request. The underlying store — resolution chain, typed settings,
observer invalidation — belongs to [settings-infrastructure.md](YB22J-settings-infrastructure.md);
this spec covers what schools see and touch.

---

## 1. Problem Statements

### PS-1 — Every School Wants Its Own Face Without a Redeploy

A principal asks for the school crest in the header, the school green as the primary color,
and the site title in Indonesian — and none of that may require editing environment files or
rebuilding assets. Brand identity must persist as database settings and uploaded media, applied
live the moment the admin saves.
**→ Requirement:** FR-BRAND-001/002 (resolution), FR-BRAND-005/006 (presets), FR-BRAND-007/008/009
(assets).

### PS-2 — Dark Mode Is a Browser Habit, Not an Account Attribute

A teacher toggles dark mode at night on a personal laptop and expects light mode still on the
classroom projector — same account, two preferences. Persisting that choice per user would write
a database row on every toggle for zero cross-device benefit, while a database roundtrip on
every page just to pick a palette would tax the most frequent read path in the app.
**→ Requirement:** FR-BRAND-011 (cookie plus localStorage), FR-BRAND-012/013 (switcher and
applier), DD-BRAND-001 (cookie rationale).

### PS-3 — Two Languages, One Middleware, Zero Queries

Vocational schools operate in Indonesian; developers and bilingual programs need English. The
operator's choice must survive navigation, apply before the first string renders, and cost no
database query — while the admin-configured default still governs fresh browsers that never
chose anything.
**→ Requirement:** FR-BRAND-016/017/018 (locale stack, resolution chain, bilingual duality).

---

## 2. Goals & Non-Goals

### Goals

- **Live brand identity without redeployment** — name, logo, favicon, palette, and custom CSS persist as settings and media. *Why:* PS-1 makes redeploy-per-school operationally impossible.
- **One-click color presets plus free-form tuning** — six curated palettes with instant apply, individual hex fields beneath. *Why:* most schools pick a preset; a few insist on exact hex values.
- **Per-browser theme and locale preferences** — cookies and localStorage, never database rows. *Why:* PS-2 and PS-3 show these are device habits, not account data.
- **Runtime language toggle with full EN/ID duality** — every string through `__()`, mirrored keys, instant switching. *Why:* Indonesian-primary product with English as a first-class second language.
- **Generated CSS variables with dark-mode palettes** — brand colors compiled to variables, cached, with computed dark shades. *Why:* one palette definition drives both modes without dual maintenance.

### Non-Goals

- **Settings storage machinery**. *Why:* owned by [YB22J](YB22J-settings-infrastructure.md); this spec only consumes its keys.
- **Per-user theme or locale accounts settings**. *Why:* per-browser persistence covers the need with no write amplification.
- **Real-time sync of preferences across tabs**. *Why:* each tab reads its own storage on load; cross-tab broadcast is depth without a reported pain.
- **Languages beyond English and Indonesian**. *Why:* the product boundary is bilingual; a third locale is a post-MVP translation project.

---

## 3. User Stories / Use Cases

One admin customizes, every user switches appearance and language; the four stories follow that
split from upload through daily toggling.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-BRAND-001 | Admin uploads a logo or favicon and sees it live without saving the full form | P0 | F | Full |
| UC-BRAND-002 | Admin applies a color preset or tunes individual colors and previews the result | P0 | F | Full |
| UC-BRAND-003 | Any user switches between light, dark, and system theme with immediate effect and no database write | P1 | B | Full |
| UC-BRAND-004 | Any user switches between English and Indonesian with the choice persisting across visits | P0 | F | Full |

### 3.1 Identity Customization

#### UC-BRAND-001 — Admin uploads logo or favicon

The admin drags the school crest into the logo field mid-morning and the preview updates before
she lifts her finger — no save button involved. The Livewire `updated*` hook validates the file
(`image`, size-capped per asset kind), stores it through the media library, persists the
returned URL as a setting, and refreshes the preview. A failed upload reports immediately while
the rest of her unsaved form values sit untouched, which is precisely why the upload refuses to
wait for the full save.

#### UC-BRAND-002 — Admin applies a preset, then fusses with hex

Six swatches greet the admin: sky, emerald, violet, rose, ocean, slate. One click floods all
four color fields and the surrounding UI repaints, because the preset path writes through the
same settings-and-observer pipeline as manual edits. The particular green is still slightly
off, so she nudges the primary hex by hand — the form compares the quartet against known
presets, stops claiming a preset match, and treats the combination as custom. Both paths end in
the same place: validated hex strings persisted, CSS cache cleared, palette live.

### 3.2 Appearance and Language

#### UC-BRAND-003 — Theme follows the device, not the account

Night shift at a partner workshop: the supervisor taps the theme toggle to dark, and the page
repaints without reload — the switcher persists to localStorage, the applier sets both the
semantic `data-theme` attribute and the `.dark` class, and the mirrored cookie keeps
server-rendered markup consistent on the next navigation. Morning on the shared classroom PC,
light mode greets her without any account setting to reset, because nothing about last night
was ever written to the database.

#### UC-BRAND-004 — Language choice that outlives the session

A new teacher opens the portal on a fresh browser and reads Indonesian — the admin-configured
default — without choosing anything. Curious, she flips to English in the language switcher;
the locale setter validates against the supported pair, queues a forever cookie, and the next
request renders English through middleware before any string is composed. Months later, after
countless sessions, that cookie still answers first, and clearing it simply returns her to the
school default rather than breaking anything.

---

## 4. Functional Requirements

Brand resolution (§4.1), identity editing (§4.2), theming (§4.3), and locale (§4.4) form the
complete surface. Every row below is implemented.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) ·
`A` = Arch (structure/contracts). **Status legend:** `Planned` = not started ·
`Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-BRAND-001 | `Brand::resolve()` returns a `BrandData` DTO with name, title, logo, favicon, colors, and static metadata, falling back to `AppInfo` defaults on exception | P0 | F | Full |
| FR-BRAND-002 | Brand resolution is dual-path: database settings for identity, `AppInfo` for static metadata | P0 | F | Full |
| FR-BRAND-003 | `Brand::colors()` caches under the `brand.colors` registry key for 24 hours | P1 | F | Full |
| FR-BRAND-004 | Six color presets (sky, emerald default, violet, rose, ocean, slate) are defined in config with one-click apply and preset detection | P0 | F | Full |
| FR-BRAND-005 | Logo uploads validate as images up to 1024 KB (PNG, JPEG, WebP) and favicon uploads up to 512 KB (PNG, JPEG, WebP, ICO) | P0 | F | Full |
| FR-BRAND-006 | `UploadBrandAssetAction` stores via Spatie Media Library collections and `RemoveBrandAssetAction` deletes and clears the setting key | P0 | F | Full |
| FR-BRAND-007 | Custom CSS persists as the `brand.custom_css` string setting, writable only by `super_admin`, rendered in a dedicated style block after the theme stylesheet behind a safety scan | P1 | F | Full |
| FR-BRAND-008 | Theme preference is mirrored in the `theme` cookie for server rendering while localStorage remains the authoritative client store; no database involvement | P1 | F | Full |
| FR-BRAND-009 | Theme switching renders through the TallstackUI theme switch with localStorage persistence and framework event listening; no custom switcher component | P1 | B | Full |
| FR-BRAND-010 | The theme applier sets both the `data-theme` attribute and the `.dark` class on `<html>` from the framework theme event | P1 | B | Full |
| FR-BRAND-011 | `Theme::cssVariables()` generates light and dark palette variables cached for one hour | P1 | F | Full |
| FR-BRAND-012 | The `Color` helper provides hex conversion, luminance, contrast, lightening, darkening, and shade computation used by dark-mode derivation | P2 | U | Full |
| FR-BRAND-013 | Supported locales are exactly EN and ID, declared in the `Locale::SUPPORTED_LOCALES` constant | P0 | U | Full |
| FR-BRAND-014 | Locale preference persists in a forever `locale` cookie, never in the database | P0 | F | Full |
| FR-BRAND-015 | `SetLocaleMiddleware` applies the resolved locale on every request via `App::setLocale()` | P0 | F | Full |
| FR-BRAND-016 | `Locale::set()` validates against supported locales, queues the cookie, and sets the runtime locale | P0 | F | Full |
| FR-BRAND-017 | `Locale::current()` resolves cookie → stored `default_locale` setting → `app.locale` config → `DEFAULT_LOCALE`, first supported value winning | P0 | F | Full |
| FR-BRAND-018 | Every branding, theme, and locale string uses `__()` with mirrored keys in `lang/en/` and `lang/id/`, switchable at runtime without restart | P0 | A | Full |

### 4.1 Brand Resolution

#### FR-BRAND-001 — One DTO for the whole identity

The layout header, the login card, the PDF certificate footer, and the setup wizard all ask
the same question — "who is this school on screen right now" — and `Brand::resolve()` answers
it once as a `BrandData` value: name, title, logo URL, favicon URL, color quartet, plus
version, author, and license metadata. When the database is half-configured or throwing, the
method catches and answers from `AppInfo` instead, because a branding lookup must never be
the reason a public page returns a 500.

#### FR-BRAND-002 — Two doors for two kinds of facts

Early in the project, brand reads collided with `AppInfo::name()` — two sources claiming the
same key, with config-cache semantics muddying which won. The dual-path split ended that:
anything an admin can change (name, title, logo, colors) resolves from database settings via
`brand()`; anything compiled in (version, author, license) resolves from `AppInfo`. The seam
is invisible to callers of `resolve()`, which assembles both halves into the single DTO the
views consume.

#### FR-BRAND-003 — Day-long color cache

Brand colors render on every page, so resolving them per request from the settings table
would add a query to the hottest path in the app. The 24-hour `brand.colors` entry removes
it — and the correctness backstop is the settings observer, which clears exactly this key
whenever a theme-related setting changes. A TTL that long would be reckless without the
observer; with it, the TTL is pure savings because no write can outlive its own
invalidation.

### 4.2 Identity Editing

#### FR-BRAND-004 — Six presets, honest detection

Emerald ships as the default because the product's own chrome was designed against it, and
the other five — sky, violet, rose, ocean, slate — cover the common school-color families
without inviting rainbow chaos. Applying a preset floods all four fields atomically; the
detector runs the reverse comparison so the UI can badge "Emerald" while the quartet matches
and quietly drop the badge the moment a hand edit diverges. Presets are suggestions with
memory, not a mode the system gets stuck in.

#### FR-BRAND-005 — Upload limits that match asset roles

A header logo at 1024 KB and a favicon at 512 KB reflect how the files are actually used: the
logo renders large across layouts and certificates, the favicon ships on every page load where
bytes compound. MIME whitelists (PNG, JPEG, WebP for logos; ICO additionally for favicons)
reject SVGs at the boundary — not from format snobbery but because inline-capable vector
formats widen the XSS surface the escaped-output invariant works to close. Oversize or
wrong-type files fail with a field error, never a silent downscale.

#### FR-BRAND-006 — Media library owns the bytes, settings own the pointer

The upload action writes bytes into the `brand_logo` or `brand_favicon` media collection and
returns a URL; the setting stores that URL string and nothing else. Removal reverses both
halves: the media row deletes and the setting key clears, so no orphaned file lingers and no
dangling URL renders a broken image. Splitting ownership this way keeps binary lifecycle
(conversions, cleanup) inside the media library while the settings store keeps its
strings-only discipline.

#### FR-BRAND-007 — Custom CSS with a leash

One school asked for a shaped login card the palette system could not express, and the answer
was a single textarea rather than a theming framework: free-text CSS in `brand.custom_css`,
rendered after the theme stylesheet so it wins ties. Power of that kind needs two guards —
only `super_admin` may write it, and a safety scan rejects script-adjacent constructs
(`<script>`, external `url()`, `@import`, `expression()`) before persistence. The writer is
already the highest-trust role; the scan exists for the day credentials leak, not for the
admin's intent.

### 4.3 Theming

#### FR-BRAND-008 — Cookies mirror, localStorage decides

Server-rendered markup needs the theme before JavaScript runs, or the first paint flashes the
wrong palette; client interactivity needs synchronous reads without cookie parsing on every
toggle. The split serves both: the `theme` cookie (`light`, `dark`, `system`) lets the base
layout set `data-theme` and the `.dark` class pre-hydration, while localStorage holds the
authoritative client value the switcher actually reads. The database never learns any of
this, which is the entire point — a preference that changes with the hour has no business in
a relational row.

#### FR-BRAND-009 — The framework switcher, not a bespoke one

The codebase once carried a custom Livewire theme component alongside DaisyUI and Alpine
fallbacks — three theming stacks arguing over the same `<html>` tag. That coexistence ended:
switching renders through the TallstackUI theme switch alone, persisting to localStorage and
emitting the framework's theme event. Deleting the bespoke component removed a whole class of
"which switcher owns this toggle" bugs and left accessibility (focus, ARIA) to the component
library that already solved it.

#### FR-BRAND-010 — Two attributes, one applier

Tailwind's `dark:` variant keys off the `.dark` class while the semantic palette keys off
`data-theme` — applying only one leaves half the design system on the wrong mode, a bug that
manifests as dark backgrounds with light-mode text. The applier in `app.js` listens for the
framework theme event and sets both attributes together, every time, so the two systems can
never disagree about which mode is active.

#### FR-BRAND-011 — Generated variables, hourly cache

`Theme::cssVariables()` compiles the four brand colors into the full variable set —
`--color-primary`, `--color-secondary`, `--color-accent`, the `--color-base-*` ramp, and the
`--brand-*` aliases — for both light and dark palettes, with dark-mode derivation lightening
the primaries and recomputing base tones. The one-hour cache keeps per-request generation off
the hot path; like the brand cache, its safety net is the settings observer clearing it on
any theme-related write, so palette edits propagate within the request that made them.

#### FR-BRAND-012 — The Color helper's quiet arithmetic

Nobody notices this helper until dark mode looks wrong: washed-out primaries, unreadable
muted text, a base ramp with visible banding. The luminance and contrast functions pick
readable foregrounds, `lighten()` at forty percent lifts primaries for dark surfaces, and
the shade computers build ramps smooth enough to survive large flat areas. Pure arithmetic,
unit-testable without a database, and the reason the generated dark palette looks designed
rather than inverted.

### 4.4 Locale

#### FR-BRAND-013 — Exactly two languages

The supported-locales constant names `en` and `id` and nothing else — not as a limitation to
apologize for but as the product boundary the translation workflow is staffed for. Every
locale-accepting method validates against this pair, so a crafted cookie value of `fr` falls
through to the next resolution layer instead of producing half-rendered pages or missing-key
noise. A third language arrives as a project decision with translators attached, never as a
string somebody sneaks into a cookie.

#### FR-BRAND-014 — Forever cookie, zero rows

Locale joins theme in cookie-only persistence: a forever cookie, no table, no model, no
query. The reasoning mirrors the theme story with one addition — unauthenticated visitors
hit the landing page before any account exists, so a database-backed preference could not
serve them anyway. Cookie loss degrades to the school default, which is a acceptable
single-click recovery rather than data loss.

#### FR-BRAND-015 — Middleware applies before strings render

`SetLocaleMiddleware` runs on every request and calls `App::setLocale()` with the resolved
locale before any controller, component, or view composes a user-facing string. Ordering is
the whole contract: locale applied after string composition would translate navigation but
not page content, a half-localized render worse than none. Because the middleware reads the
cookie and the cached setting only, it adds no query to the request.

#### FR-BRAND-016 — Set validates, then persists, then applies

The `Locale::set()` sequence matters in its exact order: reject unsupported values first, so
invalid input never reaches storage; queue the cookie second, so the choice survives the
session; set the runtime locale third, so the current response already reflects the change.
Callers get a boolean verdict rather than an exception for bad input, keeping the language
switcher a single-click control with no error state to design for.

#### FR-BRAND-017 — Four layers, first supported value wins

A fresh browser with no cookie reads the admin's `default_locale` setting — Indonesian on a
typical install — so the school's choice governs visitors who never chose. A returning
browser's cookie outranks it, honoring personal choice over institutional default. Beneath
both sit the `app.locale` config and the `DEFAULT_LOCALE` code constant as last resorts that
should rarely speak. Each layer validates before winning; an unsupported value anywhere
simply yields to the next layer down.

#### FR-BRAND-018 — Bilingual strings with runtime toggle

Indonesian-first does not mean Indonesian-only: every user-facing string in branding, theme,
and locale surfaces passes through `__()`, each key mirrored in `lang/en/` and `lang/id/`,
and switching locales re-renders without restart or redeploy. A key missing from one side
renders raw in that language — the visible scar that the locale consistency scan exists to
prevent — so duality is enforced mechanically rather than by translator diligence alone.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-BRAND-001 | Brand asset uploads validate MIME type and file size server-side | 100% of uploads gated; zero unsafe stored files | P0 | F | Full |
| NFR-BRAND-002 | Logo and favicon uploads preview live without page reload | preview < 3s after selection | P1 | B | Full |
| NFR-BRAND-003 | Color preset selection previews all four colors before applying | preview before commit, 100% of presets | P2 | B | Full |
| NFR-BRAND-004 | Theme toggle applies without server roundtrip | < 100ms client-side | P1 | B | Full |
| NFR-BRAND-005 | Locale switch persists in cookie and applies on next load | < 1s to next-load render | P0 | F | Full |
| NFR-BRAND-006 | Logo and favicon uploads expose alt text for screen readers | 100% of upload controls labelled | P1 | B | Full |
| NFR-BRAND-007 | Preset selection and theme/locale switches are perceivable beyond color and announced via live regions with labelled inputs | WCAG 2.1 AA on identity controls | P1 | B | Full |
| NFR-BRAND-008 | All identity strings use `__()` with `en`/`id` key parity | zero missing-key pairs | P0 | A | Full |

### 5.1 Uploads and Responsiveness

#### NFR-BRAND-001 — Server-side validation as the real gate

Client-side file pickers lie — `accept="image/*"` is a suggestion the browser honors and an
attacker ignores. The MIME and size checks inside the upload actions are the actual boundary,
and they run on the stored bytes rather than the claimed extension. A crafted upload meeting
a friendly client but hostile content dies here, before the media library ever sees it.

#### NFR-BRAND-002 — Previews faster than doubt

An admin who waits ten seconds after selecting a logo starts wondering whether the upload
worked and clicks again, producing duplicate media rows. The sub-three-second preview —
temporary upload URL swapped into the preview slot — closes the feedback loop before doubt
forms. Speed here is not polish; it is duplicate prevention.

#### NFR-BRAND-003 — See the palette before committing it

Applying a preset sight-unseen and discovering the rose primary clashes with the school crest
wastes a save cycle and a cache invalidation. The picker renders all four colors of the
hovered preset first, so the decision is visual rather than nominal. Nobody should have to
memorize what "ocean" means in hex.

#### NFR-BRAND-004 — Toggles that feel instant

Theme state lives in localStorage precisely so the toggle needs no network: read, write,
repaint, all inside a hundred milliseconds. Any perceptible lag would signal a regression —
a server roundtrip smuggled back into the path — rather than a performance tradeoff to tune.

### 5.2 Persistence and Access

#### NFR-BRAND-005 — Locale choice that sticks

The forever cookie is the durability story: set once, honored for months across sessions
without re-selection. Applying on the very next load (not the one after) matters because a
switch that visibly does nothing reads as broken — the middleware ordering guarantees the
next render already speaks the chosen language.

#### NFR-BRAND-006 — Uploads described, not just shown

A logo preview that is purely visual locks out screen-reader operators from confirming their
own upload. Alt text on the preview plus a textual filename confirmation makes the upload
verifiable by ear, which is the difference between an admin who knows the crest landed and
one who must ask a colleague to look.

#### NFR-BRAND-007 — Identity controls beyond color and silence

Check icons and borders — not hue alone — mark the selected preset, so color-blind operators
choose as confidently as anyone. Theme and locale switches announce through live regions
instead of changing silently, and every input carries a real label. These are the AA
behaviors that survive framework migrations because they live in markup semantics, not in
component-library styling.

#### NFR-BRAND-008 — Duality without drift

Two translation files describing the same interface will drift unless something mechanical
compares them. The parity scan is that mechanism: every `__()` key resolved in English must
resolve in Indonesian and vice versa. New strings ship with both translations or the scan
fails the commit — diligence optional, enforcement automatic.

---

## 6. API / Data Contracts

### 6.1 BrandData and Brand

```php
final readonly class BrandData extends BaseData
{
    public function __construct(
        public string $name,
        public string $title,
        public string $logo,
        public string $favicon,
        public array $colors,
        public string $version,
        public string $authorName,
        public string $authorEmail,
        public string $description,
        public string $license,
        public string $gitUrl,
    ) {}
}

final class Brand
{
    public static function name(): string;
    public static function title(): string;
    public static function logo(): string;
    public static function favicon(): string;
    public static function colors(): array;
    public static function resolve(): BrandData;
    public static function get(string $key, mixed $default = null): mixed;
    public static function clearCache(): void;
}
```

`Brand::colors()` caches 24h under `brand.colors`. `resolve()` never throws — exceptions fall
back to `AppInfo` defaults.

### 6.2 Theme and Color

```php
final class Theme
{
    public static function defaults(): array;
    public static function presets(): array;
    public static function all(): array;
    public static function get(string $key): string;
    public static function base(): string;
    public static function cssVariables(): array;
}
```

Generated variables: `--color-primary`, `--color-secondary`, `--color-accent`,
`--color-base-{100,200,300,content}`, `--brand-{primary,secondary,accent}`; cached 1h under
`theme.css_variables`. Presets (`sky`, `emerald`, `violet`, `rose`, `ocean`, `slate`) are
defined in config with emerald as default.

### 6.3 Locale

```php
final class Locale
{
    public const DEFAULT_LOCALE = 'en';
    public const SUPPORTED_LOCALES = [
        'en' => ['name' => 'English', 'native' => 'English'],
        'id' => ['name' => 'Indonesian', 'native' => 'Bahasa Indonesia'],
    ];

    public static function set(string $locale): bool;
    public static function current(): string;
    public static function all(): array;
    public static function keys(): array;
    public static function isSupported(string $locale): bool;
    public static function metadata(string $locale): ?array;
}
```

`current()` chain: `locale` cookie → `setting('default_locale')` → `config('app.locale')` →
`DEFAULT_LOCALE`. The stored `default_locale` (default `id`) is the admin-configured default;
the constant is the code-level last resort.

### 6.4 Asset Constraints

| Asset | Rule | MIME | Max |
| ----- | ---- | ---- | --- |
| Logo | `nullable\|image\|max:1024` | PNG, JPEG, WebP | 1024 KB |
| Favicon | `nullable\|image\|max:512` | PNG, JPEG, WebP, ICO | 512 KB |
| Custom CSS | free-text string, `super_admin` only, safety scan | n/a | setting row |

---

## 7. Design Decisions

Each decision records a settled tradeoff with its rationale woven into the narrative; the
linked FR rows carry the verifiable behavior, so no separate test layer is recorded here.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-BRAND-001 | Theme and locale preferences persist in cookies and localStorage, never in the database | P0 | — | — |
| DD-BRAND-002 | Brand facts resolve dual-path: database for identity, `AppInfo` for static metadata | P0 | — | — |
| DD-BRAND-003 | Logo and favicon upload immediately on selection instead of waiting for form save | P0 | — | — |
| DD-BRAND-004 | Custom CSS is a sandboxed string setting rendered after the theme stylesheet | P1 | — | — |
| DD-BRAND-005 | Locale default comes from the stored setting with cookie override, not from code constants | P0 | — | — |
| DD-BRAND-006 | Theming runs on a single TallstackUI stack; the custom switcher and fallback stacks were removed | P1 | — | — |

### 7.1 Storage Choices

#### DD-BRAND-001 — Preferences belong to the browser

Storing a per-user theme in the database was the obvious relational answer and the wrong
product answer: it writes rows on every toggle, cannot serve logged-out visitors, and forces
a query onto every page for a value that changes with the hour. Cookies (server-readable,
middleware-friendly) plus localStorage (synchronous, client-instant) cover both consumers
with zero schema. Cleared cookies cost one re-click — the accepted price of never migrating
a preferences table again.

#### DD-BRAND-002 — Two sources, one DTO

The collision that motivated this split — brand name claimed by both settings and `AppInfo`
— taught that static product metadata and admin-editable identity have different owners and
different lifecycles. Keeping `AppInfo` canonical for the static half while the database
owns the editable half gives each writer exactly one place to write, and `resolve()`
shields every reader from caring which half answered.

#### DD-BRAND-003 — Uploads that refuse to wait

Bundling a 900 KB logo into the full-page save turns an upload failure into a form failure:
the admin loses nothing technically but must reconstruct which of eighteen fields carried
the error. Immediate upload isolates the failure to the file field while the rest of the
form sits pristine, and the orphan risk — upload without eventual save — costs a small
local file the removal action already knows how to clean.

### 7.2 Extension and Convergence

#### DD-BRAND-004 — A textarea instead of a theming framework

The custom-CSS requirement could have grown into variables with validation schemas, scoped
slots, or a build-time pipeline — each a project of its own. A single sandboxed string,
guarded by role and scan, satisfied the actual request (one school's login card) in an
afternoon. The sandbox is deliberately narrow: placement after the theme stylesheet gives
the CSS full override power within styling, while the scan keeps it from escaping into
script execution.

#### DD-BRAND-005 — The setting outranks the constant

Hardcoding the default locale in PHP would make the admin's language choice advisory rather
than authoritative — fresh browsers would render the constant while the setting claimed
otherwise. Putting the stored `default_locale` above config and constant in the chain makes
the admin the actual owner of the school's first impression, with the cookie above it
preserving personal choice. Two sources, strict priority, one whitelist: the shape that
keeps "which language will a visitor see" answerable in one glance.

#### DD-BRAND-006 — One theming stack standing

Three coexisting theme implementations meant three places where dark mode could break and
three accessibility stories to maintain. Consolidating on the TallstackUI switch deleted the
custom Livewire component and the DaisyUI/Alpine fallbacks outright, leaving `data-theme`
plus `.dark` as the single contract the applier honors. Migration completed in 0.15.0; the
decision stays recorded so nobody reintroduces a parallel switcher "just for this page."

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|----------------|
| Logo upload to visible preview | < 3s | browser timing on settings page |
| Preset apply to UI repaint | < 500ms | browser timing |
| Theme toggle to repaint | < 100ms, no server roundtrip | client timing |
| Locale switch to applied render | < 1s (next load) | navigation timing |
| Missing `en`/`id` key pairs | 0 | locale consistency scan |
| Unsafe stored uploads | 0 | server-side validation coverage |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|------------------|
| [settings-infrastructure.md](YB22J-settings-infrastructure.md) (YB22J) | Typed store, `brand.*` / `theme.*` / `locale.*` keys, observer invalidation |

### Build Guide

With this spec implemented, schools render under their own identity, users toggle appearance
and language per browser, and every downstream spec (authentication errors, dashboards,
certificates) inherits localized, branded rendering for free.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [authentication.md](YB7RG-authentication.md) | Login errors render in the resolved locale; layout uses brand identity |
| 2 | [layout-and-ui-system.md](8XMYS-layout-and-ui-system.md) | Theme variables and brand DTO feed the shared layout |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume cookie-cleared browsers falling back to the school default is acceptable recovery; no server-side preference backup is planned | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Phase 2 Configuration specs and build order
- [Settings infrastructure](YB22J-settings-infrastructure.md) — the store, helpers, and observer this spec consumes
- [School profile](81SMS-school-profile.md) — sibling consumer of the settings store
- [ADR: Self-hosted single-tenant](../adr/adr-self-hosted-single-tenant.md) — zero-external-services default
- [ADR: Performance optimization](../adr/adr-performance-optimization.md) — cache TTL and tier discipline
- [ADR: Gradual migration](../adr/adr-gradual-migration.md) — validation-sharing migration path
