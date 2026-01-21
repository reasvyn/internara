# Project Overview

**Internara** is a high-performance Internship Management System engineered with a **Modular
Monolith** architecture on top of the Laravel framework. It is designed to centralize, automate, and
secure the entire internship lifecycle—bridging the gap between students, academic supervisors, and
industry mentors within a single, cohesive ecosystem.

This `docs` directory serves as the **Authoritative Source of Truth** for the project. It contains
everything from high-level vision statements to granular technical specifications, ensuring that
every developer can contribute with confidence and architectural alignment.

---

## 1. Vision & Strategic Purpose

### The Vision

To deliver a **minimalist, highly structured, and resilient** platform for vocational internship
(PKL) governance. We prioritize "Convention over Configuration" and pragmatic modularity to ensure
that the system remains maintainable even as it scales in complexity.

### The Problem Space

Vocational internships often suffer from a "visibility gap." Placements are frequently misaligned,
student progress is tracked via fragmented manual logs, and assessments are often subjective or
delayed. This leads to a loss of academic legitimacy and prevents the internship from being a
meaningful signal of student competency.

Internara solves this by providing:

- **Integrated Planning**: Centralized placement and requirement management.
- **Continuous Observation**: Real-time daily journals and attendance tracking.
- **Evidence-Based Evaluation**: Structured assessments mapped to specific competency indicators.

---

## 2. Technical Stack & Architectural Pillars

### The Modern TALL Stack

Internara leverages the power of the **TALL Stack**, chosen for its rapid development cycle and deep
integration with the PHP ecosystem:

- **Laravel (v12)**: The backbone of the application, providing robust routing, Eloquent ORM, and
  enterprise-grade security features.
- **Livewire (v3)**: Enables building reactive, dynamic interfaces using pure PHP, significantly
  reducing the cognitive load of managing a separate frontend framework.
- **Alpine.js**: Handles lightweight client-side interactions (dropdowns, toggles) with zero
  overhead.
- **Tailwind CSS (v4)**: A utility-first CSS framework that ensures our UI remains consistent,
  performant, and easy to refactor.
- **UI Components**: We use **MaryUI** (for complex Livewire interactions) and **DaisyUI** (for
  semantic CSS components) to maintain a professional, accessible aesthetic.

### Architectural Pillars

The system is built on three non-negotiable architectural principles:

1.  **Modular Monolith**: Utilizing `nwidart/laravel-modules`, we encapsulate business domains
    (e.g., `User`, `Attendance`, `Assessment`) into self-contained modules. This allows for high
    cohesion within modules and low coupling between them.
2.  **Strict Layered Communication**: Communication between modules is strictly governed. A module
    **must never** reference another module's concrete implementation. Interaction happens
    exclusively through **Contracts (Interfaces)**, **Events**, or framework-level abstractions like
    **Policies/Gates**.
3.  **Domain-Driven Logic**: We follow a strict internal layering:
    - **UI Layer (Livewire/Volt)**: Handles presentation and user events.
    - **Service Layer (EloquentQuery)**: The "Brain" where all business orchestration resides.
    - **Data Layer (Eloquent)**: Manages persistence and domain relationships.

---

## 3. Documentation Ecosystem

### 3.1. Main Engineering Guides (`docs/main/`)

The primary source for developers. This section covers the "How-To" of our system:

- **[Main Documentation Overview](main/main-documentation-overview.md)**: A map of the entire
  technical landscape.
- **Architecture & Workflow**: Detailed guides on how to build and integrate new features.

### 3.2. Versioning & Technical Narratives (`docs/versions/`)

Analytical records of the system's state at specific milestones, organized into subdirectories
(`releases/`, `unreleases/`).

- **[Version Overview](versions/versions-overview.md)**: Tracks support status and release history.
- **Version Notes**: Deep-dives into the technical "Why" behind every version.

### 3.3. Engineering Plans (`docs/internal/plans/`)

Pre-development architectural blueprints and implementation roadmaps for future or active
development series.

### 3.3. Quality Assurance (`docs/tests/`)

Comprehensive guides on maintaining system integrity through automated testing.

- **Test Suite**: Documentation for Unit, Feature, and Browser testing using **Pest**.

---

## 4. Documentation Standards (The Writing Guide)

To maintain a "Developer-First" documentation culture, all contributors must adhere to these
principles:

1.  **Single Source of Truth (SSOT)**: Each concept belongs to **one** authoritative document. If
    you need to mention a rule defined elsewhere, use a concise summary and a **hyperlink**. _Do not
    duplicate logic._
2.  **Aesthetic-Natural Principle**: Documentation should be calm and structurally minimalist. Use
    clear headers, bullet points, and avoid "wall of text" paragraphs.
3.  **Technical Depth & Rationale**: Don't just say _what_ to do; explain _why_ it is done that way.
    This helps other developers understand the architectural intent.
4.  **Actionable Language**: Use direct, developer-centric language. Include code snippets or CLI
    commands where they provide immediate value.
5.  **Iterative Artifact Synchronization**: Documentation is not an afterthought. A feature is only
    "Done" when its corresponding documentation accurately reflects the new system state.
6.  **Formatting Conventions**:
    - Use Markdown (`.md`) exclusively.
    - Use code blocks with language hints (e.g., ` ```php `, ` ```bash `).
    - Always include a "Table of Contents" for long-form guides.
    - **English Only**: All documentation must be written in English.

---

_This guide ensures that Internara remains a sustainable, high-quality open-source project. Follow
these principles to help us build a better internship management experience._
