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

## 5. Advanced Verification Orchestration (`app:test`)

To manage the systemic complexity of a Modular Monolith and prevent memory accumulation (Memory Leaks) during large-scale verification, Internara utilizes a custom **Advanced Orchestrator**.

### 5.1 Orchestration Benefits
- **Process Isolation**: Each module/segment is executed in a separate PHP process, ensuring a clean memory heap for every run.
- **Resumable Sessions**: Supports persistent testing sessions via `--continue`. Successful segments are skipped in subsequent runs, allowing developers to focus on fixing failures.
- **Smart Invalidation**: Automatically detects file changes within modules and invalidates previous successful results, ensuring regressions are always caught.
- **Stability Reporting**: Provides a real-time **Global Pass Rate** and **Stability Index** based on the entire 100% system baseline.

### 5.2 Key Commands
- `php artisan app:test`: Standard sequential verification.
- `php artisan app:test --continue`: Resume from the last failure, skipping valid segments.
- `php artisan app:test --report`: View comprehensive system stability metrics.
- `php artisan app:test --dirty`: Run tests only for modules with uncommitted changes.
