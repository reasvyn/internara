# Application Blueprint: v0.1.0 (Genesis)

**Series Code**: `ARC01-INIT-01` **Status**: `Archived` (Released)

> **Spec Alignment:** This blueprint establishes the **Modular Monolith** architecture required to
> support the long-term scalability goals of the **[Internara Specs](../../../internara-specs.md)**.

---

## 1. Core Problem Statement

Standard applications suffer from "Monolithic Decay." We need strict domain separation.

**Goal**: Establish the Modular Monolith foundation.

---

## 2. Architectural Impact

### 2.1 Infrastructure: Modular Bootstrapping

- **Structure**: Adoption of `nwidart/laravel-modules`.
- **Constraint**: All domains reside in `modules/`.

### 2.2 Domain: UI Centralization

- **New Module**: `UI`.
- **Scope**: **TALL Stack** encapsulation (Specs 5).

---

## 3. UI/UX Strategy

- **Mobile-First:** Base layouts configured for responsiveness.
- **i11n:** Localization infrastructure setup.

---

## 4. Exit Criteria

- [x] **Modular Autoloading**: Auto-discovery active.
- [x] **Asset Pipeline**: Vite bundling correct.
- [x] **Spec Verification**: Tech stack aligns with Specs.

---

## 5. vNext Roadmap (v0.2.0)

- **Shared Utilities**: Common traits.
- **RBAC**: Modular permissions.
