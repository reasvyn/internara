# Automated Tooling Reference: Infrastructure Standards

This document formalizes the **Automated Tooling** reference for the Internara project, standardized
according to **ISO/IEC 12207** (Infrastructure and Support Processes). It provides a technical
catalog of commands engineered to enforce architectural invariants, verify behavioral
specifications, and ensure code quality.

> **Governance Mandate:** All software artifacts generated or verified via these tools must strictly
> comply with the **[System Requirements Specification](system-requirements-specification.md)** and
> the **[Code Quality Standardization](code-quality-standardization.md)**.

---

## 1. System Verification Suite (Quality Gates)

The following commands are the mandatory gates for all developmental activities.

### 1.1 Full System Verification

```bash
composer test
```

- **Objective**: Execute all behavioral verification suites (Unit, Feature, Architecture) using Pest
  v4. Mandatory for baseline promotion.

### 1.2 Static Analysis & Linting

```bash
composer lint
```

- **Objective**: Perform full-spectrum static analysis and check code style compliance using Laravel
  Pint (check mode) and Prettier.

### 1.3 Automated Code Formatting

```bash
composer format
```

- **Objective**: Automatically fix code style violations across PHP, Blade, and JS files to maintain
  consistency with the project's formatting standards.

---

## 2. Modular Artifact Generators (Support Module)

Generators are engineered to respect the **Architecture Description** invariants, the
**src-Omission** namespace rule, and the **Modular DDD** hierarchy.

### 2.1 Generic PHP Class

```bash
php artisan module:make-class {ClassName} {ModuleName} --interface={ContractPath}
```

- **Objective**: Generates a `final` class within the module's domain structure.

### 2.2 Contract & Concern Construction

```bash
php artisan module:make-interface {InterfaceName} {ModuleName}
php artisan module:make-trait {TraitName} {ModuleName}
```

- **Standard**: Places artifacts in the correct `Contracts/` or `Concerns/` sub-directories.

### 2.3 Browser Testing Scaffolding

```bash
php artisan module:make-dusk {TestName} {ModuleName}
```

- **Standard**: Automatically incorporates **Dusk** boilerplate for visual verification.

---

## 3. System Identity & Orchestration

### 3.1 Metadata & Version Audit

```bash
php artisan app:info
```

- **Objective**: Verifies system version, series code, and author attribution integrity.

### 3.2 Memory-Efficient Test Orchestration

```bash
php artisan app:test {ModuleName?} --no-browser
```

- **Objective**: Executes tests sequentially per module to cap memory usage. Mandatory for CI/CD
  pipelines.

### 3.3 Development Environment Orchestration

```bash
composer dev
```

- **Objective**: Starts the integrated development environment, including the local server, queue
  listener, log tailing (Pail), and Vite development server in a single concurrent session.

---

## 4. Security & Access Control

### 4.1 RBAC Baseline Synchronization

```bash
php artisan permission:sync
```

- **Objective**: Synchronizes the modular permission seeders with the database, ensuring the
  security posture matches the System Requirements Specification requirements.

---

_Utilizing these standardized tools is mandatory to maintain the structural integrity and
predictability of the Internara system._
