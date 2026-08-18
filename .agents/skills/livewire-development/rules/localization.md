# Localization — Every User-Facing String Through `__()`

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

The application is bilingual (Indonesian SMA/SMK domain); every user-facing string in a component or
Blade view must be resolved through `__()` with keys present in **both** `lang/en/` and `lang/id/`.
Hardcoded text is a defect (D3): it renders one language to everyone and escapes translation review.
This asset defines the keying patterns, the string categories, and the confirmation-dialog wiring.
Full rules: `docs/conventions.md` §14 and `docs/architecture/modular-pattern.md` §23.

---

## Every User-Facing String Uses `__()`

**What it enforces:** No hardcoded user-visible text exists in a component or Blade view. Labels,
headings, buttons, modal titles, table headers, flash messages, and validation messages all resolve
via `__()`.

**Why it matters:** A hardcoded string is a one-language string. The Indonesian users receive English
(when the code says English) or mixed, and the translation files — the single reviewable source —
never see the string at all. Translation audits, `scan_conventions.py` (D3), and future locale
additions all operate on `__()` keys; hardcoded text is invisible to all of them.

**How to apply:** Compose every string as a key reference. For flash messages emitted by Actions or
components: `__('{module}.{entity}.{action}_success')` — never a hardcoded string in the `flash()`
call. Status labels: call `LabelEnum::label()` (which invokes `__()` internally) rather than
translating in the view, so the enum owns the label source. Modal titles, button labels, and table
headers all go through `__()`.

**Pitfalls to avoid:**

- `flash()->success('Saved successfully')` — must be `__('enrollment.register_success')`.
- `{{ $internship->status }}` in a view to show a status — must be `{{ $internship->status->label() }}`.
- A component `messages()`/`rules` message that returns raw English instead of `__()`.

**Verification:** `scan_conventions.py` (D3) is clean; a grep for user-visible literals finds none
outside language files.

---

## Keying Patterns and Key Placement

**What it enforces:** Keys follow scope patterns, and each key exists in both `lang/en/` and
`lang/id/` (module and submodule files colocated in the same directory, extending the module file).

**Why it matters:** A consistent key namespace makes keys predictable to invent and searchable to
locate. Requiring the pair (en + id) preserves the bilingual contract; a key added to one file only
(very common when writing the Action flash first) produces a raw-key break for the other locale.

**How to apply:**

| Scope           | Pattern           | Example                     |
| --------------- | ----------------- | --------------------------- |
| Module-level    | `{module}.key`    | `__('enrollment.register')` |
| Submodule-level | `{submodule}.key` | `__('internship.create_success')` |
| Shared          | `common.key`      | `__('common.actions.save')` |

Add each key to `lang/en/{module}.php` and `lang/id/{module}.php` in the same edit — never one
without the other.

**Pitfalls to avoid:**

- A key invented per-concern on the fly (`__('save')` for several modules) — collides and loses
  context; use `common.*` deliberately.
- Adding the en key, deferring the id key to "later".

**Verification:** Every `__()` key in the component/view resolves in both `lang/en/` and `lang/id/`
(tinker `echo __('{key}')` in both locales); the key appears once each.

---

## Confirmation Dialogs Route Through the Shared Confirm Component

**What it enforces:** Destructive confirmations use the shared confirm component with localized
title/message/confirm/cancel texts.

**Why it matters:** A consistent confirmation dialog preserves the project's interaction pattern and
its accessibility behavior (focus management, live announcements from `rules/accessibility.md`). The
shared component also centralizes the two-step destructive confirmation that ad-hoc `window.confirm`
or a bare `wire:confirm` does not provide consistently.

**How to apply:**

```blade
<x-core::ui.confirm
    :title="__('internship.confirm_delete_title')"
    :message="__('internship.confirm_delete_message')"
    :confirmText="__('common.actions.delete')"
    :cancelText="__('common.actions.cancel')"
/>
```

All four strings are localized keys present in both language files.

**Pitfalls to avoid:**

- Replacing with `confirm('Delete?')` inlined in the browser — no localization, no shared behavior.
- Localizing some strings but not others in the same dialog.

**Verification:** The dialog is rendered via the shared component; every string literal is a `__()`
key present in both locale files.

---

## References

| Topic                       | Asset                                       |
| --------------------------- | ------------------------------------------- |
| Localization convention     | `docs/conventions.md` §14                   |
| Modular localization        | `docs/architecture/modular-pattern.md` §23  |
| Translation pairing         | `docs/conventions.md` §Localization         |
| Localization & docs rule    | `.agents/skills/feature-building/rules/localization-and-docs.md` |