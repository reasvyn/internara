# Development Workflow

This document serves as the **Standard Operating Procedure (SOP)** for all development activities
within the Internara project. It integrates high-level version planning with the specific technical
steps required to build features in our Modular Monolith architecture.

**The Golden Rule:** Never start coding without a plan. Never finish without **Artifact
Synchronization**.

> **Mandate:** Every technical modification triggers a full cycle of Quality Assurance and
> Documentation Synchronization. Implementation is only complete when all related artifacts reflect
> the new system state.

---

**Table of Contents**

- [1. Phase 1: Pre-Development (Context & Planning)](#1-phase-1-pre-development-context--planning)
- [2. Phase 2: Development Execution (Implementation)](#2-phase-2-development-execution-implementation)
- [3. Phase 3: Post-Development (Verification & Artifact Sync)](#3-phase-3-post-development-verification--artifact-sync)

---

## 1. Phase 1: Pre-Development (Context & Planning)

Before writing a single line of code, you must establish the "Why" and "What" through analytical
contextualization.

### 1.1. Review Previous Version Context

- **Action:** Read the deep analytical narrative of the immediately preceding version.
- **Goal:** Understand the technical rationale and architectural impact of previous choices to
  ensure continuity.

---

## 3. Phase 3: Post-Development (Verification & Artifact Sync)

A feature is not "Done" until it passes the **Iterative Sync Cycle**. If any step below results in a
code change, the entire cycle must be repeated.

### 3.1 Iterative QA Cycle

- **Testing (Pest):** Run tests for the specific module and the entire suite.
- **Security Audit:** Verify IDOR, XSS, and authorization gates through analytical review.
- **Quality & Linting:** Run `npm run lint` and `vendor/bin/pint`.

### 3.2 Continuous Artifact Synchronization

Documentation is code. Update the following artifacts iteratively:

- **Analytical Version Note:** Update `docs/versions/{current}.md` with technical rationales and
  deep-dives (No checklists).
- **Application Info:** Update `app_info.json` at root with latest version and series code.
- **Release Notes:** Update `RELEASE_NOTES.md` at root with high-level highlights.
- **Changelog:** Add entries to `CHANGELOG.md` under the unreleased version.
- **Technical Guides:** Synchronize module-specific docs in `docs/main/modules/` and architectural
  guides.
- **Readme:** Ensure `README.md` version indicators are accurate.

---

**Navigation**

[← Previous: Software Development Life Cycle](software-lifecycle.md) |
[Next: Role & Permission Management Guide →](role-permission-management.md)
