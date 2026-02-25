# Data Integrity Protocol: SLRI Implementation Guide

This document formalizes the **Software-Level Referential Integrity (SLRI)** protocols for the 
Internara project, adhering to **ISO/IEC 25010** (Reliability). It defines the engineering 
patterns required to maintain data consistency in an environment where physical foreign key 
constraints across module boundaries are **strictly prohibited**.

---

## 🏛️ The SLRI Principle: Sovereignty vs. Consistency

In a Modular Monolith, each module must be physically portable. Traditional database foreign 
keys (FKs) across modules create "Hard Coupling" that prevents independent testing and 
migration. SLRI shifts the responsibility of integrity from the Database Engine to the 
**Service Layer**.

### 1. The SLRI Invariant
- **Rule**: Physical FKs are allowed *internal* to a module.
- **Rule**: Cross-module relationships MUST use indexed **UUID** columns without physical 
  database constraints.
- **Authority**: The "Owner" module of a data entity is the ONLY authority for its existence 
  and state.

---

## 🔄 Pattern 1: Integrity Verification (The PEP Check)

When a module creates or updates a record that references a foreign module (e.g., an 
`Internship` record referencing a `User` UUID), it must verify the existence of that identity.

### 1.1 The Protocol
1.  Inject the **Service Contract** of the target module.
2.  Invoke the `exists()` or `find()` method before persistence.
3.  Throw a semantic exception if the reference is invalid.

```php
// Correct Implementation in InternshipService
public function register(array $data): string
{
    // 1. Verify foreign identity via Contract (not Model!)
    if (! $this->userService->exists($data['student_id'])) {
        throw new UserNotFoundException(__('user::exceptions.not_found'));
    }

    return $this->model->create($data)->uuid;
}
```

---

## 🗑️ Pattern 2: Cascading & Cleanup (Event-Driven)

Since the database won't automatically clean up related records across modules, we utilize 
**Domain Events** to orchestrate asynchronous cleanup.

### 2.1 The "Past Tense" Event
When a parent record is deleted, dispatch an event.

```php
// In SchoolService
public function delete(string $uuid): bool
{
    $deleted = $this->model->where('uuid', $uuid)->delete();
    
    if ($deleted) {
        event(new SchoolDeleted($uuid));
    }
    
    return $deleted;
}
```

### 2.2 The Isolated Listener
Other modules (e.g., `Department`) listen for this event and perform their own internal 
cleanup logic via their own services.

---

## 🛠️ Pattern 3: Handling Orphaned Records

Despite best efforts, software-level integrity can occasionally drift. Internara employs a 
"Defensive Querying" strategy.

### 3.1 Graceful Degradation
When retrieving a list of records with foreign references, the Service Layer should be 
prepared for "Missing Anchors."

- **Pattern**: If a referenced UUID no longer exists, the UI should render a "Redacted" or 
  "Deleted Entity" placeholder instead of crashing with a `NullPointerException`.

---

## 🧪 Verification & Audit

Compliance with SLRI is verified through:

1.  **Architecture Police**: `tests/Arch.php` ensures no physical FKs are declared in 
    migrations targeting other module namespaces.
2.  **Integrity Tests**: Every service method that accepts a foreign UUID MUST have a test 
    case for the "Invalid Reference" scenario.

---

_Data is the systemic memory of Internara. By following this protocol, we ensure that memory 
remains consistent, portable, and secure._
