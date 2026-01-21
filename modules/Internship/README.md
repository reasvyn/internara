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
  including the number of available slots and an optional assigned **Mentor**.
- **InternshipRegistration:** Records a student's application or placement in a specific internship.
- **InternshipRequirement:** Defines dynamic prerequisites (Documents, Skills, or Conditions) for a
  specific academic year.
- **RequirementSubmission:** Stores student proofs and verification status for requirements.

### 2. Services

- **InternshipPlacementService:** Manages industry quotas, slot allocation, and placement details.
- **InternshipRegistrationService:** Handles student applications and enforces mandatory requirement
  verification before approval.
- **InternshipRequirementService:** Orchestrates the lifecycle of prerequisite submissions and
  verifications.

### 3. Livewire Managers

- **PlacementManager:** Administrative interface for managing industry partners. Features
  **Just-in-Time (JIT)** mentor creation and assignment.
- **InternshipManager:** Handles the overall internship lifecycle and registration review.
- **RequirementManager:** Administrative interface for defining dynamic prerequisites.
- **RequirementSubmissionManager:** Student-facing interface for fulfilling requirements.

## Technical Implementation

### Prerequisite Validation

- Registration approval is gated by the `hasClearedAllMandatoryRequirements` check.
- Supports three types of requirements: `document` (file upload), `skill` (self-rating), and
  `condition` (boolean checklist).

### Capacity Validation

- The `InternshipRegistrationService` performs application-level validation to prevent
  over-allocation of slots.
- It uses the `InternshipPlacementService` to verify current availability before confirming a
  registration.

### Decoupled Schema

- Tables in this module reference `School`, `Department`, and `User` modules using manual indexes on
  UUID/ID columns, adhering to the **Database Isolation Principle**.
