# Logging & Error Handling Pattern — SmartLogger, PII Masking & Error Handling

## Description

SmartLogger dual-channel system, PII masking, translation resolution, error context, and
observability integration. Grounded in **PSR-3** (Logger Interface), **Structured Logging**,
**PII/GDPR compliance**, and **Defence in Depth** (NIST) — all mapped to Internara's stack.

---

## Non-Negotiable

Hard rules. Violations are architecture violations.

1. **Single point of entry — SmartLogger.** All application logging MUST route through `SmartLogger`. No direct `Log::info()`, `Log::error()`, or `$this->log()` calls outside SmartLogger. This ensures consistent PII masking, dual-channel routing, and translation resolution.

2. **PII masking is mandatory.** Every log entry containing user data MUST pass through `PiiMasker::maskArray()`. Call `withPiiMasking()` on SmartLogger when logging payloads that may contain PII (email, phone, name, IP, User-Agent). This is GDPR §5(1)(f) — integrity and confidentiality.

3. **Dual-channel separation.** System log (`storage/logs/`) is for technical debugging. Activity log (`activity_log` table) is for business audit trail. Never mix concerns — technical errors go system-only, business mutations go both channels.

4. **Activity log is append-only.** Never update or delete activity log entries. The audit trail is immutable. If an entry is wrong, create a correcting entry — never modify the original.

5. **Activity log failure is non-blocking.** If the database is unreachable, the activity log insert MUST fail gracefully. The system log writes a diagnostic entry, execution continues, and the calling Action completes successfully. The application MUST NOT crash because of audit logging failure.

6. **System log failure IS blocking.** If `storage/logs/` is unwritable, the exception propagates. A system that cannot log should surface the problem immediately.

7. **Known exceptions are NOT logged.** `RuntimeException`, `AppException`, `ModuleException`, `ValidationException`, `AuthorizationException`, `ModelNotFoundException`, `NotFoundHttpException` are re-thrown immediately without logging. Only unexpected `\Throwable` instances are caught, logged, and re-thrown.

---

## How to Apply

### 1. PSR-3 — Logger Interface (PHP-FIG)

PSR-3 defines a common interface for logging libraries. SmartLogger implements the spirit of PSR-3 (severity levels, context arrays, message templates) while extending it with dual-channel routing and PII masking. The LoggerInterface defines: `emergency()`, `alert()`, `critical()`, `error()`, `warning()`, `notice()`, `info()`, `debug()`. SmartLogger maps to: `success()`, `info()`, `warning()`, `error()`.

**Reference:** [PSR-3 Logger Interface](https://www.php-fig.org/psr/psr-3/)

### 2. Structured Logging

Every log entry includes structured context: module name, event name, causer ID, subject ID, payload data. This enables programmatic querying of the activity log — filter by module, by user, by date range. The system log uses Laravel's daily rotation (14-day retention).

### 3. PII/GDPR Compliance

SmartLogger's PII masking covers:

| Data Type | Masking | Example |
|-----------|---------|---------|
| Email | Partial mask | `jo***@example.com` |
| Phone | Partial mask | `*******8901` |
| Name | Partial mask | `J. Smith` |
| IP (v4) | First 2 octets | `192.168.***.***` |
| IP (v6) | First segment | `2001:db8::****` |
| User-Agent | Truncated | First 50 chars + `...` |
| Password/token/key | Full mask | `***` |

Recursive masking handles nested arrays at every nesting level.

### 4. Defence in Depth (NIST)

Three independent layers ensure logging integrity:

| Layer | Mechanism | Purpose |
|-------|-----------|---------|
| **Route middleware** | `CheckRoleMiddleware` | Prevents unauthorized access |
| **Livewire `authorize()`** | Policy delegation | UI-level authorization |
| **Policy `before()`** | `Gate::before` for super admin | Ultimate bypass guard |

---

## Anti-Patterns

| You see... | It should be... | Violation |
|-----------|----------------|-----------|
| `Log::info('User created')` outside SmartLogger | `SmartLogger::info('User created')->save()` | Single point of entry bypassed |
| Logging raw email/IP without `withPiiMasking()` | `SmartLogger::info(...)->withPiiMasking()->save()` | PII exposure in logs |
| `activity_log` write wrapped in try-catch that re-throws | Try-catch that logs to system and continues | Activity log failure blocks execution |
| Updating activity log entries | Create a correcting entry | Audit trail integrity |
| Logging `ValidationException` or `RejectedException` | Re-throw without logging | Known exception noise |
| Technical error logged to activity-only | `systemOnly()` for infrastructure failures | Channel misrouting |
| Activity log without causer (anonymous entries) | Always provide `for($user)` or verify `Auth::user()` | Anonymous audit entries |

---

## Quick References

- `docs/conventions.md` §Logging Conventions — SmartLogger usage, PII masking
- `docs/guides/infra/logging.md` — complete logging strategy
- `app/Modules/Core/Support/SmartLogger.php` — SmartLogger implementation
- `app/Modules/Core/Support/PiiMasker.php` — PII masking utility
- [PSR-3 Logger Interface](https://www.php-fig.org/psr/psr-3/) — PHP logging standard
- [Laravel — Logging](https://laravel.com/docs/logging) — Log channels, daily rotation
- [GDPR Article 5](https://gdpr-info.eu/art-5-gdpr/) — Principles relating to processing of personal data
- [Structured Logging](https://www.structuredlogging.org/) — key-value log format
- [OWASP Logging Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Logging_Cheat_Sheet.html) — security logging practices
