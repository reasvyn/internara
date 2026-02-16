# Engineering Protocol: Evolution & Maintenance

This document establishes the authoritative **Maintenance Protocol** for Internara, adhering to
**Lehman's Laws of Software Evolution** and **ISO/IEC 12207**.

---

## ⚖️ Core Mandates & Prohibitions (The Maintenance Laws)

### 1. Evolution Invariants

- **Non-Destructive Mandate**: Prioritize relocation, refactoring, or deprecation over hard deletion
  of code.
- **No Batch Actions**: Executing massive, automated batch replacements or bulk modifications across
  multiple modules simultaneously is **STRICTLY PROHIBITED**. Maintenance MUST be atomic and focused
  on the current defect or debt to prevent systemic instability.
- **Behavioral Preservation**: Corrective or perfective maintenance MUST NOT change the external
  behavioral contracts (API/Service Contracts) unless explicitly authorized.
- **Regression Zero-Tolerance**: Maintenance activities must maintain or increase the existing test
  coverage.

### 2. Debt & Security Laws

- **Debt Documentation**: Every maintenance cycle that bypasses a long-term fix MUST document the
  resulting technical debt in `docs/developers/debts/`.
- **Proactive Hardening**: Maintenance is an opportunity to apply "Hukum" (Laws) such as `final`
  keywords, strict types, and PII encryption.

---

## 🎯 Scope & Authorized Actions

### 1. Protocol Scope

- **Corrective Maintenance**: Fixing reported bugs and anomalies.
- **Adaptive Maintenance**: Updating code for compatibility with new PHP/Laravel versions.
- **Perfective Maintenance**: Refactoring logic to improve maintainability or performance.

### 2. Authorized Actions

- **Bug Triage**: Categorizing and reproducing defects.
- **Structural Realignment**: Moving legacy code into the **Modular DDD** hierarchy.
- **Dependency Updates**: Managing version increments for third-party packages.

---

## Phase 0: Anomaly Detection & Triage

- **Issue Identification**: Search **GitHub Issues** and **Discussions** for community feedback.
- **Issue Formalization**: Ensure a **GitHub Issue** exists for the anomaly before proceeding.
- **Triage Protocols**: Categorize reports based on severity (Critical to Low).
- **Hotfix Branch**: If critical, switch to a hotfix branch: `hotfix/{issue-id}-{description}`.

## Phase 1: Root Cause Analysis (RCA) & Reproduction

- **Reproduction Suite**: Create a failing Pest test that demonstrates the defect (TDD approach).
- **Tracing**: Use `search_file_content` to identify the source of the anomaly.

## Phase 2: Corrective Implementation

- **Logic Correction**: Fix the logic so the reproduction test passes.
- **Atomic Commits**: Commit using **Conventional Commits** (`fix(...)`), referencing the Issue.
- **Hardening**: Apply strict types and PHPDocs to the affected classes.

## Phase 3: Perfective Refactoring (Debt Management)

- **Code Cleanup**: Remove redundant logic or dead code.
- **Debt Audit**: Check if the fix resolves any item in `docs/developers/debts/`.

## Phase 4: Regression & Arch Verification

- **Full Module Pass**: Run all tests in the affected module.
- **PR Readiness**: Prepare a **Pull Request** via `gh pr create`. Ensure the "Closes #IssueID"
  syntax is used.
- **Isolation Audit**: Run Arch tests to ensure maintenance didn't introduce cross-module coupling.

## Phase 5: Workspace Cleanup & Closure

- **Branch Deletion**: Delete the hotfix or maintenance branch after successful merge.
- **Issue Verification**: Confirm the bug report issue is closed on GitHub.
- **Keypoints Summary**: Report on Actions, Rationales, and Outcomes.

---

_Maintenance is the struggle against the entropy of time and complexity._
