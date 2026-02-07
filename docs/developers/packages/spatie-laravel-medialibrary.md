# Spatie MediaLibrary: Asset Management Orchestration

This document formalizes the integration of the `spatie/laravel-medialibrary` package, which powers
the **Document & Media Orchestration** baseline for the Internara project. It defines the technical
customizations required to maintain modular sovereignty and UUID-based identification for system
assets.

---

## 1. Technical Baseline (Asset View)

Internara utilizes a specialized configuration of the media engine to satisfy the document
management requirements defined in the **[System Requirements Specification](../specs.md)**.

### 1.1 Identity Invariant: Polymorphic UUID Support

The persistence baseline for media records is configured to utilize a `string` based `model_id` to
ensure compatibility with the system's **UUID v4** identity standard.

- **Protocol**: All domain entities utilize UUIDs; the media subsystem must facilitate these
  polymorphic relationships without concrete ID coupling.

### 1.2 Resource Sovereignty (Module metadata)

The media schema is enhanced with a `module` attribute to establish clear domain boundaries for
uploaded artifacts.

- **Traceability**: Enables administrative grouping and forensic auditing of assets based on their
  originating business domain (e.g., `Internship`, `Profile`).

---

## 2. Implementation Invariants

### 2.1 Model Integration

Domain entities requiring asset attachment must implement the `HasMedia` contract and utilize the
`InteractsWithMedia` concern.

- **Standard**:
    ```php
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('domain_scope')->singleFile();
    }
    ```

### 2.2 Orchestration via Service Layer

Persistence of media artifacts must be orchestrated through the **Service Layer** to ensure
compliance with **[Data Integrity Protocols](../patterns.md)**.

---

## 3. UI Layer Synchronization

Integration with the presentation layer must utilize standardized MaryUI components to ensure
responsive, **Mobile-First** document capturing.

- **Verification Invariant**: Successful asset persistence and retrieval must be verified via
  **`composer test`**.

---

_By strictly governing the media engine, Internara ensures a resilient, traceable, and secure asset
management posture that satisfies stakeholder requirements for digitized document orchestration._
