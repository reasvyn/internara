# Exception & Error Handling

## What It Enforces

The project uses a structured exception hierarchy: `ValidationException` for format/constraint
validation, `RejectedException` for business rule violations, and `RuntimeException` for
infrastructure failures. Debug functions (`dd()`, `dump()`, `ray()`, `var_dump()`, `print_r()`,
`die()`) are forbidden in application code.

## Why It Matters

Different failure modes propagate to different layers:

- `ValidationException` is caught by Livewire's error bag and shown as inline field errors
- `RejectedException` is caught by component try/catch and shown as user-friendly flash messages
- `RuntimeException` indicates an infrastructure problem that should be logged and shown as a
  generic error

A clear exception chain means every layer knows what to expect. Components don't need to parse error
messages to determine the failure type — the exception class tells them.

## When It Applies

The full exception chain during a request:

1. Livewire validates (UX layer, inline errors)
2. Action validates authoritatively (`ValidationException` if format fails)
3. Action checks Entity rules (`RejectedException` if business rule fails)
4. Action wraps persistence in transaction (`HandlesActionErrors` catches infrastructure failures)
5. Component catches exceptions and shows appropriate feedback

Architecture tests enforce the debug function ban. Never use `dd()`, `dump()`, `ray()`, or similar
in committed code.

Exceptions: Debug functions are acceptable in test files and local-only development. The
`RejectedException` is only for business rule violations — infrastructure problems should use
`RuntimeException`.
