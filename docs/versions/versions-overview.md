# Application Versions Overview

This directory contains the release history and versioning standards for the Internara project.

**Internara** is an open-source internship management system built with a modern Laravel tech stack,
including **Livewire 3** and a **Modular Monolith** architecture. Its primary goal is to streamline
the entire internship lifecycle, from student registration and daily journal logging to final
assessments and reporting.

---

## Documentation Sections

### 1. [Versioning Strategy & Documentation Guide](versions-guide.md)

Detailed guidelines on Internara's Semantic Versioning, Series Codes (e.g., `ARC01-CORE`), and the
standard lifecycle documentation structure.

---

## Release History

### Newest Releases

- **v0.5.x-alpha** ([Documentation v0.5.x-alpha](v0.5.x-alpha.md)): **Operational & Activity Tracking
  Phase**. Implementation of Daily Journal (Logbook), Attendance System, and Supervisor Matching.
- **v0.4.0-alpha** ([Documentation v0.4.x-alpha](v0.4.x-alpha.md)): **Institutional & Academic
  Phase**. Implementation of School, Department management, and foundational Internship registration
  workflows.

### Previous Releases

- **v0.3.0-alpha** ([Documentation v0.3.x-alpha](v0.3.x-alpha.md)): **User & Profile Management
  Phase**. Implementation of User management, profile systems, auth refinements, and security
  hardening.
- **v0.2.0-alpha** ([Documentation v0.2.x-alpha](v0.2.x-alpha.md)): **Core & Shared Systems Phase**.
  Implementation of the Shared (Utilities) and Permission (RBAC) modules.
- **v0.1.1-alpha** ([Documentation v0.1.x-alpha](v0.1.x-alpha.md)): **Project Initiation Phase**.
  Environment setup, framework installation, and establishing the modular architecture foundation.

---

# Development Lifecycle & Versioning Strategy

This guide is the **authoritative standard** for how we plan, build, and document releases in the
Internara project. It is designed to ensure that every version tells a complete engineering
story—from the initial problem definition to the final quality verification.

Adherence to this guide is mandatory. It ensures our history is clear, our architecture is
respected, and our future is predictable.

---

## 1. Versioning Guidelines

Internara employs a robust hybrid versioning strategy. We combine standard Semantic Versioning for
technical compatibility with a unique "Series Code" to track development themes and stages.

### 1.1. Semantic Versioning (SemVer)

We strictly follow [SemVer 2.0.0](https://semver.org/).

| Type      | Format  | Description                                                                          |
| :-------- | :------ | :----------------------------------------------------------------------------------- |
| **MAJOR** | `X.0.0` | **Incompatible** API changes or architectural shifts. Requires a migration guide.    |
| **MINOR** | `0.Y.0` | **New Functionality** that is backward-compatible. Used for new modules or features. |
| **PATCH** | `0.0.Z` | **Bug Fixes** or minor refinements. Safe to update immediately.                      |

### 1.2. The Series Code

While SemVer tells us _what_ changed technically, the Series Code tells us _why_ and _where_ we are
in the project timeline. This code is appended to our internal release notes and documentation.

**Format:** `{CODENAME}-{SCOPE}-{SEQUENCE}`

#### Components Breakdown

1.  **`{CODENAME}`** (Abstract Context) A unique, abstract identifier (ALL CAPS) that represents the
    specific development cycle.
    - **Requirement:** Must start with a letter.
    - **Style:** Unique and abstract (e.g., `ARC01`, `BRC02`, `VENUS`).
    - _Example:_ `ARC01` (Alpha Release Cycle 01).

2.  **`{SCOPE}`** (Focus Area) Identifies the primary engineering focus:
    - `INIT`: **Initiation** (Project setup, tooling, repo creation).
    - `FND`: **Foundational** (Core architecture, base services, shared traits).
    - `FEAT`: **Feature-Driven** (User-facing functionality, new modules).
    - `RFT`: **Refactoring** (Code cleanup, performance, technical debt).
    - `SEC`: **Security** (Vulnerability patches, audits).

3.  **`{SEQUENCE}`** (Optional) Used for sub-cycles or specific variations (e.g., `01`, `hotfix`).

**Full Example:** `ARC01-INIT` (Alpha Cycle 1, Initiation focus).

---

## 2. The Lifecycle Documentation Standard

In Internara, **documentation is code**. It is not an afterthought. Every version release (e.g.,
`v0.4.0-alpha`) must be accompanied by a dedicated document in `docs/versions/` that captures the
full engineering context.

> **The Golden Rule:** Do not just list _what_ you did. Tell the story of **Why** you did it,
> **How** you did it, and **Proof** that it works.

### 📜 Documentation Maintenance Principle

**"Update First, Create Second"**

Before creating a new documentation file, always ask: _"Does a file for this topic already exist?"_

- **Update:** If you modify the User module, update `modules/User/README.md` or
  `docs/main/modules/user.md`.
- **Create:** Only create new files for entirely new domains, architectural layers, or distinct
  technical concepts.
- **Why?** This prevents "documentation rot" and fragmentation. We want a **Single Source of
  Truth**.

---

### Phase 1: Pre-Production (The Planning Context)

This phase occurs **before** any code is written. It defines the "Rules of Engagement".

#### 1. Goals & Architectural Philosophy

- **Purpose:** Contextualize the release. Why are we doing this?
- **Problem Keypoints:** List specific pain points.
    - _Example:_ "The current 'User' module is tightly coupled with 'Auth', making reuse difficult."
- **Architectural Pillars:** High-level strategies to solve these problems.
    - _Example:_ "Decouple User and Auth into separate modules communicating via Interfaces."
- **Detailed Explanation:** Elaborate on the _why_. Justify your architectural choices.

#### 2. Architectural Constraints (The "Must Nots")

- **Purpose:** Prevent scope creep and architectural decay (entropy).
- **Content:** Explicit, non-negotiable rules for this specific version.
- **Examples:**
    - "No logic allowed in Controllers; delegate strictly to Services."
    - "Modules must not access other modules' database tables directly."
    - "The `app/` directory must remain empty except for ServiceProviders."

---

### Phase 2: Production (The Execution)

This phase details the actual implementation. Choose the format that best fits the release type.

#### Format A: `Scope of Work` (Infrastructure/Refactor)

_Best for: `INIT`, `RFT`, `SEC` scopes._

A categorized checklist of concrete technical tasks.

- **Environment:** "Install Laravel 12, Configure SQLite."
- **Tooling:** "Setup Pest PHP, Configure Pint."
- **Refactor:** "Move `User` model to `Modules/User/src/Models`."

#### Format B: `System & Feature Keystones` (Feature/Foundation)

_Best for: `FEAT`, `FND` scopes._

Group work into logical "Keystones" (Major achievements).

1.  **Keystone Title** (e.g., "Role-Based Access Control")
    - **Goal:** User-centric objective (e.g., "Allow admins to manage permissions").
    - **Implementation:** Technical strategy (e.g., "Implement `spatie/laravel-permission` with
      custom Policy gates").
    - **Developer Impact:** How this helps the team (e.g., "Simplifies auth checks to
      `$user->can('edit')`").

---

### Phase 3: Post-Production (Verification & Future)

This phase provides the **proof of quality** and sets the stage for what comes next.

#### 1. Quality Assurance & Verification

Prove the system works.

- **Tooling Setup:** Confirm test suites and linters are active.
- **Verification Checks:** Specific actions taken to validate the release.
    - _Example:_ "Ran `php artisan test --filter=User`: 100% Pass."
    - _Example:_ "Manually verified login flow with 2FA enabled."

#### 2. Security Issues

Transparently document security findings.

- **Status:** "No critical issues identified" OR list specific vulnerabilities found and fixed
  (Severity, Impact, Fix).

#### 3. Documentation Checks

Ensure the "Single Source of Truth" is up to date.

- **Checklist:**
    - New features documented in `docs/main/`?
    - `README.md` updated with version status?
    - `CHANGELOG.md` reflects all new production work?
    - `SECURITY.md` reviewed for protocol updates?
    - Architecture guide updated?
    - TOCs (Table of Contents) updated?

#### 4. Roadmap & Next Steps

Strictly define the boundaries of the _next_ iteration to prevent scope creep _now_.

- **Must Have:** Critical path items for the next version.
- **Should Have:** Important but deferrable improvements.
- **Won't Have:** Explicitly out of scope for the next version.

---

## 3. Standard Version Document Template

Copy this template for every new version document in `docs/versions/`.

```markdown
# Overview: Version vX.X.X (Codename)

## 1. Version Details

- **Name**: `vX.X.X`
- **Series Code**: `[CODENAME]-[SCOPE]-[SEQ]`
- **Status**: `In Progress` / `Released`
- **Description**: Brief summary of this release's intent.

---

## 2. Goals & Architectural Philosophy (Pre-Production)

### Problem Keypoints (Why we are doing this)

- [ ] Current Issue A...
- [ ] Current Issue B...

### Architectural Pillars (How we solve it)

- [ ] Strategy A...
- [ ] Strategy B...

### Architectural Constraints (The Rules)

_Immutable rules defined for this version._

1.  **Constraint 1:** Description...
2.  **Constraint 2:** Description...

---

## 3. Scope of Work / Keystones (Production)

_(Select Format A or B below, delete the other)_

### Format A: Scope of Work (Infrastructure/Refactor)

#### Infrastructure & Setup

- [ ] Task...

#### Refactoring

- [ ] Task...

### Format B: System Keystones (Features)

#### Keystone 1: [Feature Name]

- **Goal**:
- **Implementation**:
- **Developer Impact**:

---

## 4. Quality Assurance & Finalization (Post-Production)

### 4.1. Quality Tooling & Tests

- [ ] Pest Tests passed by run `composer test`.
- [ ] Pint/Prettier formatting applied by run `composer lint`.

### 4.2. Verification Checks

- [ ] Verify A...
- [ ] Verify B...

### 4.3. Documentation Integrity

- [ ] New features documented in `docs/main/`.
- [ ] `README.md`, `CHANGELOG.md`, and `SECURITY.md` updated.
- [ ] Technical conventions updated.
- [ ] Root TOC and Module TOCs updated.

### 4.4. Security Audit

- [ ] Status: ...

### 4.5. Roadmap & Next Steps (vNext)

#### Must Have

- ...

#### Should Have

- ...

#### Won't Have

- ...
```

---

**Navigation**

[← Previous: Custom Module Generator](../main/advanced/custom-module-generator.md) |
[Next: Versioning Strategy & Guide →](versions-guide.md)