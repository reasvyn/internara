# User Module

The `User` module is responsible for the core identity system of Internara. it manages user
accounts, administrative operations, and high-level account security.

## Purpose

- **Identity Management:** Acts as the primary source of truth for user authentication data.
- **Administration:** Provides tools for managing users and their roles.
- **Security:** Safeguards the system owner (SuperAdmin) and handles account status toggles.

## Key Features

### 1. Account Services

- **SuperAdminService**: Specialized logic for protecting and managing the system owner.
- **UserService**: General user lifecycle management (CRUD).

### 2. Management UI

- **[Administrative CRUD](../../docs/versions/v0.3.x-alpha.md#31-the-user-module-administrative)**:
  Livewire-based index and form for account management.
- **Status Toggle**: Quick activation/deactivation of accounts directly from the UI.

---

**Navigation** [← Back to Module TOC](../../docs/main/modules/table-of-contents.md)
