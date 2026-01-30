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

- **Objective**: Perform full-spectrum static analysis and formatting using Laravel Pint and
  automated analysis tools. Ensures compliance with
  **[Conventions and Rules](conventions-and-rules.md)**.

---

## 2. Modular Artifact Generators

Generators are engineered to respect the **Architecture Description** invariants and the
**src-Omission** namespace rule.

### 2.1 Service Construction

```bash
php artisan module:make-service {ServiceName} {ModuleName}
```

- **Invariant**: Generates a **Contract-First** service structure.

### 2.2 Persistence Construction (Models & Migrations)

```bash
php artisan module:make-model {ModelName} {ModuleName} --migration
```

- **Standard**: Automatically incorporates **UUID Identity** and modular isolation constraints.

---

## 3. System Identity & Orchestration

### 3.1 Configuration Audit (App Info)

```bash
php artisan app:info
```

- **Objective**: Verifies system version, series code, and environment health.

### 3.2 Dependency Synchronization (Refresh Bindings)

```bash
php artisan app:refresh-bindings
```

- **Objective**: Synchronizes the **Auto-Discovery** cache for Service Contracts. Mandatory after
  modifying the service layer.

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
