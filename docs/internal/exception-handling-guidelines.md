# Exception Handling: Engineering Resilience

In Internara, exception handling is treated as a core business concern. We aim to provide meaningful
feedback to users while ensuring that developers have the analytical data needed to resolve issues
quickly.

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

Database failures, network timeouts, etc. These are handled by the global Laravel handler, logged
with full context, and presented to the user as a generic "System Error" in production.

---

## 2. Using Custom Exceptions

Custom exceptions are located in the `Exception` module or within their respective feature modules.

### 2.1 Defining an Exception

```php
namespace Modules\Journal\Exceptions;

use Exception;

class JournalLockedException extends Exception
{
    // ...
}
```

### 2.2 Catching in Livewire

Always wrap Service calls in a try-catch block when an exception is expected.

```php
try {
    $this->journalService->update($id, $data);
    $this->success(__('journal::messages.updated'));
} catch (JournalLockedException $e) {
    $this->error(__('journal::messages.locked'));
}
```

---

## 3. Global Error Reporting & PII

To maintain privacy, certain data MUST NOT be logged in its raw form.

- **PII Masking**: The global logger automatically masks fields like `email`, `password`, and
  `phone` before writing to the log file.
- **Trace Context**: Ensure that logs include the `user_id` and `correlation_id` for easier
  debugging across modular boundaries.

---

_By following these guidelines, you help keep Internara stable and user-friendly. Remember: An
unhandled exception is a failure of documentation._
