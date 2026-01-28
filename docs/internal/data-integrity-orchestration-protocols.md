# Data Integrity & Orchestration Protocols

This document formalizes the **Software-Level Referential Integrity (SLRI)** protocols for the
Internara project, adhering to **ISO/IEC 25010** (Reliability). It defines the engineering standards
required to maintain data consistency in a modular monolith environment where physical foreign key
constraints across module boundaries are prohibited.

> **Governance Mandate:** All service-layer persistence logic must demonstrate adherence to these
> integrity protocols to satisfy the
> **[Code Quality Standardization](code-quality-standardization.md)**.

---

## 1. The SLRI Principle (Software-Level Integrity)

Because Internara enforces **Strict Modular Isolation**, data relationships across modules must be
orchestrated by the **Service Layer** rather than the database engine.

- **Invariant**: The "Parent" module's Service Contract is the authoritative source for validating
  the existence of a foreign entity.
- **Protocol**: Never query a foreign module's model directly to verify existence; utilize the
  designated Service Contract.

---

## 2. Integrity Verification Patterns

### 2.1 Creation & Assignment Verification

When creating a record that refers to a foreign module (e.g., assigning a Student to a Placement),
the service must verify the foreign entity's existence.

```php
public function create(array $data): Model
{
    // Verification: Utilize the foreign Service Contract to validate the identity
    if (! $this->studentService->exists($data['student_id'])) {
        throw new EntityNotFoundException(__('student::exceptions.not_found'));
    }

    return parent::create($data);
}
```

### 2.2 Deletion & Restriction Protocols

To prevent "Orphaned Records," services must implement one of the following strategies during
deletion:

- **Restrict**: Prevent deletion if dependent records exist in other modules.
- **Cascade (Logic)**: Trigger a domain event (e.g., `InternshipDeleted`) to allow other modules to
  clean up their related data asynchronously.
- **Nullify**: Update foreign references to `null` if the relationship is optional.

---

## 3. Transactional Orchestration

Multi-module operations that alter the state of multiple entities must be encapsulated within a
database transaction to ensure **Atomicity**.

- **Standard**: Utilize `DB::transaction()` within the primary orchestrating Service.
- **Scope**: Transactions should be kept as short as possible to minimize lock contention.

---

## 4. Query Scoping & Performance

Since physical joins are prohibited across module boundaries, utilize the following patterns for
data retrieval:

- **Eager Loading (Internal)**: Use `with()` only for relationships within the same module.
- **Hydration (External)**: Retrieve external data via Service Contracts and hydrate the result
  collection manually or through UI-level orchestration.

---

_By strictly adhering to these protocols, Internara ensures a reliable and consistent data
ecosystem, preserving the benefits of modularity without sacrificing systemic integrity._
