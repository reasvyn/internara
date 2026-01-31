# Guidance Module

The `Guidance` module formalizes the onboarding and briefing process for students, managing official
institutional handbooks and tracking student readiness.

> **Spec Alignment:** This module satisfies the **Stakeholder Onboarding** requirements by providing
> a structured hub for instructional materials and a digital trail of student acknowledgements.

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

### 3. Systematic Gating

- **Integrity Invariant:** Automatically locks access to critical features (Journals, Attendance,
  Assignments) if mandatory guidelines have not been acknowledged.
- **Monitoring Table:** Real-time rekapitulasi for staff to monitor student compliance.

---

_The Guidance module ensures that every student begins their internship with a verifiable
foundation of institutional knowledge._
