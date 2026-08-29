# UI Stack & Component Usage — Layer Roles and TallstackUI Primacy

The frontend is built from a three-layer stack (Tailwind CSS v4 + TallstackUI v4 + self-hosted
semantic palette + Alpine.js), and components come from TallstackUI by default. Choosing the right
layer for a job — and reusing the framework's components instead of hand-rolling HTML/CSS — is what
keeps the UI consistent, themed, and accessible.

---

## Intent

Use TallstackUI `x-ts-*` components for consistency (table, modal, form, buttons, cards, toast via
Interactions), use the self-hosted semantic palette (`--color-primary`, `--color-base-100` etc. via
`@theme` in `resources/css/app.css`), and avoid custom CSS whenever TallstackUI can achieve the
design. Follow existing component patterns within the module. Legacy DaisyUI tokens (`btn`, `badge`
etc.) survive only as shims in `@layer components` until fully migrated to `x-ts-*` — do not
introduce new DaisyUI/maryUI usage.

## Rationale — What Fails Without It

- **Hand-rolled HTML components** drift from the design system: inconsistent spacing, no theme color
  binding, forgotten focus and aria states, and a second styling source of truth that `sync-docs`
  and module review can't inventory.
- **Arbitrary Tailwind color utilities** (`text-red-500`, `bg-blue-200`) bypass the self-hosted
  semantic palette — a hardcoded red breaks when the brand theme changes and may fail the contrast
  gate (see Accessibility rule).
- **Custom CSS for what a component already does** duplicates framework behavior and makes every
  theme/upgrade harder; the design system retires custom CSS when the box's widgets cover the need.
- **Ignoring module-internal patterns** means one module uses `x-ts-table` with sorting and a
  neighbour uses a bespoke `<table>` — the module fingerprints diverge and newcomers have no template.
- **Reintroducing DaisyUI/maryUI/PHPFlasher** (`x-mary-*`, `flash()->`, `@flasher_render`,
  `@plugin daisyui`) breaks the 0.15.0 removal contract — packages are deleted, shims are temporary.

## How to Apply

### Know the stack — each layer has a job

| Layer                    | Purpose                                                        |
| ------------------------ | -------------------------------------------------------------- |
| **Tailwind CSS v4**      | Utility-first CSS framework (`@theme`, `dark:` via `.dark`)    |
| **TallstackUI v4**       | TALL-stack Livewire component library (`x-ts-*`, Interactions) |
| **Self-hosted palette**  | Semantic color bridge (`--color-base-*`, `--color-primary` etc. via `@theme` + `@layer components` shims) |
| **Alpine.js**            | Lightweight JavaScript interactivity (dropdowns, modals)       |

### Component usage table — the default lookup

| Need          | TallstackUI Component (`x-ts-*`)                          |
| ------------- | --------------------------------------------------------- |
| Tables        | `x-ts-table` / `x-ts-table.*` (sorting, pagination, selection) |
| Forms         | `x-ts-input`, `x-ts-select`, `x-ts-textarea`, `x-ts-choices` |
| Modals/Dialogs| `x-ts-modal`, `x-ts-dialog`, `x-ts-slide`                 |
| Notifications | `x-ts-toast` (via `Interactions` → `toast()->success()->send()`) + `x-ts-alert` |
| Buttons       | `x-ts-button`                                             |
| Cards         | `x-ts-card`                                               |
| Stats         | `x-ts-stat` / metric cards                                |
| Alerts        | `x-ts-alert`                                              |
| Tabs          | `x-ts-tab` / `x-ts-tabs`                                  |
| Choices       | `x-ts-choices` (multi-select)                             |
| Icons         | `x-ts-icon` (Heroicons)                                   |
| Layout        | `x-core::ui.page-header`, `x-core::ui.record-manager` (wraps `x-ts-*`) |

```blade
<x-ts-card title="{{ __('intern.list') }}">
    <x-ts-table :rows="$interns" with-pagination />
</x-ts-card>
```

Toast feedback (replaces removed `flash()->` / PHPFlasher / maryUI `$this->success()`):

```php
// in Livewire component (InteractsWithToast)
$this->toast()->success(__('crud.created'))->send();
$this->toast()->error(__('crud.failed'))->send();
```
```blade
{{-- in core::layouts.base --}}
<x-ts-toast />
```

### Styling principles

1. Prefer TallstackUI `x-ts-*` components over custom HTML for consistency.
2. Use semantic palette tokens (`primary`, `secondary`, `accent`, `info/success/warning/error`, `base-100/200/300`) over arbitrary colors.
3. Do NOT write custom CSS unless TallstackUI cannot achieve the design.
4. Do NOT reintroduce `x-mary-*`, `flash()->`, `@flasher_render`, `@plugin daisyui`, or raw DaisyUI class contracts as design primitives — legacy tokens survive only as temporary shims.
5. Follow existing component patterns in the same module — copy the sibling's contract shape, not a tutorial's.

## Anti-Patterns & Pitfalls

- `bg-red-500` or `text-gray-900` in Blade — arbitrary utility colors replacing the semantic palette.
- A bespoke `<table class="...">` when `x-ts-table` with sorting/pagination fits — duplicated framework behavior.
- `style="margin-top: 12px"` inline styles — utilities exist for this; inline styles bypass the theme system.
- Importing a CSS framework class globally to work around one component — one-off global patches leak across the app.
- `x-mary-*`, `flash()->`, `@flasher_render`, `@plugin daisyui` — removed in 0.15.0 (FB792 FR-TS5); use `x-ts-*` + `toast()` instead.
- Hardcoding legacy DaisyUI classes (`btn`, `badge`, `table-sm`) as new design — they are shimmed only for backward compat; new code must use `x-ts-*`.

## Verification

- Every rendered need has a corresponding TallstackUI component; custom CSS only where the box physically cannot express the design (documented).
- Colors in Blade come from semantic palette tokens, not raw hex/arbitrary utilities.
- No `x-mary-*`, `flash()->`, `@flasher_render`, or `@plugin daisyui` remains (FB792 FR-TS5 gate).
- `npx prettier --check` + `npm run build` clean after any CSS/Blade change.
