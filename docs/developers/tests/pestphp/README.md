# Pest PHP: Verification Documentation Index

This directory formalizes the utilization of the **Pest v4** framework for the Internara project,
standardized according to **ISO/IEC 29119** (Software Testing). It provides technical guidance for
constructing deterministic verification suites.

---

## 1. Foundational Verification

- **[Writing Tests](writing-tests.md)**: Semantic structure utilizing the **`test(...)`** pattern.
- **[Expectations API](expectations.md)**: Formal catalog of behavioral assertions.
- **[Life Cycle Hooks](hooks.md)**: Orchestration of setup and teardown baselines.
- **[Verification Datasets](datasets.md)**: Matrix-driven behavioral verification.

## 2. Advanced Verification Protocols

- **[Architecture Verification](arch-testing.md)**: Automated enforcement of modular isolation.
- **[Browser Verification](browser-testing.md)**: End-to-end user flow validation via Playwright.
- **[Mutation Verification](mutation-testing.md)**: Quantitative evaluation of verification quality.
- **[Stress Verification](stress-testing.md)**: System performance and load concurrency audit.

## 3. Infrastructure & Tooling

- **[Dependency Mocking](mocking.md)**: Orchestration of mocks and spies within the V&V cycle.
- **[Pest Extensions](plugins.md)**: Technical protocols for framework extensibility.
- **[Output Snapshots](snapshot-testing.md)**: Verification of complex artifact stability.

---

_For Internara-specific verification patterns and domain invariants, refer to the
**[Testing & Verification Standards](../testing.md)**._
