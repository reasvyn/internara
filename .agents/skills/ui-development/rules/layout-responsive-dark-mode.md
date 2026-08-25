# Layout, Responsiveness & Dark Mode — Structure and Theming

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

Layout, responsiveness, and theming are the visible skeleton of every blade view: DaisyUI's drawer
and navbar for structure, mobile-first breakpoints for responsiveness, and `data-theme` + CSS
variables for brand/dark theming. Each piece falls apart in a characteristic way when skipped.

---

## Intent

Use DaisyUI's `drawer` for sidebar navigation and `navbar` for top navigation. Responsive design is
mandatory — mobile-first with `sm:`/`md:`/`lg:` breakpoints, tested at mobile, tablet, desktop. Dark
mode must work without visual breakage; brand colors come from CSS variables defined in the Settings
module, with a theme toggle implemented via Alpine.js + Livewire.

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

- Sidebar navigation → DaisyUI `drawer`.
- Top navigation → DaisyUI `navbar`.
- Content width → `max-w-7xl mx-auto` container pattern.

```blade
<div class="drawer lg:drawer-open">
    <input id="app-drawer" type="checkbox" class="drawer-toggle" />
    <div class="drawer-content flex flex-col">
        <div class="navbar">…</div>
        <main class="max-w-7xl mx-auto p-4">…</main>
    </div>
    <div class="drawer-side">
        <label for="app-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
        <aside>…sidebar…</aside>
    </div>
</div>
```

### Responsive — mobile-first

- Build mobile-first: base classes for mobile, then `sm:`/`md:`/`lg:` for larger viewports.
- Test at mobile, tablet, desktop before shipping (mandatory per Styling Principles).
- No horizontal scrolling at 320px viewport (see Accessibility rule for reflow specifics).

### Dark mode

- DaisyUI supports dark mode via the `data-theme="dark"` attribute.
- Implement the theme toggle via Alpine.js + Livewire (state + persistence).
- Brand colors are CSS variables defined in the Settings module — consume them, never re-hardcode:

```blade
<div :data-theme="theme" class="min-h-screen">
    <button @click="theme = theme === 'dark' ? '' : 'dark'">
        {{ theme === 'dark' ? __('ui.dark') : __('ui.light') }}
    </button>
</div>
```

### Tailwind v4 specifics

- **CSS-first configuration** — no `tailwind.config.js`; check `resources/css/`.
- Uses the `@theme` directive for custom values.
- Uses `@import` for layers instead of `@layer`.
- Check `resources/css/app.css` for the project-specific theme setup before adding any global style.

```css
/* resources/css/app.css */
@import "tailwindcss";
@import "daisyui";
@theme {
    --color-brand: var(--brand-color);
}
```

## Anti-Patterns & Pitfalls

- Hardcoded `bg-white`/`text-black` for major surfaces — breaks dark mode; use theme tokens.
- Desktop-only layout (`hidden md:block` for everything meaningful) — mobile users get an empty page.
- Writing brand color hex in Blade/CSS instead of the Settings module's variables.
- A "dark toggle" that changes the attribute but reloads the page losing state — persistence via
  Livewire/Alpine storage is part of the feature.
- Mixing `@layer` (Tailwind v3 syntax) into `app.css` — v4 uses `@import`/`@theme`.

## Verification

- Drawer/navbar used for chrome; container `max-w-7xl mx-auto`.
- Layout renders at mobile (320px), tablet, desktop without overlap or horizontal scroll.
- Dark theme renders with no unreadable contrast (spot-check on key pages).
- `npx prettier --check` + `npm run build` clean; `docs/foundation/ui-ux.md` / `branding.md` match
  the implemented theme tokens.