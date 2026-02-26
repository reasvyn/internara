# Testing & Verification Guide: Engineering Reliability

This document formalizes the **Verification & Validation (V&V)** protocols for the Internara
project, adhering to **IEEE Std 1012** and **ISO/IEC 29119**.

---

## 🏛️ The V&V Invariant: Deterministic Quality

Verification ensures technical correctness, while Validation ensures requirement fulfillment.

1.  **3S Audit**: All artifacts must pass a 3-stage verification protocol: **Security (S1)**, 
    **Sustainability (S2)**, and **Scalability (S3)**.
2.  **TDD-First**: Automated verification must exist prior to logic implementation.
3.  **Modular Isolation**: Tests must verify a module's behavior without dependent implementation
    leakage.
4.  **Behavioral Coverage**: A minimum of **90% coverage** is required for all domain logic, 
    mirroring the `src/` hierarchy.

---

## 1. Verification Philosophy

- **[Verification Philosophy](#1-verification-philosophy)**: Engineering reliability and the Pest v4
  baseline.
- **[Laravel Testing Basics](laravel-tests.md)**: Getting started guide for testing in the Laravel
  ecosystem.
- **[Troubleshooting & FAQ](troubleshooting.md)**: Common issues and fixes for the engineering team.

## 2. Specialized Verification Protocols

- **[Persistence Verification](database-testing.md)**: Standards for schema and model integrity.
- **[Presentation Verification](livewire-tests.md)**: Standards for interactive UI orchestration.
- **[Endpoint Verification](http-tests.md)**: Standards for boundary protection and API state.
- **[Infrastructure Verification](console-tests.md)**: Standards for CLI orchestration tools.
- **[Dependency Mocking](mocking.md)**: Protocols for strict modular isolation during V&V.
- **[Dusk Browser Verification](dusk-browser-test.md)**: Protocols for browser automation via
  Laravel Dusk.

## 3. Pest v4 Technical Baseline

- **[Pest Documentation Index](pest-index.md)**: Comprehensive technical catalog for the Pest
  verification framework.

---

_All verification activities must satisfy this **Testing & Verification Guide**._
