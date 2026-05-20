# Validation

## What It Enforces

Form Request classes handle validation for Controller endpoints. Actions validate their own inputs independently (`Validator::validate()`) as the authoritative source. Array syntax is used for validation rules (not pipe `|` syntax). Custom validation rule classes encapsulate reusable business validation.

## Why It Matters

Defense in depth: Livewire validates for UX (inline errors), FormRequest validates for Controller endpoints, and the Action always validates authoritatively. Each layer is independent — if a caller skips the outer layers, the Action still enforces rules.

Array syntax (`['required', 'email']`) is preferred over pipe syntax (`'required|email'`) because it composes cleanly with `Rule::` objects, is easier to read for complex rules, and doesn't require escaping within strings.

## When It Applies

- Controller endpoints: FormRequest classes handle validation + authorization
- Actions: `Validator::validate()` or `Validator::make()->validate()` for authoritative validation
- Livewire components: `$this->validate()` for UX inline errors (Action re-validates)
- Reusable business rules: custom ValidationRule classes

Use `validated()` on FormRequest results — never `$request->all()`. Use `after()` for cross-field validation. Use `Rule::unique()->ignore()` for update scenarios.

Business rules vs validation:
- Format/constraint validation: Validator rules → throws ValidationException
- State-based business rules: Entity check + RejectedException
- Authorization: Policy Gate → AuthorizationException

Exceptions: Simple Actions with only one caller may duplicate rules instead of creating a shared FormRequest. The Action's validation is never optional.
