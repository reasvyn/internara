# Journal Module

The `Journal` module manages the daily activity tracking (Logbook) for students.

> **Spec Alignment:** This module fulfills the **Student Progress Monitoring** requirements of the
> **[Internara Specs](../../docs/internal/internara-specs.md)** (Section 2), documenting student
> activities and competency achievements.

## Purpose

- **Activity Tracking:** Systematic record of student daily tasks and reflections.
- **Supervision:** Facilitates the recording of mentoring content by **Instructors** and **Industry
  Supervisors**.
- **Competency Mapping:** Links activities to learning objectives.

## Key Features

### 1. Daily Logbook

- **Attachments:** Secure proof of work (Photos, Documents).
- **i18n:** All log labels and status indicators are localized.
- **Identity:** All journal entries use **UUIDs**.

### 2. Supervision Workflow

- **Approval Logic:** Verified entries are locked to maintain historical integrity.
- **Dual Verification:** Supports feedback from both academic and industry supervisors.
- **Academic Scoping:** Automatically scoped to the active cycle via `HasAcademicYear`.

### 3. Mobile-First Experience

- **Daily Logging:** Optimized interface for students to record logs on-site.
- **Quick Review:** Streamlined workflow for supervisors to approve logs via mobile devices.

---

_The Journal module provides the narrative evidence of a student's internship journey._
