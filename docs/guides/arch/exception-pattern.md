# Exception Pattern — Dual Hierarchy, Error Handling & RejectedException

## Description

This pattern governs how Internara structures **exception hierarchies** and **error handling**. It synthesizes global industry standards — **Exception Hierarchy Design** (Robert C. Martin, SOLID), **Fail-Fast Principle** (Programming by Contract, Bertrand Meyer), **Result Type** concept (functional error handling), **Defence in Depth** (NIST) — into enforceable rules tied to Internara's stack: `AppException` (infrastructure), `ModuleException` (business rules), `RejectedException`, `HasExceptionContext`, and `HandlesActionErrors`.

Without it, `RuntimeException` is used for everything (business rules, validation, infrastructure), catch blocks cannot distinguish failure types, and error messages leak stack traces. With it, every exception carries precise semantics, catch blocks are targeted, and users see friendly messages while developers get full context.

---

## Non-Negotiable

Hard rules. Violations are architecture violations.

1. **`RejectedException` for business rules, never `RuntimeException`.** Business rule violations (state machine, entity invariants, domain constraints) MUST throw `RejectedException`. `RuntimeException` is reserved for infrastructure failures. This is **C8** (invariant from `docs/conventions.md` §9). The exception message describes what was rejected and why — no default messages.

2. **Two trees, not one.** `AppException` (infrastructure/HTTP) and `ModuleException` (business rules) are **independent siblings** — both extend `RuntimeException` but are NOT parent-child. This enables precise catch targeting:

```php
catch (ModuleException $e)         // Business rule violations only
catch (InfrastructureException $e) // Infrastructure failures only
```

If `ModuleException` extended `AppException`, a `catch (AppException $e)` would silently swallow business rule violations.

3. **`HasExceptionContext` for both trees.** All exceptions use the shared trait providing: `withHint()` (user-facing resolution), `withContext()` (debug metadata), `toCliOutput()` (terminal display), `getSanitizedContext()` (PII-masked), `isUserFacing()`, `shouldReport()`.

4. **HandlesActionErrors as safety net.** Known exception types (`RejectedException`, `ValidationException`, `AuthorizationException`) pass through unmodified. Unknown `Throwable` is logged with full context and rethrown as generic `RuntimeException` — preventing stack traces from leaking to users.

5. **Every throw site provides context.** No default messages. Every `throw new RejectedException(...)` must include what was rejected and why. Every `throw new InfrastructureException(...)` must include what failed and the relevant identifiers.

6. **`isUserFacing()` distinguishes display from logging.** User-facing exceptions (input corrections, business rejections, permission denials) are shown to users. System-facing exceptions (infrastructure failures) result in a generic error page while full details are logged internally.

---

## How to Apply

### 1. Dual Hierarchy — Why Two Trees

```
RuntimeException
│
├── AppException (abstract)            ← Application, HTTP, infrastructure failures
│   │
│   ├── ActionException (abstract)     ← Request-level failures (validation, conflict)
│   ├── InfrastructureException (abstract)  ← System-level failures
│   └── PresentationException (abstract)    ← HTTP-level failures
│
└── ModuleException (abstract)         ← Business rule violations
    └── RejectedException
```

### 2. AppException Tree

| Branch | Purpose | User-Facing? | Examples |
|--------|---------|--------------|---------|
| `ActionException` | Request-action failures | Yes | Validation error, conflict state |
| `InfrastructureException` | System-level failures | No | I/O error, rate limit, DB unreachable |
| `PresentationException` | HTTP-level failures | Yes | Not found, unauthorized, forbidden |

Every concrete exception implements `statusCode()` — the HTTP status code it maps to.

### 3. ModuleException Tree

`RejectedException` is the sole concrete child. Thrown when a domain invariant or business rule is violated. The exception message describes what was rejected and why.

### 4. HasExceptionContext — Shared Capabilities

| Capability | Purpose |
|-----------|---------|
| `withHint()` | User-facing resolution hint |
| `withContext()` | Debug metadata (key-value) |
| `toCliOutput()` | Terminal display (message + hint + sanitized context) |
| `getSanitizedContext()` | PII-masked context |
| `isUserFacing()` | `true` by default; `false` for infrastructure |
| `shouldReport()` | `true` by default; `false` for expected/graceful |

### 5. Error Handling in Actions — Defence in Depth

`HandlesActionErrors` trait provides a safety net:

```php
// Known types — rethrown as-is (they carry correct semantics)
catch (RejectedException $e) { throw $e; }
catch (ValidationException $e) { throw $e; }
catch (AuthorizationException $e) { throw $e; }

// Unknown — logged with full context, wrapped in RuntimeException
catch (Throwable $e) {
    SmartLogger::critical('Unexpected error')->withContext([...])->save();
    throw new RuntimeException('An unexpected error occurred', 0, $e);
}
```

This is **Defence in Depth** (NIST) — never rely on a single error handling layer.

### 6. Three Failure Modes

| Failure Mode | Exception | Handled By | User Experience |
|-------------|-----------|-----------|----------------|
| Format/invalid input | `ValidationException` | Livewire error bag | Inline field errors |
| Business rule violation | `RejectedException` | Component try/catch | Flash error message |
| Infrastructure failure | `RuntimeException` (rethrown) | Component try/catch | Generic error message |

---

## Anti-Patterns

| You see... | It should be... | Violation |
|-----------|----------------|-----------|
| `throw new RuntimeException('business rule')` | `throw new RejectedException('business rule')` | C8 — wrong exception type |
| `catch (Exception $e)` catching everything | `catch (RejectedException $e)` / `catch (InfrastructureException $e)` | Imprecise catch — swallows all types |
| `throw new RejectedException()` with no message | `throw new RejectedException(__('internship.cannot_approve'))` | No context, no user-facing message |
| `throw new \RuntimeException()` for business logic | `throw new RejectedException()` | Business rule in wrong tree |
| `dd($e)` / `dump($e)` in catch blocks | `SmartLogger::critical(...)` + rethrow | Debug call in production |
| Catch block that silently ignores exception | At minimum: `SmartLogger::warning(...)` + rethrow or user toast | Silent failure |
| `try { $action->execute(); } catch (\Throwable $e) { // no log }` | `HandlesActionErrors` or explicit logging | Lost error context |
| `throw new \Exception()` (base PHP Exception) | Use project-specific exception hierarchy | No semantic distinction |
| Business logic in catch block (retry, state change) | Business logic in Action; catch only for error display | Catch is not a business rule executor |
| No `statusCode()` on AppException subclass | Every concrete AppException implements `statusCode()` | Missing HTTP mapping |

---

## Quick References

- `action-pattern.md` §9 Error Handling — three failure modes, `HandlesActionErrors`
- `livewire-pattern.md` — Livewire catch blocks, `RejectedException` → toast
- `logging-pattern.md` — SmartLogger exception context, PII masking
- `modular-pattern.md` §10 Logging & Error Handling — architecture contracts
- [Robert C. Martin — SOLID Principles](https://en.wikipedia.org/wiki/SOLID) — SRP, error handling discipline
- [Bertrand Meyer — Programming by Contract](https://en.wikipedia.org/wiki/Design_by_contract) — Fail-Fast, preconditions
- [Wikipedia — Exception Handling](https://en.wikipedia.org/wiki/Exception_handling) — exception hierarchy design
- [PHP — SPL Exceptions](https://www.php.net/manual/en/spl.exceptions.php) — standard exception hierarchy
- [Result Type Concept](https://doc.rust-lang.org/std/result/) — functional error handling (Rust `Result<T, E>`)
