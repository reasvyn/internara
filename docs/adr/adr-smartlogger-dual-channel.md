# SmartLogger Dual-Channel Logging

> **Last updated:** 2026-08-25 **Changes:** rewrite to MADR-lite industry-standard format

| Field | Value |
|-------|-------|
| Status | Accepted |
| Deciders | Reas Vyn |
| Date | 2026-08-16 |
| Technical Story | [Logging Pattern](../guides/arch/logging-pattern.md) and `laravel-activitylog` integration |

## Context and Problem Statement

The application needs two distinct logging concerns: technical system logs for debugging and
operations (`storage/logs/laravel.log`, daily rotation, 14-day retention) and business activity
audit logs for administrators and GDPR compliance (`activity_log` table via
`spatie/laravel-activitylog`, 365-day retention, queryable by user/action/module/date).
Using `Log::` directly for both mixes debug noise with audit events, leaks PII into plaintext
files, and leaves audit trails unqueryable. Spatie alone solves queryability but introduces a
single point of failure — database-channel errors would be invisible without a fallback.

**Decision Drivers:**

* Audit completeness by default — every significant business event must be traceable
* PII protection before data reaches any sink, even on developer error
* Queryability of audit trails without log-file grepping
* Resilience — activity-channel failure must not hide itself

## Considered Options

* **Single channel (`Log::` only)** — *Pros:* simplest. *Cons:* no queryable audit trail, no
  structured separation, PII in plaintext.*
* **Activity log only (Spatie)** — *Pros:* queryable. *Cons:* database failure hides audit loss;
  no system-level debug stream.*
* **Dual-channel SmartLogger with PII masking (chosen)** — fluent logger routing to either or
  both channels with automatic masking and shared structured context. *Pros:* audit + debug,
  masked by default, graceful degradation. *Cons:* two stores to prune.*

## Decision Outcome

**Chosen option: Dual-channel SmartLogger with PII masking** — all logging flows through
`SmartLogger`, a fluent, dual-channel logger routing every call to either or both sinks.

**Architecture:**

```
BaseAction::log()                    HandlesActionErrors
       │                                    │
       │ SmartLogger::info()               │ SmartLogger::error()
       │ withPiiMasking()                  │ systemOnly()
       │ both()                            │
       └──────────┬────────────────────────┘
                  │
                  ▼
          SmartLogger::save()
              │
        ┌─────┴──────┐
        ▼             ▼
   System Log    Activity Log
   (laravel.log) (activity_log)
```

**Fluent API:**

```php
SmartLogger::success('User registered')->for($user)->save();
SmartLogger::info('Profile updated')->for($user)->about($profile)->save();
SmartLogger::warning('Disk space low')->systemOnly()->save();
SmartLogger::error('Payment failed', ['txn' => 'abc'])->activityOnly()->save();
```

**Channel Routing:**

| Mode | System | Activity | Use Case |
|------|--------|----------|----------|
| `both()` | ✓ | ✓ | Default for Command Actions |
| `systemOnly()` | ✓ | — | Technical ops, errors via HandlesActionErrors |
| `activityOnly()` | — | ✓ | Audit-only events |

**PII Masking** — `withPiiMasking()` pipes payloads through `PiiMasker::maskArray()`:
`password`/`token`/`secret`/`api_key` fully masked (`***`), emails/phones/names partially,
IPs preserve first 2 octets. Key-name-based, not content-aware.

**Graceful Degradation** — activity channel wrapped in try-catch; on DB failure it logs to
system log and continues. System channel is not wrapped — unwritable files surface immediately.

**BaseEvent Integration** — events extending `BaseEvent` integrate via `event()`: dispatch
inside `save()`, `eventName()` supplies the translation key, `toPayload()` merges public
properties.

### Positive Consequences

* Every significant business event audited with full context — compliance-ready by default
* PII masked before reaching any sink, even on accidental full-payload logging
* Activity logs queryable via Eloquent scopes; enriched system logs via LogContextMiddleware

### Negative Consequences

* Two stores to operate (files + table); pruning via scheduled commands essential
* Key-name masking misses non-standard keys — requires discipline in payload key naming

## Links

* [Logging Pattern](../guides/arch/logging-pattern.md) — SmartLogger and PiiMasker contracts
* [Architecture Overview](../architecture.md) — where logging sits across layers
* [Activity Log Model](../guides/arch/logging-pattern.md) — queryable audit trail
