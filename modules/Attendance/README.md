# Attendance Module

The `Attendance` module manages student presence tracking during the internship period.

> **Governance Mandate:** This module implements the requirements defined in the authoritative
> **[System Requirements Specification](../../docs/dev/specs.md)**. All implementation must adhere
> to the **[Coding Conventions](../../docs/dev/conventions.md)**.

---

## Purpose

- **Presence Tracking:** Verifies student attendance at internship locations.
- **Monitoring:** Provides **Instructors** and **Industry Supervisors** with data to verify student
  professionalism.
- **Grading Data:** Source for participation-driven scoring in the `Assessment` module.

## Key Features

### 1. Flexible Attendance Recording

- **Adaptive Input:** Support for real-time check-in and retrospective manual entry to accommodate
  field realities (e.g., informal permits).
- **Comprehensive States:** Supports Present (Hadir), Sick (Sakit), Permitted (Izin), and
  Unexplained (Tanpa Keterangan) states.
- **Informal Justification:** Students can provide context/notes for their attendance status.
- **Guidance Gating:** Integrity check to ensure students have acknowledged mandatory guidelines
  prior to first presence.

### 2. Services

- **AttendanceService**: Orchestrates secure temporal and spatial verification.
    - _API_: `mark(studentId, type, location)`, `getSummary(studentId, month)`.
    - _Contract_: `Modules\Attendance\Services\Contracts\AttendanceService`.

### 3. Supervisor Monitoring

- **Real-time Overview:** Instructors and Supervisors can monitor assigned students.
- **Mobile-First:** Monitoring dashboards are optimized for mobile access.
- **i18n:** All status labels (Present, Late, Absent) are localized.

### 3. Dashboard Widget

- **One-Click Actions:** Simplified UI for students to clock in/out on mobile devices.

---

_The Attendance module ensures accountability and provides evidence of student engagement._
