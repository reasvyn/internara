# Comprehensive Engineering Protocol: Construction & Implementation

This document establishes the authoritative **Development Protocol** for the Internara project,
adhering to **ISO/IEC 12207** (Software Construction) and **PSR-12** standards.

---

## ⚖️ Core Mandates & Prohibitions (The Construction Laws)

The Agent must adhere to these invariants during every line of code implementation.

### 1. Construction Invariants

- **TDD-First Mandate**: Logic implementation MUST be preceded by the construction of verification
  suites (Pest v4).
- **Branch Sovereignty**: Construction MUST occur on a dedicated branch following the
  `feature/{module}/{description}` or `hotfix/{description}` pattern.
- **Traceable Commits**: Utilize **Conventional Commits** (`type(scope): description`) referencing
  the Issue or Blueprint (e.g., `feat(user): add identity encryption. Refs #123`).
- **No Batch Actions**: Executing massive, automated batch replacements or bulk modifications across
  multiple modules simultaneously is **STRICTLY PROHIBITED**. Changes MUST be atomic and focused on
  the current domain to prevent destructive systemic drift.
- **The `src` Omission**: All PHP namespaces MUST omit the `src` segment (e.g.,
  `Modules\User\Models`).
- **Strict Typing**: All PHP files MUST declare `declare(strict_types=1);`.
- **Finality Invariant**: All support, helper, and utility classes MUST be declared as **`final`**.

### 2. Logic & Security Laws

- **Thin Component Rule**: Livewire components MUST be thin orchestrators. Business logic belongs
  exclusively in the **Service Layer**.
- **PII Protection**: Personally Identifiable Information MUST use the `encrypted` cast and be
  masked in logs.
- **Localization Invariant**: Hard-coding user-facing strings is a **Critical Quality Violation**.
  Use `__('module::file.key')`.
- **No Magic Values**: Abstract status codes and constants into **Enums**.

---

## 🎯 Scope & Authorized Actions

### 1. Protocol Scope

- **Feature Construction**: Building _new_ domain logic, presentation, and infrastructure from
  scratch.
- **Scaffolding**: Generating new modules, services, or components using Artisan tools.
- **Distinction**: This protocol focuses on **creation and growth**, whereas
  **[Refactoring](./refactoring.md)** focuses on **structural realignment and logic hardening** of
  existing artifacts.

### 2. Authorized Actions

- **Repository Management**: Creating and switching branches via `git checkout -b`.
- **Commit Execution**: Staging and committing changes with conventional messages.
- **PR Orchestration**: Initiating **Pull Requests** via `gh pr create` for code review.
- **Code Generation**: Utilizing custom Artisan generators.

---

## Phase 0: Scaffolding & Traceability Reset

- **Traceability Check**: Link the implementation to the Blueprint and **GitHub Issue**.
- **Branch Management**: Create or switch to the appropriate feature branch:
  `feature/{module}/{description}`.
- **Environment Reset**: Run `php artisan migrate:fresh --seed` to ensure a clean baseline.

## Phase 1: Test Specification (Behavioral Design)

- **Unit Suite**: Write tests for the Service Layer methods.
- **Feature Suite**: Write tests for the end-to-end domain flow (HTTP/Livewire).
- **Mirroring Invariant**: Ensure the `tests/` path exactly mirrors the `src/` hierarchy.

## Phase 2: Domain Layer Implementation

- **Model Construction**: Implement UUID, Statuses, and PII protection.
- **Service Construction**: Implement logic within Service Contracts. Extend `EloquentQuery` where
  appropriate.
- **Validation**: Implement validation at the earliest possible boundary (PEP).
- **Atomic Commits**: Commit changes using **Conventional Commits**, referencing the Issue (e.g.,
  `feat(user): add uuid identity. Refs #123`).

## Phase 3: Presentation Layer Implementation

- **Livewire Construction**: Implement the "Thin Component" logic.
- **UI Styling**: Apply Tailwind v4 classes with **Mobile-First** priority.
- **Accessibility**: Verify ARIA attributes and semantic HTML tags.

## Phase 4: Localization & Hardening

- **i18n Extraction**: Move all raw strings to `lang/` files.
- **Metadata Sync**: Update `app_info.json` if achieving a milestone.
- **DocBlock Audit**: Add professional, analytical PHPDocs to all public methods.

## Phase 5: Verification & PR Readiness

- **Logic Verification**: Run `composer test`.
- **Static Analysis**: Run `composer lint` (Laravel Pint).
- **Arch Audit**: Ensure zero-coupling via Arch tests.
- **Pull Request**: Prepare the branch for a **Pull Request**. Ensure the PR description includes
  "Closes #IssueID" to automate issue closure upon merge.

## Phase 6: Workspace Cleanup & Closure

- **Branch Deletion**: Once the PR is merged into the integration baseline (`develop`), delete the
  local and remote feature branch: `git branch -d feature/...` and
  `gh pr checkout <pr-number> --delete-branch`.
- **Issue Verification**: Confirm that the linked **GitHub Issue** is formally closed.
- **Keypoints Summary**: Final report of construction outcomes and repository state.

---

_Implementation is the realization of architectural promise; precision is non-negotiable._
