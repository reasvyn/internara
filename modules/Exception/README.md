# Exception Module

The `Exception` module centralizes all error handling and exception management within Internara.

## Purpose

- **Standardization:** Provides base exception classes and translatable messages.
- **i18n:** Manages the `exception::messages` namespace for global error feedback.
- **Security:** Prevents sensitive data leakage in production.

## Key Features

### 1. Base Exception Classes

- **AppException:** Foundational class for all domain errors.
- **Standardized Keying:**
    - Module-specific: `module::exceptions.key`
    - General: `exception::messages.key`

### 2. Standardized Logic

- **Localization Bridge:** Ensures all exception messages are automatically localized (ID/EN) before
  being presented to the user.

---

_The Exception module ensures system resilience and a professional, localized feedback loop._
