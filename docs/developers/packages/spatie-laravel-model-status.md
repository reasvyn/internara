# Spatie Model Status: Entity Lifecycle Orchestration

This document formalizes the integration of the `spatie/laravel-model-status` package, which powers
the **Entity Lifecycle** baseline for the Internara project. It establishes the technical protocols
required to track state transitions and maintain an immutable audit trail of domain status changes.

---

## 1. Rationale: Systemic Auditability

Internara prioritizes forensic transparency for all operational entities.

- **Audit Invariant**: Utilization of a dedicated `statuses` table to decouple lifecycle state from
  primary domain attributes.
- **Traceability**: Captured metadata includes the subject responsible for the transition and the
  analytical justification (`reason`).

---

## 2. Construction Invariants (Implementation View)

### 2.1 Domain Application

Entities with non-binary lifecycles (e.g., `Internship`, `Assessment`) must implement the
`HasStatuses` concern.

- **Requirement**: Entities must demonstrate compliance with the state transition rules defined in
  the **[Architecture Description](../architecture.md)**.

### 2.2 Orchestrating Transitions

Status modifications must be accompanied by semantic reasoning to ensure data quality.

- **Standard**:
    ```php
    $entity->setStatus('baseline_state', 'Semantic justification for transition');
    ```

---

## 3. Analytical Query Invariants

To ensure high-performance data orchestration, services must utilize the provided query scopes.

- **Scoping**: Use `whereStatus()` and `whereNotStatus()` to filter data grids at the database
  layer.
- **Validation**: Transition logic must verify that the requested state is valid for the current
  domain context.

---

## 4. Integration with V&V

All state machine transitions must be verified via **`composer test`**.

- **Test Invariant**: Verification must confirm that unauthorized roles are prohibited from
  triggering restricted status transitions.

---

_By strictly governing lifecycle orchestration, Internara ensures that the system state remains
predictable, auditable, and aligned with stakeholder requirements for operational transparency._
