# Software Bill of Materials (SBOM): Internara Dependency Inventory

This document provides the authoritative **Software Bill of Materials (SBOM)** for the Internara system, standardized according to **ISO/IEC 5962** (SPDX). It lists the primary third-party components, their versions, and licensing terms.

---

## 1. Core Framework & Infrastructure

| Component | Provider | License | Version |
| :--- | :--- | :--- | :--- |
| **Laravel Framework** | Laravel | MIT | ^12.0 |
| **PHP** | PHP Group | PHP | ^8.4 |
| **SQLite/PostgreSQL** | Community | Public Domain/BSD | (Environment Dependent) |

---

## 2. Primary Domain Orchestrators

| Component | Role | License |
| :--- | :--- | :--- |
| **nwidart/laravel-modules** | Modular Monolith Engine | MIT |
| **livewire/livewire** | Presentation Engine | MIT |
| **spatie/laravel-permission** | RBAC Engine | MIT |
| **spatie/laravel-activitylog** | Audit Orchestrator | MIT |
| **spatie/laravel-medialibrary** | Asset Management | MIT |

---

## 3. Visual & UI Ecosystem

| Component | Role | License |
| :--- | :--- | :--- |
| **tailwindcss** | CSS Framework | MIT |
| **robsontenorio/mary** | UI Component Library | MIT |
| **postare/blade-mdi** | Iconography | MIT |
| **simplesoftwareio/simple-qrcode** | QR Visualization | MIT |

---

## 4. Verification & Quality Tooling

| Component | Role | License |
| :--- | :--- | :--- |
| **pestphp/pest** | Primary Testing Engine | MIT |
| **laravel/pint** | PHP Linting | MIT |
| **phpstan/phpstan** | Static Analysis | MIT |

---

## 5. Security & Maintenance Invariant

Internara utilizes automated dependency auditing via:
- **GitHub Dependabot**: Continuous scanning for vulnerable dependencies.
- **Composer Audit**: Integrated security checks during the build process.

_Release Promoters MUST update this SBOM during every MAJOR release cycle to ensure institutional compliance._
