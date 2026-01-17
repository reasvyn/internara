# Gemini Guidelines: Internara Project

This document outlines the core principles, project context, and operational guidelines for the AI
assistant (“Gemini”) when working on the Internara project. These guidelines ensure consistency,
architectural clarity, high code quality, and efficient collaboration.

---

## Project Overview

**Internara** is an internship management system built with **Laravel Modular Monolith (Modular
MVC)**, where business rules are centralized in a **service-oriented business logic layer**.

The architecture prioritizes clarity, maintainability, and pragmatic separation of concerns by
leveraging Laravel’s native MVC conventions while intentionally avoiding unnecessary abstraction
layers such as standalone Entities, Repositories, or internal DTOs unless explicitly justified by
clear boundary or integration requirements.

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

---

## Core Architecture Philosophy

- The project adopts **Modular Monolith**, not strict Domain-Driven Design or Clean Architecture.
- **Eloquent Models** represent persisted domain data and may contain limited, domain-relevant
  behavior.
- **Services** are the primary holders of business logic and orchestration.
- **Controllers and Livewire components remain thin**, focusing only on request handling and UI
  concerns.
- Abstractions such as Repositories, Entities, or internal DTOs are **not introduced by default**
  and must be explicitly justified by concrete architectural needs (e.g. external integrations,
  async boundaries, or storage substitution).

---

## Project-Specific Workflow & Principles

These directives guide all technical workflows for the Internara project. For detailed architectural
and development documentation, always refer to the project’s `/docs` directory.

### Core Workflow Principles

- **Planning First** Always formulate a detailed plan, present it to the user, and obtain explicit
  approval before starting any implementation.

- **English Only** All code, documentation, comments, and communication must be written entirely in
  **English**.

- **Professional PHPDoc** Every class and method must include concise and professional PHPDoc in
  English. Every method and function must have a PHPDoc that clearly describes its intent,
  parameters, and return values.

- **Mandatory Documentation** Every new feature, standardized pattern, or significant technical
  change must be accompanied by comprehensive documentation in the `docs/` directory. Documentation
  is a primary artifact and a prerequisite for task completion.

- **Release Management** For all versioning, changelog updates, and release protocols, strict
  adherence to the **[Release Guidelines](docs/main/release-guidelines.md)** is mandatory.

- **Proactive Renaming & Restructuring** You are authorized to propose renaming files, folders,
  classes, methods, interfaces, traits, attributes, parameters, keys, or even entire modules if the
  current naming is inaccurate or suboptimal.
    - **Mandatory Approval:** You must explicitly ask for and receive user approval before applying
      these changes.
    - **Thoroughness:** Changes must be comprehensive. Do not leave residual code, old imports, or
      dead references.
    - **Breaking Changes:** Mark such changes as **BREAKING CHANGES** in your plan if they impact
      backward compatibility.
    - **Documentation Update:** You must immediately update any related documentation to reflect
      these changes.

- **Cross-check Project Documentation** Always cross-reference and adhere to existing project
  documentation (especially within the `/docs` directory). For comprehensive navigation, prioritize:
    - [docs/table-of-contents.md](docs/table-of-contents.md)
    - [docs/main/architecture-guide.md](docs/main/architecture-guide.md)
    - [docs/main/modular-monolith-workflow.md](docs/main/modular-monolith-workflow.md)
    - [docs/main/main-documentation-overview.md](docs/main/main-documentation-overview.md)
    - [docs/main/development-conventions.md](docs/main/development-conventions.md)
    - [docs/main/development-lifecycle-guide.md](docs/main/development-lifecycle-guide.md)
    - [docs/versions/versions-overview.md](docs/versions/versions-overview.md)
    - [docs/versions/v0.1.x-alpha.md](docs/versions/v0.1.x-alpha.md)

---

## Standard Project Workflow

1. **Receive User Input** Carefully analyze the request, identifying explicit instructions, goals,
   and implied context.

2. **Study Instructions** Review all provided guidelines, project documentation, and task-specific
   requirements.

3. **Build Knowledge (Targeted Context & History)**
    - Start by reading the **Priority Documentation** listed above.
    - **Historical Review:** Examine the current and at least **2 previous version documents** in
      `docs/versions/`. This provides the necessary historical scope to ensure development is
      sustainable and aligned with the project's evolution.
    - Conclude which specific modules, classes, or documentation files are relevant to the task.
    - **Do not** attempt to study the entire documentation or codebase at once. Focus on
      "Just-in-Time" context to maintain flexibility and avoid performance/inconsistency issues.
    - Analyze surrounding code patterns without performing modification actions during this phase.

4. **Formulate Task Plan** Create a clear, step-by-step plan aligned with project architecture and
   best practices.

5. **Request User Approval** Present the plan and wait for explicit approval before implementation.

6. **Execute Approved Tasks** Implement strictly according to the approved plan, project
   conventions, and coding standards.

7. **Report and Conclude** Provide a concise Keypoints Summary outlining actions taken, decisions
   made, and outcomes achieved.

8. **Commit and Push All Changes (Mandatory)** When instructed to commit and push all, ensure _all_
   changes in the codebase (including those not directly made by Gemini) are staged and committed.
   Craft professional commit messages by thoroughly reviewing _all actual_ changes in the codebase.

---

## Knowledge Construction Principles

- **Priority-First:** Always start with the core documentation. It provides the "Source of Truth"
  for the project's identity.
- **Selective Contextualization:** Deep-dive only into the relevant domain modules. Ingesting too
  much context can lead to rigid responses and inability to handle "special cases" that may require
  pragmatic deviations from convention.
- **The Balance:** Prioritize Internara-specific conventions, but balance them with **Global
  Industry Standards** (e.g., PSR, SOLID, Clean Code). The codebase should remain universal and
  accessible to entry-level developers.
- **Informed Flexibility:** While conventions are rules, architecture should not be a prison. If a
  task requires a "convention break" for a better technical outcome, justify it clearly in the
  planning phase.

---

## Foundational Technical Context

...

### Namespace Convention

Namespaces **must omit the `src` segment**:

- _Correct:_ `namespace Modules\User\Services;`
- _Location:_ `modules/User/src/Services/UserService.php`

---

## Technical Conventions

### Multi-Language Support

- The application must be built as **multi-language** from the start.
- Supported locales: **English (`en`)** and **Indonesian (`id`)**.
- All user-facing strings, exceptions, and validation messages must use translation keys.

### Service Layer

...

---

## GitHub Operations (gh-cli)

You are authorized and encouraged to use the **GitHub CLI (`gh`)** to manage the project's presence
on GitHub. This ensures that the development process is transparent and well-integrated with
GitHub's ecosystem.

### Scope of Operations

- **Pull Requests (PR):** Create, list, check status, and draft PRs for completed tasks. Every
  significant change or version completion should be accompanied by a PR.
- **Issues:** Create and manage issues to track bugs, features, and technical debt.
- **Repository Management:** Monitor repository status and perform necessary repository-level
  configurations.
- **GitHub Project:** Actively manage tasks within the **'Internara Project'** (GitHub Project).
  Ensure issues and PRs are correctly linked to the project board.
    - **Project ID:** `PVT_kwHOA9rvKM4A7HFm` (Number: 2)
- **Workflows:** Monitor GitHub Actions workflows to ensure CI/CD pipelines are passing.
- **Documentation Synchronization:** Ensure all local documentation updates are pushed and
  synchronized with the GitHub repository, maintaining it as the central source of truth.

---

## Leveraging Laravel Boost Tools

Use the integrated Laravel Boost tools to support efficient development:

- **Artisan Commands:** `list-artisan-commands`
- **URL Generation:** `get-absolute-url`
- **Debugging:** `tinker`, `database-query`, `browser-logs`, `last_error`.
- **Documentation Search:** Prioritize `search-docs` for Laravel-ecosystem docs. Use
  `search_file_content` or `glob` for project-local docs under `/docs`.
