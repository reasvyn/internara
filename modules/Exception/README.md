# Exception Module

The `Exception` module centralizes all error handling and exception management within Internara. it
ensures that every error, whether from domain logic or system failure, is handled consistently and
presented safely to the user.

## Purpose

- **Standardization:** Provides base exception classes for the entire application.
- **Localization:** Manages user-friendly, translatable error messages.
- **Security:** Prevents sensitive system information from leaking in production logs or responses.

## Key Features

### 1. Base Exception Classes

- **[AppException](../../docs/main/exception-handling-guidelines.md#21-appexception-modulesexceptionappexception)**:
  The foundational class for all domain errors.
- **[RecordNotFoundException](../../docs/main/exception-handling-guidelines.md#22-recordnotfoundexception-modulesexceptionrecordnotfoundexception)**:
  Standardized handling for missing data.

### 2. Standardized Logic

- **[HandlesAppException Trait](../../docs/main/exception-handling-guidelines.md#4-global-exception-handling-strategy)**:
  Provides methods for consistent reporting and rendering across web and API requests.

### 3. Multi-language Messages

- Centralized translation files in `lang/` for common error states.

---

**Navigation** [← Back to Module TOC](../../docs/main/modules/table-of-contents.md)
