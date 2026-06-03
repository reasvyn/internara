# Enrollment Domain

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Consolidated Student Registration and Company Placement Slots

## Purpose

The **Enrollment** domain manages the student participation pipeline for the internship program (PKL). It handles guest applications, program registration, document verification, placement slot management at partner companies, direct assignments, and placement change workflows.

It is the gateway for student participation. No student can clock-in, submit logbooks, or receive grading until they have a verified `Registration` and an assigned `Placement` within an active academic year.

---

## Design Principles

### 1. Application-to-Registration Lifecycle
Student enrollment follows a structured progression:
1. **Guest Application**: Unauthenticated applicants submit their student identifiers, target company details, and school documents.
2. **Review & Approval**: Administrators review applications. Approval automatically creates a `User` account, activates the `Mentee` role, and provisions a `Registration` entry.
3. **Document Verification**: Students upload mandatory files (e.g., parental consent, health certificates) linked to the program requirements. Placements are withheld until files are approved.
4. **Final Verification**: Once verified, the registration transitions to `VERIFIED`, granting the student active status.

### 2. Slot-Based Capacity Tracking
Placements represent available internship positions at host companies:
- Each placement defines a specific `quota` (capacity).
- Allocations are tracked atomically. Assigning a student to a placement increments the used slot count.
- The system prevents overallocation: `quota` limits are strictly enforced, and double placements are blocked.

### 3. Placement Change Workflow
When a placement needs to change (due to company issues or health reasons):
- Students submit a `PlacementChangeRequest` with a detailed justification.
- While pending, the student's current placement remains active.
- **Atomic Swap**: Upon approval, the system atomically releases the student from the old placement slot (decrementing its used count) and assigns them to the new placement slot (incrementing its used count).

---

## Domain Boundary

### Technical Ownership
- **Account Applications**: Forms, validations, and workflows for unauthenticated program applications.
- **Program Registrations**: The multi-step registration wizard, verification flow, and registration state management.
- **Placement Slots**: Managing host company placement quotas, locations, and descriptions.
- **Assignments**: Enforcing capacity constraints, direct assignments, and matching mentors to student placements.
- **Change Requests**: Student requests, justifications, and administrator approval workflows.

### Dependencies
- **Core**: Relies on `BaseModel`, `BaseAction`, `BasePolicy`, and Spatie Media Library.
- **User**: Links registrations to student `User` identities.
- **Partners**: Placements reference company profiles from the Partners domain.
- **Program**: Registrations are targeted at specific `Internship` (PKL program) periods.
- **Guidance**: Mentors are assigned to supervise student placements.

---

## Domain Rules & Invariants

- **R1 — Guest Account Creation**: Approving an application triggers account creation with system-generated usernames and password notifications sent automatically.
- **R2 — Capacity Hard Cap**: A placement slot cannot be assigned to a student if `used_slots >= quota`.
- **R3 — Unique Registration Constraint**: A student can have only one active or pending registration per academic year.
- **R4 — Mandatory Document Uploads**: Registrations cannot be verified as complete until all required documents matching the `DocumentRequirement` specification are uploaded and set to `APPROVED`.
- **R5 — Atomic Placement Swap**: Approving a placement change must release the old slot quota and claim the new slot quota in a single database transaction.
- **R6 — Safe Slot Deletions**: A placement slot cannot be deleted if it has student records assigned to it.

---

## Key Features

- **Guest Application Portal**: Form allowing prospective students to apply to the school's internship programs.
- **Multi-Step Registration Wizard**: Interactive wizard guiding students through program selection, slot selection, and document uploads.
- **Document Verification Hub**: Admins can preview, approve, or reject uploaded PDF/image requirements with inline feedback.
- **Placement Quota Dashboard**: Dynamic grid tracking available, assigned, and remaining internship slots at partner companies.
- **Direct Student Placement**: Admin utility to bypass the wizard and assign students to company slots directly.
- **Placement Change Requests**: Complete loop for students to request re-placement and admins to evaluate and execute the changes.
- **Automatic Mentor Allocation**: Assigns supervising teachers or company supervisors to student placements during assignment.
