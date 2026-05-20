# Validation

## What It Enforces

The Action is the authoritative source of validation. Livewire components may repeat validation rules for UX (inline error display), but the Action always re-validates independently before performing any operation. The Action uses `Validator::validate()` or `Validator::make()->validate()` to enforce format and constraint rules.

## Why It Matters

Defense in depth. Livewire validation runs in the browser context and can be bypassed — either accidentally (JavaScript disabled) or intentionally (crafted requests). The Action runs server-side and cannot be circumvented because it's the last validation gate before persistence.

Separating UX validation (component) from authoritative validation (Action) also clarifies intent. The component validates for user experience — showing inline errors, disabling submit buttons. The Action validates for data integrity — ensuring only valid data reaches the database.

## When It Applies

Always validate in Actions. The component may optionally validate for UX, but the Action always validates.

The distinction between validation and business rules matters:
- Format validation (required fields, email format, string length) → `Validator::validate()` throws `ValidationException`
- Uniqueness constraints → included in Validator rules → `ValidationException`
- State-based business rules (can this record be deleted?) → Entity check + throw `RejectedException`
- Authorization → Policy `Gate` check → `AuthorizationException`

Exceptions: None for the principle itself. The validation approach varies by context:
- Simple Actions with no HTTP context: `Validator::make()` inside the Action
- Actions called from Controllers: FormRequest classes shared across endpoints
- The validation rules themselves should be kept in the Action (not extracted to FormRequest) unless the same rules are needed by multiple Actions
