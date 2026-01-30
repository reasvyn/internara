# Core Module

The `Core` module contains application-specific infrastructure, foundational configurations, and
custom development tooling. Unlike the `Shared` module, the `Core` module is tailored to Internara's
specific business model and architecture.

> **Spec Alignment:** This module implements the infrastructure for user roles, multi-language
> support, and dynamic settings required by the
> **[Internara Specs](../../docs/internal/internara-specs.md)**.

## Purpose

- **Infrastructure:** Manages project-wide settings and foundational services.
- **Dynamic Settings:** Authoritative source for the `setting()` helper, preventing hard-coding of
  brand names, logos, and site titles.
- **Localization Middleware:** Handles persistence of user language preferences (ID/EN).

## Key Features

### 1. Localization Infrastructure

- **Middleware:** Automatically sets the application locale based on user preference.
- **Language Support:** Enforces the dual-language requirement (Indonesian & English).

### 2. Development Tooling

- **Modular Generators:** Custom Artisan commands to scaffold components following Internara's
  conventions (e.g., `module:make-service`, `module:make-model`).

### 3. Application Identity

- **Dynamic Configuration:** Centralizes management of `brand_name`, `brand_logo`, and other
  application-level configurations as mandated by the conventions and rules.

---

_The Core module is the glue of Internara. It connects architectural rules with business reality._
