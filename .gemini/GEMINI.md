# Gemini Guidelines: Internara Project

This document outlines the core principles, project context, and operational guidelines for the AI
assistant (“Gemini”) when working on the Internara project. These guidelines ensure consistency,
architectural clarity, high code quality, and efficient collaboration.

> **Single Source of Truth Mandate:** The document **`docs/internal/internara-specs.md`** is the
> **Immutable, Authoritative Specification** for this project. All architectural decisions, code
> conventions, and lifecycle events MUST align with it.

---

## Project Overview

**Internara** is an internship management system built with **Laravel Modular Monolith (Modular
MVC)**, where business rules are centralized in a **service-oriented business logic layer**.

The architecture prioritizes clarity, maintainability, and pragmatic separation of concerns by
leveraging Laravel’s native MVC conventions while intentionally avoiding unnecessary abstraction
layers.

---

## Interaction Principles

As an AI assistant, you operate under the following identity and interaction principles.

### Interaction Guidelines

- **Aesthetic-Natural Tone** Maintain a calm, adaptive, and structured tone focused on clarity,
  efficiency, and cognitive ease.

- **Keypoints Summary** Always conclude interactions with a concise, scannable summary of key
  information, decisions, and outcomes.

- **Privacy First** Never store, write, or engage with personal or sensitive information. Actively
  refuse such requests.

- **Limited Initiative** Do not take initiatives too far, especially regarding lifecycle status
  changes (e.g., marking a version as "Released") or major strategic decisions without explicit user
  instruction.

---

## Core Architecture Philosophy

- The project adopts **Modular Monolith**, not strict Domain-Driven Design or Clean Architecture.
- **Eloquent Models** represent persisted domain data and may contain limited, domain-relevant
  behavior.
- **Services** are the primary holders of business logic and orchestration.
- **Controllers and Livewire components remain thin**, focusing only on request handling and UI
  concerns.
- Abstractions such as Repositories, Entities, or internal DTOs are **not introduced by default**.

---

## Project-Specific Workflow & Principles

These directives guide all technical workflows for the Internara project.

### Core Workflow Principles

- **Planning First (SDLC Phase 2)** Always formulate a detailed plan (Blueprint) derived from
  `internara-specs.md`, present it to the user, and obtain explicit approval before starting any
  implementation.

- **English Only** All code, documentation, comments, and communication must be written entirely in
  **English**.

- **Open Source Commitment** As this is an OSS project, never use or integrate any paid enterprise
  services (e.g., paid APIs, proprietary software) in its development or features.

- **Directory Exclusion** Always exclude `vendor` and `node_modules` directories when running shell
  commands, scanning, or using other tools to ensure efficiency and focus on project-specific code.

- **Professional PHPDoc** Every class and method must include concise and professional PHPDoc in
  English.

- **Mandatory Documentation (Phase 4)** Every new feature, standardized pattern, or significant
  technical change must be accompanied by comprehensive documentation. Internara follows a
  **Doc-as-Code** principle.

- **Iterative Sync Cycle** Every technical modification triggers a full cycle of Quality Assurance
  (Testing, Linting, Security) and Documentation Synchronization.

- **User-Facing Release Notes** Version notes MUST be written as **Friendly, User-Centric
  Narratives** linking back to **Spec Milestones**.

- **Release Management** Strict adherence to the
  **[Release Guidelines](docs/internal/release-guidelines.md)** is mandatory.

- **Cross-check Project Documentation** Always cross-reference and adhere to existing project
  documentation. For comprehensive navigation, prioritize:
    - [docs/internal/internara-specs.md](docs/internal/internara-specs.md) (**SSoT**)
    - [docs/internal/table-of-contents.md](docs/internal/table-of-contents.md)
    - [docs/internal/architecture-guide.md](docs/internal/architecture-guide.md)
    - [docs/internal/development-workflow.md](docs/internal/development-workflow.md)
    - [docs/internal/software-lifecycle.md](docs/internal/software-lifecycle.md)
    - [docs/internal/release-guidelines.md](docs/internal/release-guidelines.md)
    - [docs/internal/development-conventions.md](docs/internal/development-conventions.md)
    - [docs/internal/blueprints-guidelines.md](docs/internal/blueprints-guidelines.md)
    - [docs/internal/ui-ux-development-guide.md](docs/internal/ui-ux-development-guide.md)

## Pinned Commands

Quick reference for essential project verification:

- **Identity & Status:** `php artisan app:info`
- **Linting:** `composer lint`
- **Testing:** `composer test`

---

## Standard Project Workflow

1. **Contextual Immersion**
    - Study the project's **Architecture**, **Documentation**, and **Codebase Structure**.
    - Understand the foundational philosophy (Modular Monolith) and key constraints.

2. **Blueprint & Version Alignment**
    - Review the **Active Version's Blueprints** and specific development tasks.
    - Ensure your understanding aligns with the current roadmap and milestones.

3. **Targeted Preparation**
    - Identify and study specific **Documentation** related to the task.
    - Analyze the relevant parts of the **Codebase** (Services, Modules, Tests) _before_ starting
      any work.

4. **Interactive & Gradual Planning**
    - Formulate a step-by-step plan.
    - **Involve the user** in key decisions. ask clarifying questions.
    - Present the plan for approval. **Do not proceed without a mandate.**

5. **Strict Execution**
    - Once approved, execute the development steps gradually, thoroughly, and carefully.
    - Strictly follow the guidelines in
      **[`docs/internal/development-workflow.md`](docs/internal/development-workflow.md)**.
    - Maintain strict module isolation and coding standards.

6. **Verification & Closure**
    - Verify work via **Tests** (`composer test`) and **Linting** (`composer lint`).
    - **Commit and Push** all changes with professional messages.
    - Report the outcome with a concise summary.

---

## Foundational Technical Context

### Stack & Versions

- **PHP:** 8.4+
- **Laravel:** v12
- **TALL Stack:** Tailwind CSS v4, Alpine.js, Laravel 12, Livewire 3 (with Volt).
- **Testing:** Pest v4.

### Modular Structure

- **Root:** `modules/` (not `app/Modules`).
- **Isolation:** Modules must be portable. No hard dependencies on other modules' concrete classes.
- **Database:** UUIDs for primary keys. No physical foreign keys between modules.

### Namespace Convention

Namespaces **must omit the `src` segment**:

- _Correct:_ `namespace Modules\User\Services;`
- _Location:_ `modules/User/src/Services/UserService.php`

---

## Technical Conventions

### Multi-Language Support (i11n)

- The application must be built as **multi-language** (EN/ID).
- **Hard-coding text is PROHIBITED.** Use `__('key')`.

### Service Layer

- **Role:** The "Brain" of the application.
- **Pattern:** **Contract-First**.
- **Constraint:** Never call `env()`. Use `setting()` for application values (brand, logo, title).

---

## Testing Standards

**Reference:** [`docs/internal/testing-guide.md`](docs/internal/testing-guide.md)

- **Philosophy:** TDD First. Tests must demonstrate compliance with **Internara Specs**.
- **Modular Isolation:** Tests in Module A must **not** touch concrete classes in Module B. Use
  Contracts.
- **Placement:**
    - **Feature:** `modules/{Module}/tests/Feature/`
    - **Unit:** `modules/{Module}/tests/Unit/`
- **Mandatory Verification:**
    - **Localization:** Test all user-facing text in **ID** and **EN**.
    - **RBAC:** Explicitly test allowed AND denied roles.
    - **Exceptions:** Verify exact translated exception messages.
- **Tooling:** Pest v4. Run via `php artisan test --parallel`.

---

## GitHub Operations (gh-cli)

You are authorized and encouraged to use the **GitHub CLI (`gh`)** to manage the project's presence
on GitHub.

- **Comprehensive Synchronization**: When the user requests "GitHub synchronization" (or similar), 
  you must perform a full synchronization sweep. This includes:
    - **Repository**: Syncing branches (push/pull).
    - **Tags**: Ensuring local and remote tags are identical.
    - **Releases**: Verifying release notes and assets match the current version.
    - **PRs & Issues**: Checking for active pull requests or issues that need status updates 
      aligned with the current work.

---

## Leveraging Laravel Boost Tools

Use the integrated Laravel Boost tools to support efficient development:

- **Artisan Commands:** `list-artisan-commands`
- **URL Generation:** `get-absolute-url`
- **Debugging:** `tinker`, `database-query`, `browser-logs`, `last_error`.
- **Documentation Search:** Prioritize `search-docs`.

for more info: [AGENTS.md](../AGENTS.md)
