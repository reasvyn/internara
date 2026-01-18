# Shared Module

The `Shared` module is the foundation of the Internara modular architecture. It contains code that
is strictly **business-agnostic** and can be reused across any other module or even different
projects.

## Purpose

- **Universality:** Provides a toolkit of traits, helpers, and base classes.
- **Portability:** Designed to have zero dependencies on other business modules.
- **Standardization:** Enforces common behaviors (like UUIDs and status tracking) through shared
  concerns.

## Key Features

### 1. Base Services

- **[EloquentQuery](../../docs/main/eloquent-query-service.md)**: A standardized implementation for
  model-based services.

### 2. Model Concerns (Traits)

- **[HasUuid](../../docs/main/shared-traits.md#1-modulessharedmodelsconcernshasuuid)**: Automatic
  UUID generation.
- **[HasStatus](../../docs/main/shared-traits.md#2-modulessharedmodelsconcernshasstatus)**:
  Standardized status lifecycle management.

### 3. Support Utilities

- **Formatter**: Static helpers for path and namespace normalization.
- **Global Helpers**: Procedural functions like `is_active_module()`.

### 4. Base Providers

- **[ManagesModuleProvider](../../docs/main/module-provider-concerns.md)**: A trait to automate
  module bootstrapping.

---

**Navigation** [← Back to Module TOC](../../docs/main/modules/table-of-contents.md)
