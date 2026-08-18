# Accessibility — WCAG 2.1 Level AA

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

All Livewire components meet **WCAG 2.1 Level AA**. Accessibility is a release criterion, not a
polish item — keyboard users, screen-reader users, and motion-limited users are part of the target
population (vocational school staff and students). Most screens are Livewire-driven partial updates,
which means the accessibility failures are Livewire-specific: unstaged focus, invisible dynamic
content, and unlabeled icon-only controls. This asset details the rules that prevent those failures.
Full rules: `docs/architecture/livewire-pattern.md` §13 and `docs/foundation/ui-ux.md` §6.

---

## Focus Management

**What it enforces:** Focus moves correctly on every state transition:

- **Modal open:** focus moves to the first focusable element inside the modal (maryUI default).
- **Modal close:** focus returns to the trigger element — implement via Alpine
  `x-on:close.window="$focus(target)"`.
- **`wire:navigate` transitions:** focus resets to the `<h1>` or the first interactive element after
  the page transition.

**Why it matters:** Keyboard users navigate by focus position, not by mouse. When a modal opens and
focus stays on the background page, a keyboard user continues "clicking" invisible background
controls; when a modal closes and focus is dropped, the next Tab starts from the browser chrome.
Breaking the focus loop is one of the most disorienting WCAG failures and is invisible to mouse-only
testers.

**How to apply:** Verify modal open/close hooks in every component that renders a modal. For
`wire:navigate`, structure pages with an `<h1>` as the first focusable region and confirm focus
lands there after transition. Test with Tab-only navigation on every modal and page transition.

**Pitfalls to avoid:**

- A modal that opens without moving focus (maryUI default overridden).
- A modal whose close button tab-focuses into the next background element.
- `wire:navigate` pages that leave focus on the old scroll position.

**Verification:** Keyboard walk of modal open → interact → close → next Tab lands correct; after
`wire:navigate`, focus is on the `<h1>`.

---

## Dynamic Content — Announced to Screen Readers

**What it enforces:** Livewire partial DOM updates are invisible to screen readers unless wrapped in
`aria-live="polite"` containers. Flash messages verify `aria-live` is present on the flash container.
Loading states use `aria-busy="true"` and `role="status"`.

**Why it matters:** Livewire re-renders change the DOM without a page navigation. Screen readers do
not announce silent content swaps, so a user reading with a screen reader never learns the validation
message that just appeared or the row the sort just added. `aria-live="polite"` is the dedicated
mechanism for exactly this.

**How to apply:**

- Wrap live-updated regions (flash messages, inline validation summaries, dynamic search results) in
  `aria-live="polite"`.
- Flash containers must carry the region attribute — verify, don't assume maryUI adds it.
- While rendering substitutes/loading, set `aria-busy="true"` on the region and `role="status"` on
  the loading indicator.

**Pitfalls to avoid:**

- A flash message with no live region — the most frequently missed announcement.
- `aria-live="assertive"` for routine status (only used for time-critical errors/status).
- Loading spinners with no role/announcement — a screen reader user sees nothing change.

**Verification:** Every flash and every dynamic update region carries `aria-live="polite"`; loading
regions use `aria-busy`/`role="status"`.

---

## Form Accessibility

**What it enforces:** Every `<x-mary-input>` has a `label` prop (renders a `<label>` with a proper
`for`). Requiredness is expressed with the `required` prop (HTML `required` attribute) — not just a
visual indicator. Validation errors from `$this->validate()` are announced via maryUI's `aria-live`
regions. After failed validation, focus moves to the first invalid field or error summary.

**Why it matters:** Unlabeled inputs are unnamed controls — a screen reader announces "edit text"
with no meaning (`label` association is the accessible name). A visual asterisk without the `required`
attribute is invisible to assistive tech and to form-submission semantics. Validation errors that
appear without focus movement or announcement leave the user glancing at a changed field they cannot
find.

**How to apply:** Confirm each `x-mary-input` carries `label` (and `required` where applicable) in
the blade. After `$this->validate()` fails, move focus to the first invalid field or the error
summary (Alpine or Livewire hook). Trust but verify maryUI's live regions actually announce the
errors on the rendered page.

**Pitfalls to avoid:**

- A placeholder-only input acting as the name — placeholders are not accessible names.
- A red border alone signaling an error — visual-only, screen readers get nothing.
- Errors rendered in a static `<div>` instead of an announced live region.

**Verification:** Every input resolves to a label via `for`/`id`; `required` inputs carry the HTML
attribute; failed-validate moves focus to the first invalid control.

---

## Table Accessibility

**What it enforces:** `x-mary-table` headers use `scope` attributes by default — verify they are not
overridden. Sortable headers include `aria-sort`. The bulk-selection header checkbox carries
`aria-label="Select all rows"`.

**Why it matters:** Table semantics are how screen readers announce rows and columns. A header
without `scope` leaves cells unassociated; a sortable header without `aria-sort` hides the current
sort direction; an unlabeled bulk checkbox announces a nameless toggle. All three are silent failures
that only affect assistive-tech users.

**How to apply:** When using a custom/overridden header template in `x-mary-table`, re-add the
`scope` attributes explicitly. Wire `aria-sort` to the current sort state. Give the select-all header
checkbox its `aria-label`.

**Pitfalls to avoid:**

- Overriding maryUI's header template and dropping `scope` in the process.
- A custom sort control (dropdown/button) without announcing the current direction.
- A select-all checkbox without a label, indistinguishable from a column-toggle checkbox.

**Verification:** Headers declare `scope`; sortable columns set `aria-sort`; select-all carries its
`aria-label`.

---

## Icon-Only Elements

**What it enforces:** Any button or link that shows only an icon MUST include an `aria-label` naming
the action.

**Why it matters:** An icon button without text is a graphic with no name to a screen reader and an
ambiguous target to a sighted-but-distracted user. The accessible name, not the icon, is what a
screen reader announces and what a good auto-test can assert.

**How to apply:**

```blade
<x-mary-button icon="o-trash" wire:click="delete('{{ $id }}')" aria-label="{{ __('common.delete') }}" />
```

Localize the label via `__()` (see `rules/localization.md`).

**Pitfalls to avoid:**

- An editable trash/home/gear icon with no label.
- A `title` attribute substituted for `aria-label` — tooltips are not accessible names.

**Verification:** Every icon-only interactive element carries a non-empty, localized `aria-label`.

---

## References

| Topic                    | Asset                                        |
| ------------------------ | -------------------------------------------- |
| Accessibility full rules | `docs/architecture/livewire-pattern.md` §13  |
| UI/UX foundations        | `docs/foundation/ui-ux.md` §6                |
| maryUI component docs    | maryUI docs (via `search-docs`)              |