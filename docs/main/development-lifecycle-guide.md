# Development Lifecycle & Versioning Strategy

This guide is the **authoritative standard** for how we plan, build, and document releases in the Internara project. It is designed to ensure that every version tells a complete engineering story—from the initial problem definition to the final quality verification.

Adherence to this guide is mandatory. It ensures our history is clear, our architecture is respected, and our future is predictable.

---

## 1. Versioning Guidelines

Internara employs a robust hybrid versioning strategy. We combine standard Semantic Versioning for technical compatibility with a unique "Series Code" to track development themes and stages.

### 1.1. Semantic Versioning (SemVer)

We strictly follow [SemVer 2.0.0](https://semver.org/).

| Type | Format | Description |
| :--- | :--- | :--- |
| **MAJOR** | `X.0.0` | **Incompatible** API changes or architectural shifts. Requires a migration guide. |
| **MINOR** | `0.Y.0` | **New Functionality** that is backward-compatible. Used for new modules or features. |
| **PATCH** | `0.0.Z` | **Bug Fixes** or minor refinements. Safe to update immediately. |

### 1.2. The Series Code

While SemVer tells us *what* changed technically, the Series Code tells us *why* and *where* we are in the project timeline. This code is appended to our internal release notes and documentation.

**Format:** `{CODENAME}-{SCOPE}-{SEQUENCE}`

#### Components Breakdown

1.  **`{CODENAME}`** (Context & Stage)
    A unique, abstract identifier (ALL CAPS). It typically starts with the stage initial:
    - **A**...: **Alpha** (Feature incomplete, unstable).
    - **B**...: **Beta** (Feature complete, stabilization phase).
    - **RC**...: **Release Candidate** (Production ready, freezing).
    - **S**...: **Stable** (Production).
    - *Example:* `ARC01` (Alpha Release Cycle 01).

2.  **`{SCOPE}`** (Focus Area)
    Identifies the primary engineering focus:
    - `INIT`: **Initiation** (Project setup, tooling, repo creation).
    - `FND`: **Foundational** (Core architecture, base services, shared traits).
    - `FEAT`: **Feature-Driven** (User-facing functionality, new modules).
    - `RFT`: **Refactoring** (Code cleanup, performance, technical debt).
    - `SEC`: **Security** (Vulnerability patches, audits).

3.  **`{SEQUENCE}`** (Optional)
    Used for sub-cycles or specific variations (e.g., `01`, `hotfix`).

**Full Example:** `ARC01-INIT` (Alpha Cycle 1, Initiation focus).

---

## 2. The Lifecycle Documentation Standard

In Internara, **documentation is code**. It is not an afterthought. Every version release (e.g., `v0.4.0-alpha`) must be accompanied by a dedicated document in `docs/versions/` that captures the full engineering context.

> **The Golden Rule:** Do not just list *what* you did. Tell the story of **Why** you did it, **How** you did it, and **Proof** that it works.

### 📜 Documentation Maintenance Principle

**"Update First, Create Second"**

Before creating a new documentation file, always ask: *"Does a file for this topic already exist?"*

-   **Update:** If you modify the User module, update `modules/User/README.md` or `docs/main/modules/user.md`.
-   **Create:** Only create new files for entirely new domains, architectural layers, or distinct technical concepts.
-   **Why?** This prevents "documentation rot" and fragmentation. We want a **Single Source of Truth**.

---

### Phase 1: Pre-Production (The Planning Context)

This phase occurs **before** any code is written. It defines the "Rules of Engagement".

#### 1. Goals & Architectural Philosophy

-   **Purpose:** Contextualize the release. Why are we doing this?
-   **Problem Keypoints:** List specific pain points.
    -   *Example:* "The current 'User' module is tightly coupled with 'Auth', making reuse difficult."
-   **Architectural Pillars:** High-level strategies to solve these problems.
    -   *Example:* "Decouple User and Auth into separate modules communicating via Interfaces."
-   **Detailed Explanation:** Elaborate on the *why*. Justify your architectural choices.

#### 2. Architectural Constraints (The "Must Nots")

-   **Purpose:** Prevent scope creep and architectural decay (entropy).
-   **Content:** Explicit, non-negotiable rules for this specific version.
-   **Examples:**
    -   "No logic allowed in Controllers; delegate strictly to Services."
    -   "Modules must not access other modules' database tables directly."
    -   "The `app/` directory must remain empty except for ServiceProviders."

---

### Phase 2: Production (The Execution)

This phase details the actual implementation. Choose the format that best fits the release type.

#### Format A: `Scope of Work` (Infrastructure/Refactor)
*Best for: `INIT`, `RFT`, `SEC` scopes.*

A categorized checklist of concrete technical tasks.

-   **Environment:** "Install Laravel 12, Configure SQLite."
-   **Tooling:** "Setup Pest PHP, Configure Pint."
-   **Refactor:** "Move `User` model to `Modules/User/src/Models`."

#### Format B: `System & Feature Keystones` (Feature/Foundation)
*Best for: `FEAT`, `FND` scopes.*

Group work into logical "Keystones" (Major achievements).

1.  **Keystone Title** (e.g., "Role-Based Access Control")
    -   **Goal:** User-centric objective (e.g., "Allow admins to manage permissions").
    -   **Implementation:** Technical strategy (e.g., "Implement `spatie/laravel-permission` with custom Policy gates").
    -   **Developer Impact:** How this helps the team (e.g., "Simplifies auth checks to `$user->can('edit')`").

---

### Phase 3: Post-Production (Verification & Future)

This phase provides the **proof of quality** and sets the stage for what comes next.

#### 1. Quality Assurance & Verification

Prove the system works.

-   **Tooling Setup:** Confirm test suites and linters are active.
-   **Verification Checks:** Specific actions taken to validate the release.
    -   *Example:* "Ran `php artisan test --filter=User`: 100% Pass."
    -   *Example:* "Manually verified login flow with 2FA enabled."

#### 2. Security Issues

Transparently document security findings.

-   **Status:** "No critical issues identified" OR list specific vulnerabilities found and fixed (Severity, Impact, Fix).

#### 3. Documentation Checks

Ensure the "Single Source of Truth" is up to date.

-   **Checklist:**
    -   New features documented in `docs/main/`?
    -   `README.md` updated with version status?
    -   `CHANGELOG.md` reflects all new production work?
    -   `SECURITY.md` reviewed for protocol updates?
    -   Architecture guide updated?
    -   TOCs (Table of Contents) updated?

#### 4. Roadmap & Next Steps

Strictly define the boundaries of the *next* iteration to prevent scope creep *now*.

-   **Must Have:** Critical path items for the next version.
-   **Should Have:** Important but deferrable improvements.
-   **Won't Have:** Explicitly out of scope for the next version.

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

- [ ] Pest Tests passed.
- [ ] Pint/Prettier formatting applied.

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

[← Previous: Development Conventions](development-conventions.md) | [Next: Release Guidelines](release-guidelines.md)