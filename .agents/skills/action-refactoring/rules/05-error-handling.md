# Error Handling

## What It Enforces

Actions use a structured exception hierarchy for different failure modes. Business rule violations throw `RejectedException`. Format validation errors throw `ValidationException` (automatic from `Validator::validate()`). Infrastructure failures are caught by the `HandlesActionErrors` trait and rethrown as `RuntimeException`. Components catch `RejectedException` to display user-friendly flash messages.

## Why It Matters

Different failure modes require different handling at the UI layer:
- Validation errors must show inline field errors — Livewire's error bag handles these automatically
- Business rule violations must show a flash message explaining why the operation was rejected
- Infrastructure errors must be logged for debugging and shown as a generic error message

A bare `RuntimeException` communicates nothing about the failure type. `RejectedException` signals "this was expected business behavior" and should be presented as helpful feedback, not an error page. The `HandlesActionErrors` trait ensures unexpected infrastructure failures are logged with context and rethrown as a known type.

## When It Applies

Always use `RejectedException` for business rule violations caught by Entity checks. Never throw bare `RuntimeException` for business logic.

The error flow is:
1. Validation fails → `ValidationException` → caught by Livewire error bag → inline errors shown
2. Business rule fails → `RejectedException` → caught by component try/catch → flash error message
3. Infrastructure fails → `HandlesActionErrors` logs + rethrows → caught by component try/catch → generic error message

Exceptions: `RejectedException` is only for business rule violations. Infrastructure problems (database connection failure, filesystem full) are `RuntimeException` — they should not be presented as user-friendly messages because there's nothing the user can do about them.
