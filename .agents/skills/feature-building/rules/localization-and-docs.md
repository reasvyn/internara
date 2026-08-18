# Localization & Documentation — Both Languages, Both Doc Tiers

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

Two non-functional deliverables are part of every feature: user-facing strings in both `lang/en/`
and `lang/id/`, and module documentation updated alongside the code. These are not post-build
cleanup — they are acceptance criteria of the feature itself.

---

## New User-Facing String — Must Exist in Both `lang/en/` and `lang/id/`

**What it enforces:** Every user-facing string introduced by the feature exists as a `__()` key in
**both** `lang/en/{module}.php` and `lang/id/{module}.php` (submodule files colocated in the same
directory). No hardcoded text anywhere in views, components, or Actions.

**Why it matters:** The application is bilingual (Indonesian SMA/SMK domain); a string that exists in
only one language renders raw key names for the other half of the users and breaks the localization
convention (D3). Hardcoded strings also defeat translation-file review and drift from the language
pair over time. Submodule files live in the same directory as their module file so the pairing is
obvious and the doc-link/arch scans can verify them together.

**How to apply:**

1. When a user-facing string is needed (button label, flash message, modal title, validation
   message), invent the key per the scope pattern (`{module}.{entity}.{action}_success`, or
   `common.*` for shared).
2. Add the key to `lang/en/{module}.php` with the English text and to `lang/id/{module}.php` with
   the Indonesian translation.
3. Reference it everywhere with `__('{module}.{key}')` — including in Actions that return
   `ActionResponse` messages and in Form Object `messages()`.

```php
// lang/en/enrollment.php
'register_success' => 'Registration submitted successfully.',
// lang/id/enrollment.php
'register_success' => 'Pendaftaran berhasil dikirim.',
```

**Pitfalls to avoid:**

- Writing `'Registration saved'` inline in a Blade view or flash call (D3 violation).
- Adding the key to `lang/en/` but forgetting `lang/id/` — the arch scanner and reviewers will flag
  the mismatch.
- Using the same key for two different messages to save entries — keys must be unique per meaning.

**Verification:** `grep` for every user-facing string resolves to a key present in both language
files; `__('{key}')` echoes a non-raw value in both locales.

---

## New Feature — Must Update Relevant Docs (Documentation-First)

**What it enforces:** Feature work updates module documentation alongside the build — conceptual
`docs/modules/{module}.md` (business rules, flows) and/or reference
`docs/modules/{module}-reference.md` (file structure, schema, routes, Actions table) — not as an
afterthought at the end.

**Why it matters:** Documentation-first is what keeps the docs and code from drifting. If docs are
written only "when there's time", they never are, and the module reference becomes stale — the exact
state `sync-docs` exists to repair. Updating docs in the same pass as the code (build order step 1
starts with Docs) makes the doc an artifact of the change instead of a reconstruction.

**How to apply:** At build order step 1, draft the doc changes the feature implies (new Actions in
the reference Actions table, new schema in the reference, new business rules in the conceptual doc).
Finish them before commit and keep the metadata `> **Last updated:**` + `**Changes:**` line fresh.
After the build, hand off to `sync-docs` to confirm alignment.

**Pitfalls to avoid:**

- Shipping code and deferring docs "to a follow-up issue" — follow-ups on docs are how drift starts.
- Updating docs without touching their `**Last updated:**` metadata — the doc-link scanner flags
  stale docs.
- Duplicating content across `{module}.md` and `{module}-reference.md` instead of cross-referencing
  (Dedup-Align Doctrine).

**Verification:** `scan_doc_links.py` reports no broken or stale module docs; the module reference
matches the actual file tree and Action list of the shipped feature.

---

## References

| Topic                           | Asset                                     |
| ------------------------------- | ----------------------------------------- |
| Localization convention (D3)    | `AGENTS.md` §Critical Invariants           |
| Localization rules              | `docs/conventions.md` §Localization        |
| Doc tier selection & metadata   | `.agents/skills/context-awareness/SKILL.md` §Documentation Senses |
| Doc writing                     | `.agents/skills/doc-writing/SKILL.md`      |
| Doc sync                        | `.agents/skills/sync-docs/SKILL.md`        |
