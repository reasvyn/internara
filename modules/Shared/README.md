# Shared Module

The `Shared` module is the foundation of the Internara modular architecture. It contains code that
is strictly **business-agnostic** and can be reused across any other module or even different
projects.

> **Spec Alignment:** This module provides the technical infrastructure (UUIDs, Standardized
> Queries) to support the reliable administration required by the
> **[Internara Specs](../../docs/internal/internara-specs.md)**.

## Purpose

- **Universality:** Provides a toolkit of traits, helpers, and base classes.
- **Portability:** Designed to have zero dependencies on business-specific modules.
- **Standardization:** Enforces common behaviors through shared concerns.

## Key Features

### 1. Base Services

- **[EloquentQuery](../../docs/internal/eloquent-query-service.md)**: A standardized implementation
  for model-based services.

### 2. Model Concerns (Traits)

- **[HasUuid](../../docs/internal/shared-concerns.md#1-identity-hasuuid)**: Automatic UUID
  generation (Standard for Internara).
- **[HasStatuses](../../docs/internal/shared-concerns.md#2-state-management-hasstatuses)**:
  Standardized lifecycle management.
- **[HasAcademicYear](../../docs/internal/shared-concerns.md#3-scoping-hasacademicyear)**: Scoping
  data to the active academic cycle.

### 3. Support & Utilities

- **i11n Infrastructure**: Utilities to support Multi-Language requirements.
- **Global Helpers**: Procedural functions for module health and status checks.

### 4. Base Providers

- **[ManagesModuleProvider](../../docs/internal/module-provider-concerns.md)**: Automates module
  bootstrapping.

---

_The Shared module is the engine room of Internara. Keep it clean, portable, and agnostic._
