# Comprehensive Engineering Protocol: Testing & V-Model Integration

This document establishes the authoritative **End-to-End Verification & Validation (V&V) Protocol**
for the Internara project, standardized according to **ISO/IEC 29119** (Software Testing) and **IEEE
Std 1012**.

---

## ⚖️ Core Mandates & Prohibitions (The V&V Laws)

The Agent must adhere to these invariants to ensure technical correctness and requirement
fulfillment.

### 1. Structural & Pattern Laws

- **Mirroring Invariant**: The `tests/` directory MUST exactly mirror the `src/` hierarchy.
- **Pest Pattern**: Utilize only **`test(...)`** and **`describe(...)`** syntax. Legacy PHPUnit
  styles are discouraged.
- **Traceability**: Every test MUST be linked to a requirement ID (e.g.,
  `test('it fulfills [SYRS-ID]')`).

### 2. Logic & Isolation Laws

- **Strict Isolation**: Direct instantiation of foreign models or concrete classes in tests is
  **PROHIBITED**. Use Service Contracts.
- **Mocking Standard**: Use real Service Contracts for cross-module integration; reserve `Mockery`
  only for high-overhead external infra (Mail, APIs).
- **Policy Verification**: Feature tests involving domain models MUST verify authorization via
  Associated Policies.

---

## 🎯 Scope & Authorized Actions

### 1. Protocol Scope

- **Verification (V)**: Testing against technical designs and conventions.
- **Validation (V)**: Testing against stakeholder requirements (SyRS).
- **Remediation**: Fixing defects identified during the testing cycle.

### 2. Authorized Actions

- **Bug Remediation**: Modifying code to fix failing tests.
- **Coverage Expansion**: Creating new tests to reach the >90% behavioral coverage target.
- **Environment Orchestration**: Running `migrate:fresh`, `optimize:clear`, and `app:test`.

---

## Phase 0: System Reset & Branch Synchronization

- **Baseline Alignment**: `git fetch --all` and sync with the integration baseline (`develop`).
- **Environment Reset**: Run `composer dump-autoload` and `migrate:fresh --seed`.
- **Mirroring Audit**: Verify that all test files are in their correct DDD-mirrored folders.

## Phase 1: Unit Verification (Mathematical Isolation)

- **Service Logic**: Execute Unit tests for atomic logic.
- **Zero-Dependency**: Ensure Unit tests utilize 100% mocking.

## Phase 2: Feature Validation (Domain Flows)

- **User Story Verification**: Execute Feature tests for end-to-end domain actions.
- **Side-Effect Audit**: Verify Events, Notifications, and Database transitions.

## Phase 3: Architecture & Isolation Audit

- **Coupling Check**: Run Pest Arch tests.
- **src Omission Check**: Verify namespaces across all tested modules.

## Phase 4: UI & Accessibility Validation

- **Render Testing**: Verify Livewire component output.
- **A11y Audit**: Ensure WCAG 2.1 compliance.

## Phase 5: Remediation & PR Gate Verification

- **Targeted Fixes**: Resolve any failures identified in previous phases.
- **Regression Pass**: Re-run the full suite to ensure no new defects were introduced.
- **Full Verification Sweep**: Run `composer test` and `composer lint` to satisfy the **PR Approval
  Criteria**.

## Phase 6: SSoT Synchronization

- **Traceability Finalization**: Ensure all tests correctly reference SyRS IDs and GitHub Issues.
- **Keypoints Summary**: Final report of Actions, Rationales, and Verified Outcomes.

---

_Testing is the executable proof of quality; failure to verify is a failure to engineer._
