# Attendance Module

The `Attendance` module manages student presence tracking during the internship period. It provides
a simple check-in/check-out workflow with automatic status determination.

## Purpose

- **Presence Tracking:** Ensures students are attending their internship placements as scheduled.
- **Compliance Monitoring:** Provides supervisors with data to verify student adherence to working
  hours.
- **Grading Data:** Serves as a primary source for "Discipline" and "Professionalism" assessments.

## Key Features

### 1. Daily Check-in/Check-out

- **Clock In:** Students record their start time. The system automatically marks the entry as `Late`
  if performed after the threshold defined in the `attendance_late_threshold` setting.
- **Dynamic Thresholds:** Start times and late thresholds are manageable via the `Setting` module,
  eliminating hardcoded values.
- **Academic Scoping:** Every attendance log is associated with the `active_academic_year`, allowing
  for accurate multi-year reporting and grading.
- **Integrity Rules:**
    - Only one attendance log is allowed per day.
    - Students must clock in before they can clock out.
    - Check-out is restricted to the same day as check-in.

### 2. Supervisor Monitoring

- **Real-time Overview:** Teachers and Mentors can view the attendance logs of their assigned
  students. Access is enforced via `AttendancePolicy`.
- **History Log:** A comprehensive table view for tracking attendance trends over time.
- **Advanced Filtering:** Supervisors can filter attendance logs by student name, and both students
  and supervisors can filter by date range (`date_from` and `date_to`).

### 3. Dashboard Widget

- **Attendance Manager:** A prominent widget on the Student Dashboard providing one-click access to
  clocking in/out and displaying the current day's status.

## Architecture

- **Service Layer:** `AttendanceService` handles all business logic, including "Late" status
  determination.
- **Contract:** `Modules\Attendance\Services\Contracts\AttendanceService`.
- **Model:** `AttendanceLog` (UUID).
- **Validation:** Enforced at the service layer to prevent duplicate entries and invalid state
  transitions.
