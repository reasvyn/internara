# Detailed Design (DD): Modular Internal Mechanics

This document provides the **Detailed Design (DD)** for the Internara system, standardized 
according to **ISO/IEC/IEEE 12207**. It elaborates on the internal mechanisms, design patterns, and 
module-level orchestration.

---

## 1. Design Philosophy: Domain Sovereignty

Internara’s detailed design is governed by the principle of **Domain Sovereignty**. Each module is 
a self-contained unit of logic, persistence, and presentation.

### 1.1 The Aggregate Pattern (DDD)
Domain models are treated as **Aggregates**. Any state modification must occur through its 
designated **Orchestration Service** to ensure domain invariants are enforced centrally.

---

## 2. Module-Level Orchestration Patterns

### 2.1 The Dual-Service Model (CQRS-Lite)
- **Query Services (`EloquentQuery`)**: Optimized for retrieval and standardized API provision.
- **Orchestration Services (`BaseService`)**: Process Managers responsible for complex workflows, 
  transactions, and event dispatching.

### 2.2 Data Transfer Protocols (DTOs)
Complex inputs MUST be encapsulated in **Read-only DTOs** (PHP 8.4+) to ensure type safety and 
eliminate primitive obsession.

### 2.3 Transactional Integrity Protocols
Multi-module operations must be encapsulated within a `DB::transaction()` in the primary service 
to ensure atomicity.

---

## 3. Inter-Module Interaction Protocols

### 3.1 Service Contracts (InterfaceRegistry)
Every module exposes capabilities through an **Interface** in `src/Services/Contracts/`. Modules 
must interact ONLY through these contracts.

### 3.2 Event-Driven Side-Effects
Cross-module side-effects are handled asynchronously via **Domain Events**.
- **Semantics**: Named in past tense (e.g., `JournalApproved`).
- **Payload**: Lightweight (UUID-only) to prevent serialization issues.
- **Isolation**: Listeners must not access models from foreign modules directly.

---

## 4. Presentation & UI Orchestration

### 4.1 Livewire Boundary Controllers
Livewire components focus on UI state and delegate logic to Service Contracts.
- **State**: Use Livewire v3 `Forms` for validation.
- **Composition**: UI integration via **Slot Injection** prevents direct view coupling.

### 4.2 Notification & Feedback
- **Transient UI Feedback**: Dispatched via the `Notifier` service for toasts.
- **Persistent Notifications**: Dispatched via the `NotificationService` (System/Email).

---

## 5. Software-Level Referential Integrity (SLRI)

Because Internara prohibits cross-module physical foreign keys, consistency is managed at the 
Service Layer.

### 5.1 The PEP Check (Policy Enforcement Point)
Services must verify the existence of foreign entities via their respective Service Contracts 
before creation or assignment.

### 5.2 Deletion & Restriction Protocols
- **Restrict**: Block deletion if dependencies exist elsewhere.
- **Cascade (Logic)**: Trigger a domain event to allow other modules to clean up data.

### 5.3 Graceful Degradation
UI components must be prepared for "Missing Anchors" (orphaned records) and render placeholders 
instead of failing.

---

## 6. Functional Life-Cycle Orchestration

### 6.1 The Instructional Loop
1.  **Logging**: Student records a **Journal Entry**.
2.  **Telemetry**: Presence is verified via **Attendance Logs**.
3.  **Audit**: Mentor/Teacher validates the entry and competency achievement.
4.  **Synthesis**: Data is aggregated into a **Participation Score**.

### 6.2 Task Fulfillment & Gating
- **Dynamic Engine**: `AssignmentTypes` are instantiated for each program.
- **Certification Gating**: Program completion is blocked until all mandatory assignments are 
  `Verified`.

### 6.3 Reporting Orchestration
- **Pattern**: `ExportableDataProvider` allows modules to provide data without direct coupling.
- **Generation**: Large reports are generated asynchronously via Queues.

---

## 7. System Orchestration Standards

### 7.1 Runtime Configuration (Setting Helper)
- **Authoritative Source**: Global `setting()` helper with Redis caching.
- **Resilience**: `Core` module provides fallbacks for early boot cycles.

### 7.2 Persistence Invariants
- **Identity**: Universal **UUID v4**.
- **Auditing**: State changes captured via `InteractsWithActivityLog`.
- **Soft Deletes**: Mandatory for high-value domain entities.
