# Security Checklist — Vulnerability Scan Items

Quick-reference checklist to ensure no critical attack vector is missed. Do NOT use as spec — see
`docs/` and OWASP for details.

## Authentication & Authorization

- [ ] Password hashing: bcrypt/argon2 via Laravel defaults
- [ ] Login rate limiting active (check `bootstrap/app.php`)
- [ ] Super admin bypass via `Gate::before` — verify it exists
- [ ] Policy methods return boolean — check every method
- [ ] No unintentional `Gate::authorize()` bypass

## XSS Prevention

- [ ] All Blade output: `{{ }}` (escaped)
- [ ] Every `{!! !!}` has inline justification comment
- [ ] No inline `<script>` — all via Alpine.js
- [ ] CSP active via `SecurityHeaders` middleware

## SQL Injection

- [ ] No `whereRaw()` / `DB::raw()` without parameterized binding
- [ ] No string concatenation in query builder
- [ ] `where('column', $value)` over `whereRaw("column = '$value'")`

## Mass Assignment

- [ ] Every model: `#[Fillable]` attribute (not `$fillable`/`$guarded`)
- [ ] No `Model::create($request->all())`
- [ ] No `Model::create($this->all())` in Livewire

## File Upload

- [ ] All uploads via Spatie MediaLibrary
- [ ] MIME type validated server-side (not just extension)
- [ ] Filename sanitized with `Str::slug()`

## PII & Data Protection

- [ ] User profiles separated from credentials (different tables)
- [ ] PII masking via `SmartLogger::withPiiMasking()`
- [ ] GDPR deletion path exists (`gdpr_deletion_logs` table)

## Configuration

- [ ] No hardcoded secrets in code
- [ ] `.env` excluded from version control
- [ ] APP_KEY unique per installation
- [ ] Database credentials in `.env` only
