# Internship Module

The `Internship` module is the core business heart of the application. It manages the entire
internship lifecycle, from placement availability to student registration and tracking.

## Purpose

- **Capacity Tracking:** Manages industry placements and their limited quotas (slots).
- **Registration Workflow:** Facilitates student applications for available placements.
- **Workflow Orchestration:** Coordinates the process of matching students to industry partners.

## Core Components

### 1. Internship Models

- **InternshipPlacement:** Represents an available internship opportunity at an industry partner,
  including the number of available slots.
- **InternshipRegistration:** Records a student's application or placement in a specific internship.

### 2. Services

- **InternshipPlacementService:** Manages industry quotas, slot allocation, and placement details.
- **InternshipRegistrationService:** Handles student applications, ensuring registrations do not
  exceed available slots.

### 3. Livewire Managers

- **PlacementManager:** Administrative interface for managing industry partners and available slots.
- **InternshipManager:** Handles the overall internship lifecycle and registration review.

## Technical Implementation

### Capacity Validation

- The `InternshipRegistrationService` performs application-level validation to prevent
  over-allocation of slots.
- It uses the `InternshipPlacementService` to verify current availability before confirming a
  registration.

### Decoupled Schema

- Tables in this module reference `School`, `Department`, and `User` modules using manual indexes on
  UUID/ID columns, adhering to the **Database Isolation Principle**.

---

**Navigation** [← Back to Module TOC](../../docs/main/modules/table-of-contents.md)
