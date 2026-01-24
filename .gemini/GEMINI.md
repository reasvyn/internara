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
- **Linting:** `./vendor/bin/pint`
- **Testing:** `php artisan test --parallel`

---

## Standard Project Workflow

1. **Receive User Input** Carefully analyze the request.

2. **Spec Validation (Critical)** Verify if the request aligns with **`internara-specs.md`**. If it
   contradicts, pause and clarify.

3. **Build Knowledge** Review **Priority Documentation** and **Blueprints**.

4. **Formulate Task Plan (Blueprint)** Create a clear, step-by-step plan aligned with project
   architecture.

5. **Blueprint Synchronization** Ensure major units are backed by an **Application Blueprint** in
   `docs/internal/blueprints/`.

6. **Request User Approval** Present the plan and wait for explicit approval.

7. **Execute Approved Tasks** Implement strictly according to the approved plan, project
   conventions, and **Internara Specs**.

8. **Report and Conclude** Provide a concise Keypoints Summary outlining actions taken.

9. **Commit and Push All Changes (Mandatory)** Ensure _all_ changes are staged and committed with
   professional messages.

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
