# Reports — API Reference

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Aggregate-rooted layout mapping for the Reports domain

This reference defines the structured aggregates and code layout within the **Reports** domain.

---

## 1. Report Aggregate
Manages student final reports compilement, PDF attachments, teacher evaluations, and supervisor annotations notes.

- **Eloquent Models**:
  - `Report` (`app/Domain/Reports/Models/Report.php`)
- **Policies**:
  - `ReportPolicy` (`app/Domain/Reports/Policies/ReportPolicy.php`)
- **Command Actions**:
  - `CreateReportAction` (`app/Domain/Reports/Actions/CreateReportAction.php`)
  - `SubmitReportAction` (`app/Domain/Reports/Actions/SubmitReportAction.php`)
  - `ApproveReportAction` (`app/Domain/Reports/Actions/ApproveReportAction.php`)
  - `AddSupervisorReportNotesAction` (`app/Domain/Reports/Actions/AddSupervisorReportNotesAction.php`)
- **HTTP Controllers**:
  - `ReportController` (`app/Domain/Reports/Http/Controllers/ReportController.php`)
- **Livewire UI Components**:
  - `ReportWriter` (`app/Domain/Reports/Livewire/ReportWriter.php`)
  - `ReportReview` (`app/Domain/Reports/Livewire/ReportReview.php`)
  - `ReportNotes` (`app/Domain/Reports/Livewire/ReportNotes.php`)
- **Enums**:
  - `ReportStatus` (`app/Domain/Reports/Enums/ReportStatus.php`)

---

## 2. ReportRevision Aggregate
Tracks requested revision logs, comments histories, and revision numbers.

- **Eloquent Models**:
  - `ReportRevision` (`app/Domain/Reports/Models/ReportRevision.php`)
- **Command Actions**:
  - `RequestReportRevisionAction` (`app/Domain/Reports/Actions/RequestReportRevisionAction.php`)
