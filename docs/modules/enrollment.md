# Enrollment — Registration, Placement & Change Requests

> **Last updated:** 2026-07-11 **Changes:** sync — initial metadata sync with new format

## Description

Student registration into internship programs, placement slot assignment, document upload
verification, guest applications, and placement change requests.

## Purpose & Boundary

Enrollment manages the complete student onboarding pipeline: from guest application (unauthenticated
intent-to-register), through admin review and user provisioning, to active registration with company
slot placement. It also handles mid-program placement changes when workplace conflicts arise.

Out of scope: internship program definitions (Program), daily activity tracking (Journals), grade
assessment (Assessment).

## Submodules

### Registration

Core enrollment record linking a student user to an Internship program. Tracks program start/end
dates, placement assignments, cohort group membership, document compliance status, and overall
enrollment state. Status drives dashboard access: active students see program features;
archived/pending students see restricted views.

### AccountApplication

Guest application form for unauthenticated prospective students. Captures full name, email, phone,
chosen program, and department. Admin review triggers atomic provisioning: creates User account with
activation token, generates Registration record, and assigns initial placement. Duplicate pending
applications from the same email are blocked.

### RegistrationDocument

Document submission verification against program-required document templates. Students upload
required files; admin verifies compliance. Each document is linked to a program requirement and
tracked with upload status and verification timestamp.

### Placement

Company slot assignment linking a student to a specific company within a program. Each placement
consumes one slot quota from a company's partnership capacity. Slot quota uses database-level
locking for atomic capacity enforcement. Direct placement by admin creates registration + placement
in a single transaction.

### PlacementChangeRequest

Student-initiated workflow for requesting a company slot change due to workplace conflicts,
distance, or other reasons. Admin reviews and approves/rejects. Approved changes atomically
decrement old slot quota and increment new slot quota.

## Key Concepts

### Capacity Atomicity

Placement slot quotas are enforced at the database level using pessimistic locking. This ensures two
concurrent requests cannot oversell a company slot. Capacity checks happen within the same
transaction as the placement creation.

### Guest-to-Student Pipeline

Unauthenticated users submit an AccountApplication. On admin approval, the system auto-creates a
User (with activation token), Registration, and initial Placement in a single atomic transaction.
The applicant receives an activation email to set their password.

### Status-Driven Access

A student's enrollment status determines their feature access within a program. Active enrollments
unlock journals, assignments, and assessments. Archived or pending enrollments present read-only or
restricted views.

## Dependencies

- Core (base classes)
- User (student identity)
- Program (internship specifications)
- Academics (department and calendar context)
- Partners (company slots)

## Used By

- Journals (activity context per registration)
- Assessment (grading per registration)
- Reports (grade card per registration)
- Journals (monitoring visits per registration)
