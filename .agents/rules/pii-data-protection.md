# PII & Data Protection — Privacy and GDPR Deletion

## Intent

Personally identifiable information (PII) — names, identifiers, contact data, academic records — is
the asset a PKL system exists to manage, and therefore the asset its breach is most punished for
losing. This rule defines how to audit PII handling: separation of credentials from profile data,
masking in logs, and a working GDPR deletion path. The goal is that a compromise at any single layer
(DB leak, log leak, backup leak) does not expose the full identity.

## Rationale

PII failures are a trust and compliance problem first, a technical one second. The concrete failure
modes:

- **Credentials coupled to profile data** — if the credentials table also holds full profile data, a
  leaked auth dump exposes identities wholesale. Separating *what authenticates* from *what identifies*
  means a credentials leak does not automatically become a full-PII leak.
- **PII in logs** — emails, phone numbers, national IDs, and tokens written to log files become
  available to anyone with log access (support, CI, monitoring), and are nearly impossible to purge
  retroactively.
- **No deletion path** — the GDPR right to erasure is not optional for an Indonesian school managing
  student data; without a tracked deletion trail the "deletion" is unverifiable and the data may
  still be in backups.

## How to Apply

- **User profiles stored in separate table from credentials.** Audit the schema: the identity
  mechanism (password hash, email login, session) lives apart from the profile (full name, NISN,
  address, phone). Act on the check: confirm the separation exists, not just that two tables happen
  to exist.
- **Check `app/Modules/Core/Support/PiiMasker.php`** — the canonical masker. It exists and defines the
  fields to mask (email, phone, NISN, tokens, etc.).
- **Activity log does not store raw PII.** Scan `spatie/laravel-activitylog` usage — properties
  causing PII to be captured verbatim are findings.
- **GDPR deletion path exists** — the `gdpr_deletion_logs` table and the flow that writes to it.
  Verify a deletion can be performed and leaves a traceable record.
- **PII masked in logs via `SmartLogger::withPiiMasking()`** — every log write that could carry PII
  passes through the masker; direct `Log::info($rawPayload)` calls are findings.

## Examples

```php
// GOOD — structured, masked logging via the project helper
Log::info('Account recovery requested', $this->log()->withPiiMasking([
    'user_id' => $user->id,
    'identifier' => $user->identifier,
]));

// BAD — raw PII written to the log
Log::info("Password reset requested for {$user->email}");

// BAD — activity captured verbatim
activity()->performedOn($intern)->withProperties(['email' => $intern->email])->log('exported');
```

## Anti-Patterns & Pitfalls

- **Logging the full request payload** — `Log::debug('request', $request->all())` leaks every field
  the form carried.
- **One off-masker write** — a single `Log::error("email={$e->getMessage()}")` in a catch block
  quietly reintroduces PII into the entire logging pipeline.
- **GDPR deletion without a trace** — "deleting" rows and writing nothing to `gdpr_deletion_logs`
  makes the erasure unprovable.
- **Profile columns drifting back into the credentials table** — new migrations adding contact data
  to the auth table without revisiting the separation.

## Verification & Detection

```bash
# Log writes that may carry PII directly
rg -n "Log::.*(email|phone|contact|address|[Nn][Ii][Ss][Nn]|token|password)" app/ --include="*.php"

# Raw activity log property capture
rg -n "->withProperties\(" app/ --include="*.php"

# The GDPR deletion marker/trail
rg -n "gdpr_deletion_logs|gdpr" database/ app/ --include="*.php"
```
