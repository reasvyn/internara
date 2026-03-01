# Media Module

The `Media` module provides centralized management for file uploads and digital assets within the
Internara ecosystem. It leverages `spatie/laravel-medialibrary` to handle file associations while
ensuring systemic consistency through standardized traits and identities.

> **Governance Mandate:** This module implements the requirements defined in the authoritative
> **[System Requirements Specification](../../docs/dev/specs.md)**. All implementation must adhere
> to the **[Coding Conventions](../../docs/dev/conventions.md)**.

---

## 1. Architectural Role

As a **Public Module**, the `Media` module provides the necessary infrastructure for all domain
modules to attach, process, and retrieve digital files (e.g., Avatars, Certificates, Reports)
without repeating implementation logic.

---

## 2. Core Components

### 2.1 Persistence Layer

- **`Media` Model**: Extends Spatie's base Media model to support **UUID v4** identities and
  module-specific metadata.

### 2.2 Global Concerns

- **`InteractsWithMedia`**: A standardized trait for Eloquent models that provides simplified
  methods for handling media collections.
    - _Features_: Automated clearing of existing media, standardized collection names, and
      simplified URL retrieval.

---

## 3. Engineering Standards

- **Zero Magic Values**: Utilizes `COLLECTION_*` constants for all media collection names.
- **Identity Invariant**: All media records are identified by a unique UUID.
- **Decoupled Processing**: Leverages Laravel's queue system for image manipulations and conversions
  to preserve application performance.

---

## 4. Verification & Validation (V&V)

Quality is ensured through **Pest v4**:

- **Unit Tests**: Verifies media attachment lifecycles, collection clearing logic, and URL
  generation.
- **Standards Compliance**: All code is verified against **PSR-12** and **Laravel Pint**.
- **Command**: `php artisan test modules/Media`

---

_The Media module ensures that all digital assets in Internara are handled with the same rigor and
security as core business data._
