# Test Plan (TP): Verification & Validation Execution

This document formalizes the **Test Plan (TP)** for the Internara system, standardized according to **ISO/IEC 29119** (Software testing). It defines the strategy, environment, and metrics for verifying system requirements and architectural invariants.

---

## 1. Testing Strategy: TDD-First

Internara mandates a **Test-Driven Development (TDD)** lifecycle using **Pest PHP v4**.

### 1.1 Test Taxonomy
- **Unit Tests**: Verify isolated business logic in services and helpers (70% of the suite).
- **Feature Tests**: Verify end-to-end domain workflows and Livewire component behavior (20%).
- **Arch Tests**: Verify architectural invariants (e.g., no cross-module model leaks) (10%).

---

## 2. Verification Gates (Quality Levels)

Every pull request must pass the following gates:

| Level | Goal | Tool |
| :--- | :--- | :--- |
| **Static Analysis** | Type safety & logic check. | PHPStan (Level 8) |
| **Code Style** | PSR-12 & Pint alignment. | Laravel Pint |
| **Behavioral** | Requirement fulfillment. | Pest |
| **Architectural** | Modular isolation check. | Pest Arch |

---

## 3. Coverage & Performance Metrics

- **Behavioral Coverage**: ≥ 90% for all domain modules.
- **Database Consistency**: Use `RefreshDatabase` for isolated, idempotent tests.
- **Mocking Strategy**: Use service contract mocking to maintain module isolation during unit testing.

---

## 4. Test Environment Orchestration

- **Environment**: SQLite (memory) for high-speed local testing.
- **CI Pipeline**: Automated execution on GitHub Actions for every commit.
- **Artifacts**: Generation of test results and coverage reports for audit purposes.
