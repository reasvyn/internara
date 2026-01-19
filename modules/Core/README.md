# Core Module

The `Core` module contains application-specific infrastructure, foundational configurations, and
custom development tooling. Unlike the `Shared` module, the `Core` module is tailored to Internara's
specific business model and architecture.

## Purpose

- **Infrastructure:** Manages project-wide settings and foundational services.
- **Localization Middleware:** Handles persistence of user language preferences.
- **Tooling:** Provides custom Artisan commands to speed up development.

## Key Features

### 1. Localization Infrastructure

- **SetLocale Middleware:** Automatically sets the application locale based on user session or
  preference.
- **Fail-safe Logic:** Ensures the application defaults to a safe locale (`id` or `en`) if no
  preference is found.

### 2. Development Tooling

- **Custom Generators:** Extended commands for creating modular classes, interfaces, and traits.
    - `php artisan module:make-class`
    - `php artisan module:make-interface`
    - `php artisan module:make-trait`

### 3. Core Functions

- **Fail-safe Setting Helper:** Provides a global `setting()` function with a direct-read fallback
  from `modules_statuses.json` to prevent fatal errors during early bootstrapping or when the
  database is unavailable.
