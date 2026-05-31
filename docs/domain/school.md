# School Domain

## Purpose

School provides the institutional foundation — schools, departments, and academic years that
define the structural boundaries for all operational domains.

---

## Design Principles

### 1. Singleton School

The school is created once during setup. There is exactly one school per installation.

### 2. Single Active Academic Year

Only one academic year can be active at any time. Activating a new year deactivates all others.

### 3. Department Deletion Guard

Departments with active profiles cannot be deleted — enforced by `DepartmentState` entity.

---

## Domain Boundary

The School domain owns the institutional foundation of the application — the school itself, its departments, and its academic years. This includes the school profile with legal name, code, address, contact information, and logo. Departments are managed with full CRUD operations including search, sort, pagination, and bulk selection. Academic years follow a single-active constraint: activating one academic year deactivates all others, and bulk deletion is supported only for inactive academic years.

School does not own user management, program definitions, placement slots, registration workflows, or any operational domain. It provides the structural context (which school, which department, which academic year) that other domains reference via foreign keys. It does not manage runtime configuration (Settings), authentication (Auth), or administrative functions (Admin).

The domain references user profiles through department affiliations and is referenced by virtually every operational domain (Internship, Registration, Placement, and others) that needs school and department context. School does not control or manage data in those consuming domains — it defines the structural boundaries within which they operate.

---

## Key Features

- Edit the school profile including legal name, code, address, contact information, and institutional logo.
- Create, update, and delete departments with search, sort, pagination, and bulk selection capabilities.
- Create, update, and delete academic years with an enforced single-active constraint across the system.
- Activate an academic year from the system settings interface, automatically deactivating all others.
- Bulk-delete multiple inactive academic years in a single operation.
- Prevent deletion of departments that still have active user profiles associated with them.
- Filter the department list by name or code using a search bar with real-time results.
- Sort department and academic year tables by clicking on column headers.
- Select multiple departments or inactive academic years for batch deletion with a confirmation dialog.
- Receive a flash toast notification confirming successful creation, update, or deletion of departments and academic years.
- Upload a school logo and see an instant preview before saving.
