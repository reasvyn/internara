# Spatie Activitylog: Forensic Audit Orchestration

This document formalizes the integration of the `spatie/laravel-activitylog` package, which powers
the **Systemic Auditability** baseline for the Internara project. It defines the technical protocols
required to satisfy the **Traceability** mandates of the authoritative
**[System Requirements Specification](../specs.md)**.

---

## 1. Technical Baseline (Audit View)

Internara utilizes a specialized configuration of the activity engine to ensure that all
state-altering operations are recorded within a forensic technical record.

### 1.1 Modular Identity Invariant (UUID)

The persistence baseline for activity logs is encapsulated within the `Log` module and configured to
utilize **UUID v4** primary keys.

- **Implementation**: The system utilizes a customized `Activity` model that supports polymorphic
  relations with UUID-based domain entities.

### 1.2 Contextual Traceability

Logs are enhanced with modular metadata to establish domain sovereignty.

- **Attribute**: A `module` identifier is captured for every log entry to facilitate cross-module
  analysis and administrative oversight.

---

## 2. Implementation Invariants

### 2.1 Automated Entity Tracking

Domain models must track state changes through the application of the `LogsActivity` concern.

- **Invariant**: Only "Dirty" (modified) attributes should be recorded to minimize log bloat.
- **Protocol**: Configuration must utilize `logOnlyDirty()` and `dontSubmitEmptyLogs()`.

### 2.2 Manual Orchestration (Event-Based Audit)

For non-persistence actions (e.g., Report Generation, Authentication events), use the manual
activity orchestrator.

- **Standard**:
    ```php
    activity()->performedOn($entity)->causedBy($user)->log('domain_action');
    ```

---

## 3. Privacy & Protection Invariants (ISO/IEC 27034)

- **Sanitization**: Sensitive data (PII) must be masked within log payloads to comply with security
  requirements.
- **Integrity**: Audit logs are considered immutable baselines and must not be modified after
  persistence.

---

_By strictly governing the audit engine, Internara ensures a high-fidelity technical record that
satisfies stakeholder requirements for accountability and forensic transparency._
