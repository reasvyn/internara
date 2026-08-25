# Data Security & Privacy

> **Last updated:** 2026-08-25 **Changes:** new skill — security & privacy for data-architect

## Intent

PII and sensitive fields are identified at design time, encrypted or masked at rest/in-transit, and never leak via logs, exports, or Blade. Security is a schema concern as much as an auth concern.

## What it enforces

- **PII inventory:** Each module lists PII fields in its spec/ADR (e.g., `users.email`, `users.phone`). No new PII column without a spec ID and masking plan.
- **Masking:** Logs use SmartLogger with PII masking (`docs/architecture/logging-pattern.md`); `scan_security.py` flags plaintext PII in `Log::info()` payloads. Exports mask by default unless explicitly authorized.
- **Encryption at rest:** Sensitive-at-rest fields use Laravel encrypted casts (`Encrypted` cast or `encrypted:array`) — declare in Model and verify in spec.
- **Field-level RBAC:** Blade reads `Entity::canViewField($actor, $field)` or Policy gates before rendering a sensitive column; never gate in Blade — gate in Entity/Policy and pass a boolean/DTO to the view (see `ui-development` Blade rule).
- **Audit trail:** Writes that touch PII or status transitions emit activity log via `Spatie\Activitylog` + SmartLogger dual-channel (DB + file), with causer tracking.
- **No mass-assignment of PII:** `#[Fillable]` (D4) whitelist + DTO validation (D5) — never `create($request->all())`.

## How to apply

- Add a PII column? Update spec with `FR-...: Store {field} encrypted`, add `Encrypted` cast to Model, add masking rule to logging, and add Policy check for read.
- Render PII? `{{ $entity->maskedEmail() }}` or `{{ $dto->emailMasked }}` — Entity/DTO Owns the masking format, Blade only binds.
- Export? Reuse the same Entity masking; require explicit `canExportRawPii` gate.

## Pitfalls to avoid

- Logging `$request->all()` that contains PII — log a DTO with masked fields instead.
- Storing phone/ID number as plain `string` without `Encrypted` + spec justification.
- `{!! $userInput !!}` in Blade for data-derived HTML (XSS) — use `{{ }}` or sanitized `Purifier`.
- Gating PII visibility with `@if (auth()->user()->hasRole(...))` in Blade — move to Entity/Policy or `@hasrole` with a precomputed gate.

## Verification

- `python3 scripts/scan_security.py` — no XSS/SQLi/PII leak, no unescaped user HTML.
- `python3 scripts/scan_conventions.py` — D4 (Fillable) clean.
- `grep -R "->all()" app --include="*.php"` shows no raw mass assignment of PII.
- Activity log entries exist for PII writes (`activitylog` table + log file).

## References

| Topic | Asset |
|-------|-------|
| Logging & masking | `docs/architecture/logging-pattern.md` |
| RBAC / field visibility | `docs/architecture/policy-pattern.md` |
| Mass assignment (D4/D5) | `docs/conventions.md` §3.3 |
| PII handling | `docs/conventions.md` §3.1–3.7 |
