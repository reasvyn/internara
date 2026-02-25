# Application Blueprint: Instructional Execution (ARC01-GAP-01)

**Series Code**: `ARC01-GAP` | **Scope**: `Instructional Monitoring`

---

## 1. Strategic Context

- **Spec Alignment**: This blueprint authorizes the construction of the monitoring and activity
  tracking subsystem required to satisfy **[SYRS-F-201]** (Real-time tracking) and **[SYRS-F-202]**
  (Mentoring documentation).
- **Objective**: Capture the daily pulse of the internship through high-fidelity activity logging,
  attendance verification, and site-visit documentation.
- **Rationale**: Monitoring is the primary tool for instructional quality control. By digitizing
  journals and guidance logs, we ensure that student progress is traceable and verifiable by
  instructors, industry mentors, and institutions.

---

## 2. Logic & Architecture (Systemic View)

### 2.1 Capabilities

- **Vocational Logging**: Systematic recording of daily student tasks and claimed competencies.
- **Presence Verification**: Secure temporal and spatial (GPS) tracking of student attendance at
  placement locations.
- **Mentoring Documentation**: Formal logging of site-visits and supervisory feedback between
  instructors and students.

### 2.2 Service Contract Specifications

- **`Modules\Journal\Services\Contracts\JournalService`**: Capturing daily activity descriptions,
  competency mapping, and industrial validation.
- **`Modules\Attendance\Services\Contracts\AttendanceService`**: Handling real-time check-in/out
  logic and retrospective attendance justifications.
- **`Modules\Guidance\Services\Contracts\GuidanceService`**: Orchestrating the mentoring
  relationship and site-visit report persistence.

### 2.3 Data Architecture

- **Temporal Invariant**: Attendance and Journal entries strictly scoped to the active placement
  window defined in the `Internship` module.
- **Identity Protocol**: Mandatory use of **UUID v4** for all transactional records.
- **Privacy Hardening**: Encryption of GPS coordinates and qualitative supervisory comments to
  protect student privacy.

---

## 3. Presentation Strategy (User Experience View)

### 3.1 UX Workflow

- **Rapid Activity Entry**: A one-handed mobile UI for students to record logs quickly while
  on-site.
- **Reactive Presence Tracking**: A single-button interface for attendance with automatic location
  detection.
- **Supervision Feed**: A chronological activity stream for instructors to monitor their assigned
  students' progress in real-time.

### 3.2 Interface Design

- **Thin Monitoring Components**: Livewire components designed for high-frequency interaction using
  MaryUI.
- **Mobile-First Priority**: Ensuring that all monitoring forms are fully functional on standard
  smartphones.

---

## 4. Verification Strategy (V&V View)

### 4.1 Unit Verification

- **Submission Windows**: Verification of logic enforcing timely logging (e.g., 7-day submission
  limit).
- **Attendance Formulas**: Unit tests for calculating duration and lateness status.

### 4.2 Feature Validation

- **Concurrency Testing**: Verification of data integrity during high-volume morning attendance
  spikes.
- **Audit Masking**: Validation that PII in guidance logs is correctly redacted before reaching
  general reporting sinks.

### 4.3 Architecture Verification

- **Coupling Audit**: Pest Arch tests ensuring that the `Journal` and `Attendance` modules do not
  depend on each other directly.

---

## 5. Compliance & Standardization (Integrity View)

### 5.1 Privacy Protocols

- **Masking Mandate**: Sensitive student reflections and feedback must be masked in logs via the
  `Log` module.

### 5.2 a11y (Accessibility)

- **Keyboard Presence**: Ensuring that attendance buttons and journal forms are fully operable via
  keyboard shortcuts.

### 5.3 i18n & Localization

- **Multi-language Feedback**: All validation errors and status descriptions localized for
  Indonesian institutional habits.

---

## 6. Documentation Strategy (Knowledge View)

### 6.1 Engineering Record

- **Monitoring Taxonomy**: Documentation of the instructional loop and scoring formulas in
  `governance.md`.

### 6.2 Stakeholder Manuals

- **Logbook Guide**: Tutorial for students on how to claim competencies and submit journals.

### 6.3 Release Narration

- **Instructional Milestone**: Highlighting the system's ability to provide real-time transparency
  into the vocational learning process.

### 6.4 Strategic GitHub Integration

- **Issue #Gap1**: Implementation of Daily Journaling and Activity services.
- **Issue #Gap2**: Development of Attendance verification and location-based logic.
- **Issue #Gap3**: Construction of Teacher Guidance and Site-Visit documentation.
- **Milestone**: ARC01-GAP (Instructional Baseline).

---

## 7. Exit Criteria & Quality Gates

- **Acceptance Criteria**: Daily journaling active; attendance tracking operational; guidance logs
  persistent.
- **Verification Protocols**: 100% pass rate in the monitoring test segment.
- **Quality Gate**: Minimum 90% behavioral coverage for the `JournalService`.

---

_Application Blueprints prevent architectural decay and ensure continuous alignment with the
foundational specifications._
