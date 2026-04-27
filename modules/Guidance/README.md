# Guidance Module

The `Guidance` module formalizes the onboarding and briefing process for students, managing official
institutional handbooks and tracking student readiness.

> **Governance Mandate:** This module strictly adheres to the **3S Doctrine** (Secure, Sustain,
> Scalable) and the **Modular Domain-Driven Design (DDD)** architecture. All implementations must
> preserve its Bounded Context isolation and maintain Documentation Parity (Sync or Sink).

---

## Purpose

- **Instructional Centralization:** A single source of truth for official school manuals and
  internship guidelines.
- **Readiness Verification:** Ensuring students have read and understood institutional policies
  before beginning active duties.
- **Administrative Flexibility:** Global toggles to enable or disable briefing requirements based on
  school readiness.

## Key Features

### 1. Handbook Management (Admin)

- **Document Hub:** Staf can upload PDF guidelines integrated with the `Media` module.
- **Version Control:** Tracking of multiple versions of instructional materials.
- **Mandatory Gating:** Ability to mark specific handbooks as "Mandatory," which impacts system
  gating logic.

### 2. Guidance Hub (Student)

- **One-Stop Access:** Clean, card-based interface for students to download resources.
- **Acknowledgement Loop:** Simple digital "Read & Agree" mechanism for students.
- **Secure Streaming:** Authenticated file downloads from private storage.

### 2. Services

- **GuidanceService**: Orchestrates mentoring relationships and site-visit logs.
- _API_: `recordVisit(teacherId, studentId, report)`, `getGuidanceHistory(studentId)`.
- _Contract_: `Modules\Guidance\Services\Contracts\GuidanceService`.

---

_The Guidance module ensures that every student begins their internship with a verifiable foundation
of institutional knowledge._
