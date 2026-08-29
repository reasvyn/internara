# XSS & Injection — Output Encoding and Query Safety

## Intent

Cross-site scripting (XSS) and SQL injection are the two highest-frequency injection defects in PHP
applications. This rule defines how to audit both: every output path must escape by default (with
every unescaped output justified), and every query must be parameterized or built through the query
builder — never concatenated.

## Rationale

**XSS** fails when user-controlled content reaches the browser unescaped: the stored payload then
executes in every victim's session, stealing cookies, tokens, or acting as the user. Blade's `{{ }}`
already escapes; every `{!! !!}` is a deliberate undoing of that protection and must carry an inline
justification, or it is a finding.

**SQL injection** fails when attacker input is interpolated into a query string: the attacker
redefines the query and reads or corrupts the whole table. Eloquent and the query builder
parameterize automatically; `DB::raw()`, `whereRaw()`, and string concatenation reopen the hole.

Common to both: the framework protects by default, which makes the *undoings* the audit target.

## How to Apply

### XSS Prevention

- **All Blade output uses `{{ }}`** (double curly braces) — escaped. Flag any `{!! !!}`.
- **Every `{!! !!}` has an inline justification comment** — e.g. sanitized markdown via
  `Str::markdown()` + `HTMLPurifier`. No justification, no unescaped output.
- **No inline `<script>` tags** — everything interactive uses Alpine.js
  (`x-data`, `wire:click`, etc.), which keeps dynamic content in the framework's escaping model.
- **CSP enforced via `SecurityHeadersMiddleware`** — present in the middleware stack and applied to
  responses; no bypass without justification.
- **CSP allows only necessary external resources** — `script-src`, `style-src`, `img-src` are
  scoped; wildcard origins and `unsafe-inline`/`unsafe-eval` are findings.

### SQL Injection

- **No `whereRaw()` / `DB::raw()` without parameterized binding.** A raw fragment is acceptable only
  when every interpolated value is a bound parameter.
- **No string concatenation in the query builder** — `where('a = ' . $x)` style interpolation is a
  finding.
- **`where('column', $value)` used over `whereRaw("column = '$value'")`** — prefer the builder's
  binding every time.

## Examples

```blade
{{-- GOOD — escaped by default --}}
{{ $document->description }}

{{-- GOOD — unescaped markdown WITH sanitization + inline justification --}}
{{-- Sanitized by HTMLPurifier in the Document entity --}}
{!! $document->descriptionHtml() !!}  {{-- justified: output of sanitizer --}}

{{-- BAD — raw, unsanitized user content --}}
{!! $document->description !!}
```

```php
// GOOD — parameterized binding
$reports = Report::where('status', $status)->where('total', '>=', $minTotal)->get();

// GOOD — raw fragment with bindings
DB::table('reports')->whereRaw('applied_at >= ?', [$since])->get();

// BAD — interpolated value into raw SQL
$reports = Report::whereRaw("status = '$status'")->get();
```

## Anti-Patterns & Pitfalls

- **`{!! !!}` without a sanitizer** — the single most common stored-XSS route. Every unescaped output must name its sanitizer.
- **Content-Security-Policy disabled "during development"** — a production `.env` or middleware
  toggle that flips CSP off is a finding, even if the code is otherwise correct.
- **`whereRaw` with `->bindings()` forgotten** — a raw fragment written as if it parameterizes, but
  values are embedded in the string.
- **Escaping done at the wrong layer** — trusting DB escaping to fix XSS, or trusting Blade escaping
  to fix SQL; they are distinct, both audited.

## Verification & Detection

```bash
# Unescaped Blade output — each hit needs an inline justification
rg -n "\{!!" resources/views/

# Inline scripts — all interactivity should be Alpine/Livewire
rg -n "<script>" resources/views/

# Raw SQL without obvious bindings
rg -n "DB::raw\(|whereRaw\(|selectRaw\(|orderByRaw\(" app/ --include="*.php"
```
