# Layout, Responsiveness & Dark Mode — Structure and Theming

Layout, responsiveness, and theming are the visible skeleton of every blade view: the shared
`core::layouts.*` shells (drawer sidebar + navbar header) for structure, mobile-first breakpoints for
responsiveness, and the `data-theme` attribute + `.dark` class + CSS variables (`@theme` palette) for
brand/dark theming. Each piece falls apart in a characteristic way when skipped.

---

## Intent

Use TallstackUI components inside the shared `core::layouts.sidebar` / `core::layouts.header` shells
for navigation chrome — never hand-rolled per-page chrome. Responsive design is
mandatory — mobile-first with `sm:`/`md:`/`lg:` breakpoints, tested at mobile, tablet, desktop. Dark
mode must work without visual breakage; brand colors come from the self-hosted semantic palette
(`@theme` variables in `resources/css/app.css`, overridable by the Settings module), with the theme
toggle via TallstackUI `<x-theme-switch>` (mirrors `data-theme` cookie + `.dark` class).

## Rationale — What Fails Without It

- **No drawer/navbar** — each page hand-rolls its chrome; navigation becomes inconsistent and
  session/menu state diverges per module.
- **Desktop-only classes** — a page built only with `flex-row` and fixed widths clamps on phones; the
  app's target audience (SMA/SMK students, teachers, supervisors) is heavily mobile.
- **Dark mode ignored** — light-only backgrounds with hardcoded whites (`bg-white`) produce readable
  pages in light and unreadable flash in dark. The requirement is "dark works without breakage", and
  that only holds when colors come from theme-aware tokens.
- **Brand colors hardcoded** — the Settings module owns brand colors as CSS variables; bypassing them
  breaks restyling and dark-mode swaps.
- **No toggle handling** — without Alpine state the user can't persist preference; an incomplete
  toggle that only flips a class but not persist is half a feature.

## How to Apply

### Layout

- Sidebar navigation → shared `core::layouts.sidebar` shell (drawer pattern, `lg:drawer-open`).
- Top navigation → `core::layouts.header` (sticky navbar with `core::ui.navbar-actions`).
- Content width → `max-w-7xl mx-auto` container pattern.

```blade
<div class="drawer lg:drawer-open">
    <input id="app-drawer" type="checkbox" class="drawer-toggle" />
    <div class="drawer-content flex flex-col">
        <div class="navbar">…</div>
        <main class="max-w-7xl mx-auto p-4">…</main>
    </div>
    <div class="drawer-side">
        <label for="app-drawer" aria-label="{{ __('common.close_sidebar') }}" class="drawer-overlay"></label>
        <aside>…sidebar…</aside>
    </div>
</div>
```

### Responsive — mobile-first

- Build mobile-first: base classes for mobile, then `sm:`/`md:`/`lg:` for larger viewports.
- Test at mobile, tablet, desktop before shipping (mandatory per Styling Principles).
- No horizontal scrolling at 320px viewport (see Accessibility rule for reflow specifics).

### Dark mode

- Dark mode works via the `.dark` class (Tailwind/TallstackUI `dark:` variant) plus the
  `data-theme` attribute (semantic palette variables); `applyTheme()` in `resources/js/app.js`
  keeps both in sync with the persisted preference.
- Implement the theme toggle via TallstackUI `<x-theme-switch>` (wrapped by `core::ui.theme-switch`) —
  do not hand-roll a new toggle or use `data-theme` selectors in new code.
- Brand colors are CSS variables (`@theme` palette, Settings-overridable) — consume them, never re-hardcode:

```blade
<html data-theme="{{ $theme }}" class="{{ $isDark ? 'dark' : '' }}">
    {{-- body uses dark: variants + semantic tokens --}}
</html>
```

### Tailwind v4 specifics

- **CSS-first configuration** — no `tailwind.config.js`; check `resources/css/`.
- Uses the `@theme` directive for custom values.
- Uses `@import` for layers instead of `@layer`.
- Check `resources/css/app.css` for the project-specific theme setup before adding any global style.

```css
/* resources/css/app.css */
@import "tailwindcss";
@theme {
    --color-primary: var(--brand-primary, #4f46e5);
    --color-base-100: var(--brand-base-100, #ffffff);
}
```

## Anti-Patterns & Pitfalls

- Hardcoded `bg-white`/`text-black` for major surfaces — breaks dark mode; use theme tokens.
- Desktop-only layout (`hidden md:block` for everything meaningful) — mobile users get an empty page.
- Writing brand color hex in Blade/CSS instead of the Settings module's variables.
- A "dark toggle" that changes the attribute but reloads the page losing state — persistence via
  `applyTheme()` (localStorage) is part of the feature.
- Mixing `@layer` (Tailwind v3 syntax) into `app.css` — v4 uses `@import`/`@theme`.

## Verification

- Drawer/navbar used for chrome; container `max-w-7xl mx-auto`.
- Layout renders at mobile (320px), tablet, desktop without overlap or horizontal scroll.
- Dark theme renders with no unreadable contrast (spot-check on key pages).
- `npx prettier --check` + `npm run build` clean; `docs/guides/ui-ux/design-system.md` / `branding.md` match
  the implemented theme tokens.
