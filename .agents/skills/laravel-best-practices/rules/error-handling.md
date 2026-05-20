# Error Handling

## What It Enforces

A structured exception hierarchy separates domain exceptions from framework exceptions. `RejectedException` communicates business rule violations. `HandlesActionErrors` trait wraps infrastructure failures. Components catch `RejectedException` for flash messages. JSON responses for API routes.

## Why It Matters

Different exception types enable different handling strategies. `ValidationException` is automatic from `Validator::validate()` — caught by Livewire's error bag. `RejectedException` is explicit — caught by component try/catch, displayed as a flash message. Infrastructure exceptions are caught and logged by `HandlesActionErrors`, then rethrown as generic errors.

The separation between `DomainException` and `AppException` keeps domain catch blocks isolated from the layered framework. Business logic never needs to catch framework-level exceptions.

## When It Applies

- Actions: throw `RejectedException` for business rule violations, never bare `RuntimeException`
- Components: catch `RejectedException` → flash error; catch `RuntimeException` → flash generic error
- Traits: `HandlesActionErrors` wraps callbacks for infrastructure safety
- Exception classes: implement `context()` method for logging payload
- API routes: force JSON exception rendering
- Logging: enable `dontReportDuplicates()` to prevent logging the same exception multiple times

Never implement `ReportableException` interface — use `$exception->report()` on the exception class instead.

Exceptions: `RejectedException` is only for business rules. Infrastructure failures (database connection errors, filesystem errors) are `RuntimeException` — they should not be user-friendly because there's nothing the user can do.
