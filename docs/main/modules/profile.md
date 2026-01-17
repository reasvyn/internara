# Profile Module

The `Profile` module handles extended user information and academic integration. It serves as the
primary store for data that distinguishes users beyond their core authentication credentials.

## Purpose

- **Academic Context:** Links users to their respective institutional structures (Departments).
- **Specialized Identification:** Stores role-specific identifiers like **NIP** (for Teachers) and
  **NISN** (for Students).
- **Personal Information:** Manages PII such as phone numbers, addresses, and bios.

## Core Components

### 1. Profile Model

- Stores extended user attributes.
- Uses `HasUserRelation` to link back to the core `User` model.
- Integrated with `HasDepartmentRelation` (from the Department module) to provide academic context.
- Uses **UUIDs** for secure identification.

### 2. Academic Integration

- **Department ID:** The profile stores a `department_id` which is validated against the
  `DepartmentService`.
- **Relationship Trait:** Utilizes the `HasDepartmentRelation` trait for a decoupled link to the
  Department module.

## Technical Details

- **Mass Assignable:** Includes `department_id`, `nip`, `nisn`, `phone`, `address`, and `bio`.
- **Database Isolation:** Adheres to the project convention of manual indexes for cross-module
  relationships (e.g., `user_id`, `department_id`).

---

**Navigation** [← Back to Module TOC](table-of-contents.md)
