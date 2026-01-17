# Module Inventory - Table of Contents

This document provides a comprehensive list of all modules within the Internara project, categorized
by their architectural role.

## 1. Foundation Modules

Core infrastructure and project-agnostic utilities.

- **[Shared](shared.md)**: Universal traits, helpers, and base services.
- **[Core](core.md)**: Project-specific infrastructure and Artisan generators.
- **[Support](support.md)**: Infrastructure integration and third-party wrappers.
- **[Exception](exception.md)**: Centralized error handling and localization.

## 2. System Services

Cross-cutting concerns and shared business services.

- **[Auth](auth.md)**: Authentication, registration, and email verification.
- **[Permission](permission.md)**: Role-Based Access Control (RBAC).
- **[Profile](profile.md)**: User profile management and polymorphic data.
- **[User](user.md)**: Centralized user identity and administrative management.
- **[Setting](setting.md)**: Global application configuration management.
- **[Log](log.md)**: User activity and system monitoring.
- **[Media](media.md)**: File upload and media library management.
- **[Notification](notification.md)**: Centralized system notifications.
- **[Setup](setup.md)**: Initial deployment and configuration wizard.

## 3. UI & Presentation

Modules dedicated to visual elements and user interaction.

- **[UI](ui.md)**: Global design system, assets, and shared Blade components.
- **[Dashboard](dashboard.md)**: Role-based landing pages, widgets, and analytics.

## 4. Domain Modules

The business logic heart of the application.

- **[School](school.md)**: Educational institution identity and settings.
- **[Department](department.md)**: Academic departments and specializations.
- **[Internship](internship.md)**: Core internship lifecycle and registration.

---

[← Back to Main Documentation](../table-of-contents.md)
