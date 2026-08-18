# Error Handling Strategy

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive error-handling rules

Exceptions are classified by failure mode; each maps to a specific exception type and a specific
user experience. Mixing them misroutes the UX and defeats C8.

| Failure Mode | Exception | Handled By | User Experience |
|-------------|-----------|-----------|-----------------|
| Format/invalid input | `ValidationException` | Livewire error bag | Inline field errors |
| Business rule violation | `RejectedException` | Component try/catch | Flash error message |
| Infrastructure failure | `RuntimeException` (rethrown) | Component try/catch | Generic error message |

**Rule:** Business rules use `RejectedException`. Infrastructure failures use `RuntimeException`.
Never use `RuntimeException` for a business rule.

**Why this classification matters:**
- `ValidationException` is raised by Livewire validation (Form Objects); the component catches it and
  renders per-field errors — the user sees exactly which field failed.
- `RejectedException` signals a *legitimate rejection* of a valid submission (duplicate record,
  terminal state, quota exceeded). It is a product-level outcome, not a bug, and surfaces as a
  translated flash message — the user can correct and re-submit.
- `RuntimeException` signals an infrastructural fault (DB down, HTTP 5xx, disk full). The user sees
  a generic error because the root cause is operational, and it is rethrown so the app-level handler
  logs it.

**How to apply:** In Entity business logic, throw `RejectedException` with a translated message
(`__()`). In Actions/Livewire, catch `RejectedException` around `execute()` and render the flash;
do not catch it upstream as an infrastructure error. Let `RuntimeException` propagate to the generic
handler with logging.

**Pitfalls to avoid:**
- Throwing `RuntimeException` for a duplicate-key check (kills the flash-message UX).
- Catching the generic `Throwable` before `RejectedException` in Livewire — the rejection never
  surfaces properly.
- Swallowing exceptions in an Action body instead of rethrowing infrastructure failures.

**Detection:** `python3 scripts/scan_violations.py` (C8) · review of try/catch ordering in Livewire
components.