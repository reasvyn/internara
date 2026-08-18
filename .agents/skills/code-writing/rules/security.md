# Security Patterns — XSS, SQL Injection, Mass Assignment, CSRF

> **Last updated:** 2026-08-17 **Changes:** rewritten comprehensively — per-attack-vector intent, application, and detection

These patterns close the web-app top four (OWASP A01/A02/A03) at the code level. Enforced by
`scan_security.py`; see `security-audit` for the full audit workflow and `docs/conventions.md` §3
for the written standard.

---

## XSS Prevention

**Intent:** Never render unsanitized user content as executable HTML.

- Use `{{ $var }}` for all user content (auto-escaped).
- `{!! $var !!}` only for explicitly sanitized content, with an inline safety comment.
- Alpine.js `x-html` follows the same rule — never bind raw user input.

```blade
{{-- SAFE: auto-escaped --}}
{{ $user->name }}

{{-- SAFE: sanitized HTML content --}}
{!! $sanitized_html !!} {{-- HTMLPurifier sanitized --}}

{{-- DANGEROUS: never do this --}}
{!! $user->input !!} {{-- XSS vulnerability --}}
```

**Why it matters:** Blade auto-escaping protects `{{ }}`; `{!! !!}` is the escape hatch. Rendering
raw user input through it (or Alpine `x-html`) lets a stored `user->input` carrying `<script>`
execute in every viewer's browser — stored XSS.

**How to apply:** Default to `{{ }}`. For rich text, run content through a sanitizer (HTMLPurifier)
once on the way in and render with `{!! !!}` **plus** a `{{-- sanitized --}}` comment stating which
sanitizer ran. Never pass request input straight to `{!! !!}`.

**Anti-patterns to avoid:** `{!! $entry->body !!}` with no sanitize step anywhere; `x-html="
rawUserInput"`.

**Detection:** `python3 scripts/scan_security.py` (XSS regex on `{!!` and `x-html`).

---

## SQL Injection

**Intent:** All SQL is parameterized; raw fragments never interpolate values.

- Always use the Eloquent query builder.
- `DB::raw()` / `whereRaw()` forbidden without parameterized binding.
- If raw SQL is unavoidable, document the exception in the method's docblock.

**Why it matters:** A raw query that interpolates input (`DB::select("SELECT * FROM users WHERE id
= $id")`) is a direct injection channel; a hostile `$id` can reshape or truncate the statement.
Binding (`?` / named params) separates data from code so injection is structurally impossible.

**How to apply:** Default to `where()`/`orderBy()`; for expressions, `DB::raw('...', [$value])` or
`whereRaw('... ?', ...)` with the value as a binding, plus a docblock note. Search: `rg "DB::raw"
app/`.

**Anti-patterns to avoid:** String-concatenating variables into `whereRaw`/`orderByRaw`; double
"sanitizing" input and calling it bound.

**Detection:** `python3 scripts/scan_security.py` · `scan_violations.py` (C3).

---

## Mass Assignment

**Intent:** Mutation input is limited to an explicit allow-list.

- Use `#[Fillable([...])]` attribute on every Model (D4).
- Never `$request->all()` or `$this->all()` — use `->only()` or a validated DTO (`->toArray()`).

**Why it matters:** `->all()` forwards every submitted field to the mutation; an attacker who adds
`admin=1` to the form hits `create()` and, if that column is fillable, escalates privileges. The DTO
(or `->only()`) passes only the fields the feature intends.

**How to apply:** Build `{Verb}{Entity}Data::from([...])` with explicitly listed properties and
`Model::create($data->toArray())`; for direct form reads, `$this->only([...])` with the field list.

**Anti-patterns to avoid:** `Model::create($request->all())`; trusting "the form only sends what I
render".

**Detection:** `python3 scripts/scan_security.py` · `scan_violations.py` (D5).

---

## CSRF

**Intent:** Every state-changing request carries a CSRF token.

- `@csrf` or Livewire for all state-changing forms.
- Exemptions require an explicit code comment explaining why (and are rare).

**Why it matters:** Without a token, a third-party page can submit a crafted POST that mutates state
on the user's behalf (e.g., changing their password). Laravel's middleware is opt-in at the view
level; a form without `@csrf` skips the check.

**How to apply:** All `form` actions (non-Livewire) include `@csrf`; Livewire components are covered
by their own token handling. If a route must be exempt (e.g., a public webhook), annotate the
comment with the reason and keep the route narrow.

**Anti-patterns to avoid:** A bare `<form method="post">` with no `@csrf`; broad exemptions applied
to an entire route group without comment.

**Detection:** `python3 scripts/scan_security.py` (form/CSRF regex).