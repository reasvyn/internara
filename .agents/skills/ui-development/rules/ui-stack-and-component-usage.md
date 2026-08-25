# UI Stack & Component Usage — Layer Roles and maryUI Primacy

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

The frontend is built from a four-layer stack (Tailwind CSS v4 + DaisyUI 5 + maryUI 2 + Alpine.js),
and components come from maryUI by default. Choosing the right layer for a job — and reusing the
framework's components instead of hand-rolling HTML/CSS — is what keeps the UI consistent, themed,
and accessible.

---

## Intent

Use maryUI components for consistency (table, modal, form, buttons, cards), use DaisyUI theme colors
(primary, secondary, accent), and avoid custom CSS whenever DaisyUI/maryUI can achieve the design.
Follow existing component patterns within the module.

## Rationale — What Fails Without It

- **Hand-rolled HTML components** drift from the design system: inconsistent spacing, no theme color
  binding, forgotten focus and aria states, and a second styling source of truth that `sync-docs`
  and module review can't inventory.
- **Arbitrary Tailwind color utilities** (`text-red-500`, `bg-blue-200`) bypass DaisyUI's
  theme-aware, contrast-prevalidated palette — a hardcoded red breaks when the brand theme changes
  and may fail the contrast gate (see Accessibility rule).
- **Custom CSS for what a component already does** duplicates framework behavior and makes every
  theme/upgrade harder; the design system retires custom CSS when the box's widgets cover the need.
- **Ignoring module-internal patterns** means one module uses `x-mary-table` with sorting and a
  neighbour uses a bespoke `<table>` — the module fingerprints diverge and newcomers have no template.

## How to Apply

### Know the stack — each layer has a job

| Layer               | Purpose                                                        |
| ------------------- | -------------------------------------------------------------- |
| **Tailwind CSS v4** | Utility-first CSS framework                                    |
| **DaisyUI 5**       | Tailwind component library (themed, accessible)                |
| **maryUI 2**        | Laravel-specific Livewire component library (built on DaisyUI) |
| **Alpine.js**       | Lightweight JavaScript interactivity (dropdowns, modals)       |

### Component usage table — the default lookup

| Need          | maryUI Component                                   |
| ------------- | -------------------------------------------------- |
| Tables        | `x-mary-table` (sorting, pagination, selection)    |
| Forms         | `x-mary-input`, `x-mary-select`, `x-mary-textarea` |
| Modals        | `x-mary-modal`                                     |
| Notifications | `x-mary-toast` (via flasher)                       |
| Buttons       | `x-mary-button`                                    |
| Cards         | `x-mary-card`                                      |
| Stats         | `x-mary-stat`                                      |
| Alerts        | `x-mary-alert`                                     |
| Tabs          | `x-mary-tabs`                                      |
| Choices       | `x-mary-choices` (multi-select)                    |

```blade
<x-mary-card title="{{ __('intern.list') }}">
    <x-mary-table :rows="$interns" with-pagination />
</x-mary-card>
```

### Styling principles

1. Prefer maryUI components over custom HTML for consistency.
2. Use DaisyUI theme colors (`primary`, `secondary`, `accent`, etc.) over arbitrary colors.
3. Do NOT write custom CSS unless DaisyUI/maryUI cannot achieve the design.
4. Follow existing component patterns in the same module — copy the sibling's contract shape, not a
   tutorial's.

## Anti-Patterns & Pitfalls

- `bg-red-500` or `text-gray-900` in Blade — arbitrary utility colors replacing the DaisyUI palette.
- A bespoke `<table class="...">` when `x-mary-table` with sorting/pagination fits — duplicated
  framework behavior.
- `style="margin-top: 12px"` inline styles — utilities exist for this; inline styles bypass the
  theme system (see the Verification Checklist's "No inline styles" item).
- Importing a CSS framework class globally to work around one component — one-off global patches
  leak across the app.

## Verification

- Every rendered need has a corresponding maryUI/DaisyUI component; custom CSS only where the box
  physically cannot express the design (documented).
- Colors in Blade come from DaisyUI semantic tokens, not raw hex/arbitrary utilities.
- `npx prettier --check` + `npm run build` clean after any CSS/Blade change.