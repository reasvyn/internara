# Software Development Life Cycle (SDLC)

This document outlines the standard Software Development Life Cycle (SDLC) adopted by the Internara project. It provides a high-level framework for delivering high-quality software, ensuring that every release is planned, designed, built, and tested according to industry best practices.

---

## 1. Phase 1: Planning & Requirement Analysis

**Goal:** Define **"What"** we are building and **"Why"**.

In this phase, we analyze the needs of our stakeholders (Students, Teachers, Industry Mentors) and translate them into actionable technical requirements.

- **Activities:**
    - **Problem Identification:** Analyzing user pain points (e.g., "Manual attendance is unreliable").
    - **Scope Definition:** Grouping requirements into "Keystones" for a specific version.
    - **Feasibility Study:** Assessing technical constraints (e.g., Geolocation accuracy).
- **Output:**
    - A **Version Plan** document (e.g., `docs/versions/v0.5.x-alpha.md`) detailing Goals, Pillars, and Constraints.

## 2. Phase 2: System Design

**Goal:** Define **"How"** we will build it.

Before coding, we establish the architectural blueprint to ensure scalability and maintainability.

- **Activities:**
    - **Architectural Design:** Deciding module boundaries and communication patterns (Interfaces/Events).
    - **Database Schema:** Designing Eloquent models and migrations with strict isolation rules (No cross-module FKs).
    - **UI/UX Design:** Planning the interface using the `UI` module components (MaryUI/DaisyUI).
- **Output:**
    - Implementation Rules in the Version Plan.
    - Drafted Interfaces and Contracts.

## 3. Phase 3: Implementation (Coding)

**Goal:** Translate design into working software.

This is the execution phase where code is written following the project's strict conventions.

- **Activities:**
    - **Modular Development:** Creating Modules, Models, Services, and UI components.
    - **Adherence to Standards:** Following PSR standards, Strict Typing, and Project Conventions.
    - **Refactoring:** Improving code structure without altering behavior.
- **Reference:**
    - Strictly follow the **[Development Workflow](development-workflow.md)** for the step-by-step coding process.

## 4. Phase 4: Testing & Quality Assurance

**Goal:** Ensure the software is bug-free and meets requirements.

Verification is not an optional step; it is a gatekeeper for release.

- **Activities:**
    - **Unit & Feature Testing:** Writing `Pest` tests for Services and Livewire components.
    - **Static Analysis:** Running `composer lint` (Pint/Prettier) to enforce style.
    - **Manual Verification:** Validating UI flows and user experience.
    - **Security Audit:** Checking for IDOR, XSS, and Logic flaws.
- **Output:**
    - Passed Test Suite (Green Build).
    - Completed QA Checklist in the Version Plan.

## 5. Phase 5: Release & Deployment

**Goal:** Deliver the software to the users.

Once verified, the version is packaged and documented for distribution.

- **Activities:**
    - **Versioning:** Assigning a Semantic Version (e.g., `v0.5.0-alpha`) and Series Code.
    - **Changelog:** Updating `CHANGELOG.md` with user-facing notes.
    - **Documentation:** Finalizing technical docs in `docs/main/`.
    - **Merging:** Merging the development branch into the main branch.
- **Reference:**
    - Follow the **[Release Guidelines](release-guidelines.md)** for the deployment protocol.

## 6. Phase 6: Maintenance

**Goal:** Ensure the system remains operational and useful.

- **Activities:**
    - **Bug Fixes:** Addressing issues reported by users (Patch releases).
    - **Performance Tuning:** Optimizing queries and load times.
    - **Updates:** Keeping dependencies (Laravel, Packages) up to date.

---

**Navigation**

[← Previous: Development Conventions](development-conventions.md) |
[Next: Development Workflow →](development-workflow.md)
