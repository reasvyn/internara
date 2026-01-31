# Attendance Module

The `Attendance` module manages student presence tracking during the internship period.

> **Spec Alignment:** This module fulfills the **Student Progress Monitoring** requirements of the
> **[Internara Specs](../../docs/internal/internara-specs.md)** (Section 2), providing real-time
> tracking of student presence and conditions.

## Purpose

- **Presence Tracking:** Verifies student attendance at internship locations.
- **Monitoring:** Provides **Instructors** and **Industry Supervisors** with data to verify student
  professionalism.
- **Grading Data:** Source for participation-driven scoring in the `Assessment` module.

## Key Features

### 1. Daily Check-in/Check-out

- **Guidance Gating:** Integrity check to ensure students have acknowledged mandatory guidelines
  prior to first presence.
- **Absence Integrity:** Automatically blocks clock-in if an approved absence (Leave/Sick) exists
  for the day.
- **Dynamic Thresholds:** Late thresholds are managed via the `Setting` module (No hard-coding).

### 2. Absence Management

- **Leave & Sick Requests:** A dedicated workflow for students to request absence with proof of
  documentation.
- **Approval Workflow:** Supervisors can review and approve absence requests, which then integrate
  with attendance stats.

### 3. Supervisor Monitoring

- **Real-time Overview:** Instructors and Supervisors can monitor assigned students.
- **Mobile-First:** Monitoring dashboards are optimized for mobile access.
- **i18n:** All status labels (Present, Late, Absent) are localized.

### 3. Dashboard Widget

- **One-Click Actions:** Simplified UI for students to clock in/out on mobile devices.

---

_The Attendance module ensures accountability and provides evidence of student engagement._
