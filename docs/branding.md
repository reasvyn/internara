# Branding and Theming
> Last updated: 2026-05-23
> Changes: fix: complete system initialization overhaul — security, middleware, recovery, form objects, docs


## How Theming Works

Branding is dynamic — colors, logos, and site metadata are configurable at
runtime through the admin panel, without editing code or running build steps.
The system resolves values through a fallback chain: runtime overrides take
highest priority, followed by database settings (cached forever), then
configuration file defaults, then hardcoded defaults.

The `brand()` helper function is the primary API for accessing brand values.
It returns the institution name, logo URL, favicon URL, site title, brand
colors (primary, secondary, accent, base), and metadata (version, author,
license) from `composer.json`.

## Color System

The application defines four brand colors: primary (main actions, links, active
navigation), secondary (supporting elements), accent (highlights), and base
(page background). When the admin sets these colors, the system automatically
computes additional values needed by the theme:

- Contrast text colors (for readability on each brand color background)
- Base surface hierarchy (base-100 for cards, base-200 for sidebar, base-300
  for page background)
- Dark mode equivalents (brand colors lightened, base colors darkened)

Six preset palettes are available (emerald, sky, violet, rose, ocean, slate),
each defining all four brand colors. Selecting a preset fills the color
pickers. Manual color changes clear the preset selection. The system
automatically detects when manual colors match a preset and re-selects it.

## Dynamic Configuration vs Compile-Time CSS

Brand colors are not hardcoded in CSS. Instead, the `Theme::cssVariables()`
method generates CSS custom properties for both light and dark themes, and
these are injected as an inline `<style>` block in the HTML shell on every
page load. This means a color change in the admin panel is reflected on the
next page refresh — no CSS rebuild, no deployment, no cache clear.

Only the color values are dynamic. Everything else — component styles, layout
grid, spacing, typography — is compiled at build time into the standard CSS
bundle. This separation keeps the CSS bundle cacheable while allowing
brand-specific colors to change instantly.

The inline style block is small (approximately 50 CSS custom properties) and
has a negligible impact on page size.

## Logo Management

The brand logo and favicon are uploaded through the admin settings panel.
Uploaded files are stored in `public/brand/` and the URL is saved as a
setting value. The `brand('logo')` helper returns this URL, falling back to
a default file if no logo has been uploaded.

Logos support light/dark mode display through an `invert` CSS filter. When
applied, the image is rendered as a solid white silhouette. This is the
default behavior for sidebar and header contexts where the background may be
dark.

A separate school logo is managed through the media library and displayed on
the school profile page. This is distinct from the system-wide brand logo.

## Font Strategy

The primary typeface is Instrument Sans, a clean sans-serif font designed for
UI text. It is self-hosted as WOFF2 files in three weights: 400 (regular),
500 (medium), and 600 (semibold). Self-hosting ensures the font loads
reliably regardless of third-party availability and eliminates external
requests.

The `font-display: swap` strategy ensures text remains visible during font
loading — the browser renders with a fallback font first, then swaps to
Instrument Sans when it finishes loading. This prevents invisible text
(FOIT).

Heavy weight (900) is used extensively for headings and brand text. Tight
letter-spacing creates compact headlines.

Email templates use Arial for maximum email client compatibility. PDF
templates use Courier New for recovery codes and system fonts for body text.

## Where to Find It

The `brand()` and `app_info()` helper functions are defined in
`app/Support/helpers.php`. The `AppMetadata` class that resolves brand values
is at `app/Settings/Support/AppMetadata.php`. The `Theme` class for
color computation is at `app/Core/Support/Theme.php`. The `Color`
utility class is at `app/Settings/Support/Color.php`. The admin
branding form is the `SystemSetting` Livewire component at
`app/Settings/Livewire/SystemSetting.php`. Color presets and defaults
are in `config/settings.php`. Font files are in `resources/fonts/`. The CSS
entry point with theme plugins is `resources/css/app.css`.
