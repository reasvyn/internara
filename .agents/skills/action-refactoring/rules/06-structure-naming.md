# Structure & Naming

## What It Enforces

Actions follow a strict naming convention: `{Verb}{Noun}Action.php` in `app/{Module}/Actions/`. The
verb describes the operation (Create, Update, Delete, Activate, Finalize, Verify, etc.) and the noun
describes the subject. All Actions extend `BaseAction` (which provides `transaction()`, `log()`, and
`withErrorHandling()`).

## Why It Matters

Consistent naming makes Actions discoverable by name alone. When you need to find "the thing that
deletes an academic year," you know it's `DeleteAcademicYearAction` in `app/Academics/Actions/`.
This predictability reduces search time and makes the codebase navigable without documentation.

The convention also prevents ambiguity. `AcademicYearAction` (without a verb) could contain multiple
methods — and that violates single responsibility. `DeleteAcademicYearAction` is unambiguous about
its purpose.

## When It Applies

Always. Every Action must follow the `{Verb}{Noun}Action` pattern.

The file header order is also prescribed:

1. `declare(strict_types=1)`
2. Namespace
3. Use statements (BaseAction, RejectedException, Model, Validator, dependencies)
4. Class declaration extending BaseAction
5. Constructor with `protected readonly` promotion for injected dependencies
6. Single `execute()` method

Return type conventions: Create returns the model, Update returns the model, Delete returns void,
Toggle/activate returns the model, Complex results return array or DTO.

Common verbs in this project: Create, Update, Delete, Activate, Deactivate, Finalize, Verify,
Submit, Approve, Reject, Upload, Set, Reset, Generate, Validate, Provision, Setup, Install, Recover,
Initialize, Toggle, Lock, Unlock, Score, Evaluate, Renew, Terminate, Batch.

Exceptions: None. This is a universal convention for the project.
