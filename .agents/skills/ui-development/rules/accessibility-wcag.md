# Accessibility — WCAG 2.1 AA for Every Styled Component

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

Accessibility is non-negotiable: every styled component must meet WCAG 2.1 Level AA. This covers
color & contrast, focus indicators, keyboard navigation, responsive reflow, and icon labeling.
See `docs/architecture/modular-pattern.md` §22 and `docs/foundation/ui-ux.md` §6 for the full
spec.

---

## Intent

Every UI component ships WCAG 2.1 AA compliant: DaisyUI theme colors that are prevalidated for
contrast, visible focus indicators on every interactive element, full keyboard navigation, no
horizontal scrolling at 320px, and `aria-label` on every icon-only control.

## Rationale — What Fails Without It

- **Contrast below 4.5:1** makes normal text unreadable for low-vision users; the school +
  supervisor audience includes everyday users, and WCAG AA is the project's committed bar
  (documented in `docs/foundation/ui-ux.md`). Arbitrary utilities like `text-red-500` routinely fail.
- **Suppressed focus rings** (`outline-none` with no replacement) make keyboard users lose the cursor
  — Tab-navigation becomes guesswork.
- **Mice-only interactive elements** (a dropdown that opens on hover only) are unreachable for
  keyboard users; WCAG 2.1.1 requires all functionality from a keyboard.
- **Horizontal scroll at 320px** (WCAG 1.4.10) breaks the reflow guarantee; tables that don't reflow
  force zoom-and-pan.
- **Icon-only buttons without `aria-label`** are announced as unlabeled noise to screen readers; the
  user hears "button" and nothing else.

## How to Apply

### Color & Contrast

- Use DaisyUI theme colors — prevalidated for contrast ratios.
- Minimum contrast: **4.5:1** for normal text, **3:1** for large text (≥18pt or ≥14pt bold).
- Never use arbitrary Tailwind color utilities (`text-red-500`, `bg-blue-200`) that may fail
  contrast checks — prefer DaisyUI semantic colors (`text-error`, `bg-info/10`).
- Status indicators must include text labels alongside color (e.g. `badge-success` + "Active", not
  just a green badge) — color is never the sole indicator (WCAG 1.4.1).

```blade
<span class="badge badge-success">Active</span>  {{-- icon + text --}}
```

### Focus Indicators

- Never suppress focus rings with `outline-none` without a visible replacement.
- DaisyUI `focus:ring` is the default — preserve it on all interactive elements.
- Custom interactive elements (Alpine.js dropdowns, custom buttons) must include
  `focus:ring focus:ring-primary`.

```blade
<button class="btn btn-ghost focus:ring focus:ring-primary">…</button>
```

### Keyboard Navigation

- All interactive elements reachable via Tab.
- Dropdowns open on Enter/Space, close on Escape.
- Modals trap focus (DaisyUI default — verify it's not overridden).
- No positive `tabindex` values — follow natural DOM order.

### Responsive & Reflow

- No horizontal scrolling at 320px viewport width (WCAG 1.4.10).
- Tables reflow to card layout **or** provide horizontal scroll with visible indicators on mobile.
- Content must not be clipped or overlap at any breakpoint.

### Icon Accessibility

- Icon-only buttons must include `aria-label`:

```blade
<x-mary-button icon="o-trash" aria-label="{{ __('common.delete') }}" />
```

- Icons paired with text should NOT duplicate the text in `alt` attributes.

## Anti-Patterns & Pitfalls

- `class="text-red-500"` for an error state — fails contrast + non-theme color; use
  `text-error` + a label.
- `outline-none` on an input/button with no `focus:ring` replacement.
- Relying on color alone for success/error/status — a coded hint or text label is required.
- A custom dropdown wired only to `mouseenter` — keyboard-unreachable.
- An icon button without `aria-label` — screen-reader users hear "button".
- Hardcoding `alt=""` on a meaningful icon paired with text — the pairing already communicates; keep
  it empty.

## Verification

- Keyboard walkthrough: Tab reaches every control, Enter/Space open dropdowns, Escape closes, content
  stays focusable.
- Contrast spot-check on key pages (normal ≥4.5:1, large ≥3:1).
- 320px viewport check: no horizontal scroll; tables reflow or scroll with visible indicators.
- `npm run build` clean; the module view follows `docs/architecture/modular-pattern.md` §22.