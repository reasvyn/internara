# Software Development Life Cycle (SDLC)

This document outlines the standard Software Development Life Cycle (SDLC) adopted by the Internara
project. It provides a high-level framework for delivering high-quality software, ensuring that
every release is planned, designed, built, and tested according to industry best practices.

---

## 1. Phase 1: Planning & Requirement Analysis

**Goal:** Define **"What"** we are building and **"Why"**.

In this phase, we analyze the needs of our stakeholders (Students, Teachers, Industry Mentors) and
translate them into actionable technical requirements.

- **Activities:**
    - **Problem Identification:** Analyzing user pain points (e.g., "Manual attendance is
      unreliable").
    - **Scope Definition:** Grouping requirements into "Keystones" for a specific version.
    - **Feasibility Study:** Assessing technical constraints.
- **Output:**
    - A **Version Plan** document (e.g., `docs/versions/v0.5.x-alpha.md`) detailing Goals, Pillars,
      and Constraints.

## 2. Phase 2: System Design

**Goal:** Define **"How"** we will build it.

Before coding, we establish the architectural blueprint to ensure scalability and maintainability.

- **Activities:**
    - **Architectural Design:** Deciding module boundaries and communication patterns
      (Interfaces/Events).
    - **Database Schema:** Designing Eloquent models and migrations with strict isolation rules (No
      cross-module FKs).
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
    - Strictly follow the **[Development Workflow](development-workflow.md)** for the step-by-step
      coding process.

## 4. Phase 4: Verification & Artifact Synchronization

**Goal:** Ensure the system is robust and that documentation accurately reflects the reality of the
code.

Verification is an iterative loop. Any fix identified during testing must trigger a new cycle of QA
and Artifact Synchronization.

- **Activities:**
    - **Unit & Feature Testing:** Writing `Pest` tests for Services and Livewire components.
    - **Iterative Sync:** Updating technical artifacts (docs/CHANGELOG) after every code tweak.
    - **Security & Privacy Deep-Dive:** Analytical review of data boundaries.
- **Output:**
    - Deep analytical verification notes in the Version Plan.
    - Synchronized technical artifacts across the `docs/` directory.

## 5. Phase 5: Release & Evolution

**Goal:** Package the engineered story for deployment.

The version is finalized through an analytical narrative that bridges the current state with the
next iteration.

- **Activities:**
    - **Narrative Versioning:** Finalizing the deep analytical report for the release.
    - **Unified Release:** Executing the release protocol as the final step of the development
      workflow.
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
