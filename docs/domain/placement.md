# Placement Domain

## Purpose

Placement bridges supply (company slots) and demand (students needing host organizations).
Manages slot capacity, direct assignments, and change requests.

---

## Design Principles

### 1. Quota Enforcement

Placement quotas are atomic — never exceed capacity. Increment/decrement operations use
locking to prevent over-allocation under concurrent requests.

### 2. Change Request Workflow

Students request placement changes; admins approve or reject. Each change is logged.

---

## Domain Boundary

The Placement domain owns the bridge between available host-organization slots and students who need placement assignments. It manages placement slots per company per program with quota tracking that enforces capacity limits atomically — increment and decrement operations use locks to prevent over-allocation under concurrent requests. It handles direct assignments where an administrator assigns a student to a specific slot (automatically creating the student's Mentee and Registration records), and it processes placement change requests where students submit reasons for wanting a different host organization.

Placement does not own company profiles or partnership agreements — those belong to the Partnership domain, which defines who the host organizations are and what their agreements specify. It does not own student identities (User), program definitions (Internship), or registration workflow logic (Registration). Placement uses companies, students, and programs as reference data but manages only the slot-to-student assignment relationship and its capacity constraints.

The domain depends on Partnership for company data to create slots, on Internship for program context, and on User for student identity. It delegates the auto-creation of Mentee and Registration records to their respective domains but does not own the lifecycle of those created records after initial provisioning.

---

## Key Features

- Create, update, and delete placement slots per company per program with real-time quota tracking.
- Assign a student directly to a placement slot, automatically creating their mentee and registration records.
- Submit placement change requests with a reason when a student wants to move to a different host organization.
- Review, approve, or reject placement change requests from students.
- Enforce atomic quota increments and decrements so slot capacity is never exceeded under concurrent access.
- Filter placement slots by company or program using dropdown selectors.
- Search placement slots by company name or program name with a text filter bar.
- View real-time quota counters showing filled and available slots for each company row.
- Review, approve, or reject placement change requests with a confirmation dialog and an optional rejection reason input.
- Receive a flash toast notification when a slot is created, a student is assigned, or a change request is processed.
