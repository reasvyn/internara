# Continuous Integration (CI): Automated Quality Gates

This document formalizes the **Continuous Integration (CI)** protocols for the Internara system, standardized according to **ISO/IEC 12207**.

---

## 1. CI Philosophy: Failure as a Guard

Internara utilizes a **Zero-Tolerance** CI policy. No modification is promoted to the authoritative baseline (`main`) unless it satisfies 100% of the automated gates.

---

## 2. Pipeline Orchestration (GitHub Actions)

The CI pipeline executes the following sequence on every push and pull request:

### 2.1 Stage 1: Static Analysis
- **Linter**: `vendor/bin/pint --test` (PSR-12 compliance).
- **Analyzer**: `vendor/bin/phpstan analyze` (Type safety and logic verification).

### 2.2 Stage 2: Verification (Behavioral)
- **Unit & Feature**: `vendor/bin/pest` (Requirement fulfillment).
- **Architecture**: `vendor/bin/pest --filter=Arch` (Modular isolation enforcement).

### 2.3 Stage 3: Security Scan
- **Dependencies**: `composer audit` (SBOM vulnerability check).
- **Code**: Integrated SAST scanning for hard-coded secrets and injection sinks.

---

## 3. Deployment Integration (CD)

Upon a successful merge to `main` and the creation of a Git Tag:
1.  **Release Creation**: Automated generation of a GitHub Release following the **[Versioning Policy](../versioning-policy.md)**.
2.  **Artifact Synthesis**: Bundling of production-ready assets (Vite build) and finalized documentation.
3.  **Audit Log**: Capture of the release baseline in the `release-management.md`.
