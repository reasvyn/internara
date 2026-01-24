# Exception Handling: Engineering Resilience

In Internara, exception handling is treated as a core business concern. We aim to provide meaningful
feedback to users while ensuring that developers have the analytical data needed to resolve issues
quickly.

> **Governance Mandate:** Exception handling must ensure system integrity and security as defined in
> the **[Internara Specs](../internal/internara-specs.md)**. Sensitive data must never be exposed
> through error messages.

---

## 1. The Strategy: "Fail Fast & Gracefully"

We follow a tiered approach to managing errors:

### 1.1 Validation Errors (Expected)

Handle these at the UI or Service entry point. Always return helpful, localized error messages via
Laravel's validation system.

### 1.2 Business Logic Exceptions (Logical)

Use custom exceptions for business rule violations (e.g., `JournalAlreadyApprovedException`). These
should be caught by the UI layer to display a friendly notification.

### 1.3 System Errors (Unexpected)

Database failures, network timeouts, etc. These are handled by the global Laravel handler and
presented to the user as a generic "System Error" in production.

---

## 2. Multi-Language & Standardized Messages

All exception messages **MUST** be localized.

- **Module-Specific Exceptions:** Use the `module::exceptions.key` pattern.
    - _Example:_ `__('journal::exceptions.locked')`
- **General Exceptions:** Use the `exception::messages.key` pattern.
    - _Example:_ `__('exception::messages.unauthorized')`

---

## 3. Using Custom Exceptions

Custom exceptions are located within their respective feature modules under `src/Exceptions/`.

### 3.1 Catching in Livewire

Always wrap Service calls in a try-catch block when an exception is expected.

```php
try {
    $this->journalService->update($id, $data);
    $this->success(__('journal::ui.updated'));
} catch (JournalLockedException $e) {
    $this->error(__('journal::exceptions.locked'));
} catch (AuthorizationException $e) {
    $this->error(__('exception::messages.unauthorized'));
}
```

---

## 4. Global Error Reporting & Privacy

To maintain privacy and comply with security specs:

- **PII Masking**: The global logger automatically masks fields like `email`, `password`, and
  `phone` before writing to logs.
- **Trace Context**: Logs include the `user_id` and `correlation_id` for traceability.
- **No Hard-coding:** Error messages must never be hardcoded in English or Indonesian within the PHP
  code.

---

_By following these guidelines, you help keep Internara stable and user-friendly. Remember: An
unhandled exception is a failure of documentation._
