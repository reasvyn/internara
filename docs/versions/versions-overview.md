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

- **v0.6.0-alpha** ([Documentation v0.6.x-alpha](v0.6.x-alpha.md)): **Assessment & Finalization
  Phase**.

    Finalization of the core internship cycle with role-specific workspaces, unified assessment
    logic, and automated PDF reporting with QR verification.

- **v0.5.0-alpha** ([Documentation v0.5.x-alpha](v0.5.x-alpha.md)): **Operational & Activity
  Tracking Phase**. Implementation of Daily Journal (Logbook), Attendance System, and Supervisor
  Matching.

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

### Phase 2: Production (Deep Analytical Narrative)

This phase details the implementation through a lens of engineering analysis. Instead of checklists,
every milestone is documented as a narrative of technical choices.

#### 1. System & Feature Keystones (Analysis)

Group work into logical "Keystones". For each keystone, provide:

- **Technical Rationale:** Why was this specific approach chosen? What alternatives were considered
  and rejected?
- **Implementation Deep-Dive:** Describe the core logic flow, key classes involved, and how
  conventions (e.g., Interface-first) were applied.
- **Architectural Evolution:** Explain how this change impacts the system's state or prepares it for
  future versions.

### Phase 3: Post-Production (Continuous Verification & Proof)

This phase provides the **analytical proof** of quality. It is an iterative cycle of verification
and artifact synchronization.

#### 1. Quality Assurance Analysis

Analyze the results of the verification cycle.

- **Test Suite Evaluation:** Narrative analysis of test coverage and the stability of the logic
  introduced.
- **Refactor & Optimization Log:** Describe why certain codes were refactored and the
  performance/readability gains achieved.

#### 2. Artifact Synchronization Protocol

Every technical change triggers a full documentation sync. This section analyzes the update status
of:

- Modular Guides (e.g., `docs/main/modules/`)
- Architecture Guide & Development Conventions
- `CHANGELOG.md` and `README.md`

---

## 3. Standard Version Document Template (Narrative Format)

Copy this template for every new version document in `docs/versions/`.

```markdown
# Overview: Version vX.X.X (series-code)

## 1. Version Details

- **Series Code**: `[CODENAME]-[SCOPE]-[SEQ]`
- **Scope**: `INITIAL`
- **Status**: `In Progress` / `Stable`
- **Current Operational Context**: Brief analysis of the system's mission in this version.

---

## 2. Goals & Architectural Philosophy (Pre-Production)

### Problem Analysis (The "Why")

Detailed prose describing the architectural debt or business needs being addressed.

### Architectural Pillars (The "How")

Narrative description of the strategies chosen to solve the identified problems.

### Architectural Constraints (The Rules)

Non-negotiable rules for this version, explained through their technical necessity.

---

## 3. Production Keystones (Analytical Narrative)

### Keystone 1: [Feature Name]

#### Technical Rationale

Describe the reasoning behind the design.

#### Implementation Deep-Dive

Detail the engineering work, logic flow, and key modules/interfaces involved.

#### Architectural Impact

Analyze how this evolves the project's foundation.

---

## 4. Quality Assurance & Artifact Sync (Post-Production)

### 4.1. Verification Analysis

Analyze test results and manual verification outcomes. Provide proof of stability.

### 4.2. Continuous Documentation Sync

Narrative summary of which technical artifacts (docs/CHANGELOG/README) were updated to maintain the
Single Source of Truth.

### 4.3. Security & Privacy Audit

Summary of the security posture after the changes.

### 4.4. Roadmap Strategy (vNext)

Analytical preview of the next iteration's boundaries.
```

---

**Navigation**

[← Previous: Custom Module Generator](../main/advanced/custom-module-generator.md) |
[Next: Versioning Strategy & Guide →](versions-guide.md)
