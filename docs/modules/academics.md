# Academics — School Profile, Departments & Academic Years

> **Last updated:** 2026-07-21 **Changes:** sync — replace ConflictException with
> RejectedException

## Description

Institutional structure: school profile, academic departments (majors), and academic calendar
management.

## Purpose & Boundary

Academics defines the school's organizational and calendar structure. It manages departments
(academic divisions), academic years (calendar periods), and the school profile entity. Departments
scope teacher and student assignments. Academic years scope internship programs and enrollment
periods. The school profile is stored in Settings as `school.*` keys.

Out of scope: internship program definitions (Program), student enrollment (Enrollment), class
scheduling (Journals).

## Submodules

### School

School profile entity (Entity + Livewire editor only — no standalone model). Managed as `school.*`
settings keys (school name, NPSN, principal name, address, phone, email). Single-tenant assumption:
one school per installation.

### Department

Academic majors/divisions with CRUD management. Departments organize teachers, students, and
internship programs. Guarded deletion: cannot delete a department with active programs, assigned
users, or active placements. Returns a `RejectedException` listing blocking records.

### AcademicYear

School calendar periods with start/end dates and a single-active constraint. Activating a new year
automatically deactivates all others. Years cannot overlap. Start date must precede end date. CLI
command `academics:activate-year {year}` for programmatic activation.

## Key Concepts

### Single-Tenant School Context

The application runs once per school. All school-wide attributes (legal name, NPSN, principal name,
address) are stored in the Settings key-value store under the `school.*` namespace, not as a
dedicated database table. This eliminates table sprawl for a fundamentally single-instance concept.

### Active Year Singleton

Exactly one academic year can be active at any time. This invariant is enforced at the model level
(deactivating others on activation) and at the database level (unique constraint on active flag).
All time-scoped features — programs, enrollments, reports — reference the active year by default.

## Dependencies

- Core (base classes, SmartLogger)
- Settings (school profile storage)

## Used By

- Program (internship scoping by academic year)
- Enrollment (registration scoping by academic year)
- User (department assignment for teachers and students)
