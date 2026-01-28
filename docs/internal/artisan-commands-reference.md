# Artisan Commands Reference: Support Tooling Standards

This document formalizes the **Support Tooling** reference for the Internara project, adhering to
**ISO/IEC 12207** (Infrastructure and Support Processes). It provides a technical catalog of custom
Artisan commands engineered to enforce **Architectural Invariants** and streamline the construction
of modular artifacts.

> **Governance Mandate:** All software artifacts generated via these tools must strictly comply with
> the **[Internara Specs](../internal/internara-specs.md)** and the
> **[Code Quality Standardization](code-quality-standardization.md)**.

---

## 1. Modular Artifact Generators

Generators are engineered to respect the **Modular Monolith** hierarchy and the **src-Omission**
namespace invariant.

### 1.1 Service Construction
```bash
php artisan module:make-service {ServiceName} {ModuleName}
```
- **Invariant**: Generates a **Contract-First** service structure, including the interface in the
  `Contracts/` subdirectory.

### 1.2 Persistence Construction (Models & Migrations)
```bash
php artisan module:make-model {ModelName} {ModuleName} --migration
```
- **Standard**: Automatically incorporates the **UUID Identity** concern.
- **Data Integrity**: Migrations are configured for modular isolation (No physical foreign keys
  across boundaries).

### 1.3 UI Construction (Livewire Components)
```bash
php artisan module:make-livewire {ComponentName} {ModuleName}
```
- **UI Standard**: Scaffolds components with responsive, **Mobile-First** layout wrappers and
  standardized MaryUI components.

---

## 2. System Identity & State Orchestration

### 2.1 Configuration Audit (App Info)
```bash
php artisan app:info
```
- **Objective**: Verifies the current system version, **Series Code**, maturity stage, and
  environmental health (PHP/Laravel versions).

### 2.2 Dependency Synchronization (Refresh Bindings)
```bash
php artisan app:refresh-bindings
```
- **Objective**: Synchronizes the **Auto-Discovery** cache for cross-module Service Contracts.
  Mandatory after adding new services or contracts.

---

## 3. Security & Access Control

### 3.1 Permission Baseline Synchronization
```bash
php artisan permission:sync
```
- **Objective**: Synchronizes the **RBAC Baseline** defined in the `Core` and feature modules with
  the database. Ensures User Roles match the authoritative specification.

---

## 4. Maintenance & Evolution

### 4.1 System Clean-up
```bash
php artisan app:flush-cache
```
- **Objective**: Clears all application, modular, and configuration caches to ensure a clean state
  for verification.

---

_Utilizing these standardized generators is mandatory to maintain the structural integrity and
predictability of the Internara modular monolith ecosystem._