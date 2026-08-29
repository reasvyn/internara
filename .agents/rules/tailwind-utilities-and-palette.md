# Tailwind Utilities & Palette — Tailwind CSS v4 + Self-Hosted Theme

Tailwind CSS v4 is the utility foundation. General UI concerns (Blade presentation, view structure, layout, component library, accessibility, localization) now live in `ui-development`.

---

## Intent

Use Tailwind utilities via the CSS-first `@theme` + `@layer` pipeline (`resources/css/app.css`), consume the self-hosted semantic palette (`--color-primary`, `--color-success`, etc.), and avoid custom CSS when utilities or TallstackUI cover the need.

## What it enforces

- **CSS-first config:** No `tailwind.config.js`. Theme tokens are defined via `@theme` in `resources/css/app.css` (`--color-base-*`, `--color-primary` etc.) with `[data-theme='dark']` overrides. Check that file before adding any color or spacing token.
- **Semantic palette only:** Use `bg-primary`, `text-success`, `border-warning`, `bg-info/10` etc. derived from `@theme`. Never use arbitrary `bg-blue-500`, `text-red-500`, `bg-[#123456]` — they bypass the self-hosted palette and fail contrast/theming.
- **No custom CSS unless necessary:** Prefer Tailwind utilities and TallstackUI `x-ts-*` components. Legacy DaisyUI shims (`.btn`, `.badge`, `.card` etc.) are in `@layer components` as transitional shims — do not extend them. If a design cannot be expressed with utilities/TallstackUI, document why in the PR.
- **No inline `style=""`:** Use utilities (`mt-4`, `flex`, `gap-2`) instead of `style="margin-top: 12px"`.
- **Dark mode via Tailwind `dark:` variant:** Colors must be theme-aware. Do not hardcode `bg-white` without a `dark:` counterpart. The dual signal is `data-theme` + `.dark` class (see `ui-development` for layout/dark-mode wiring).

## Why it matters

The self-hosted palette is changed at runtime via `Theme::cssVariables()` without recompilation. Hardcoded colors break brand theming and dark mode; custom CSS duplicates framework behavior and blocks upgrades.

## How to apply

- Check `resources/css/app.css` for existing tokens before adding a new color/spacing value.
- Use `bg-[var(--color-primary)]` only when a semantic utility does not exist; otherwise use `bg-primary`.
- Verify `npm run build` clean and `npx prettier --check` after any CSS change.

## Pitfalls to avoid

- `class="text-red-500"` for error — use `text-error` + label.
- `style="margin-top: 12px"` — use `mt-3`.
- Adding a color to `tailwind.config.js` — this project has no such file; add to `@theme` instead.
- Extending `@layer components` shims for new designs — use utilities or TallstackUI.

## Verification

- `grep -R "bg-\[#\|text-\[#\|bg-blue-\|text-red-" resources/views --include="*.blade.php"` returns no hits (no arbitrary colors).
- `grep -R "style=\"" resources/views --include="*.blade.php"` returns no hits (no inline styles).
- `npm run build` clean; `npx prettier --check` clean (non-PHP only).
- General UI rules (Blade, view structure, layout, a11y, i18n) are checked via `ui-development`, not here.
