# Final Internship Report («Laporan PKL»)

**Event:** Writing, submitting, reviewing, and grading the student's final internship report.

**Phase:** 4 — Operations / 5 — Assessment & Evaluation (overlaps both)

**Previous Event:** [Logbook Workflow](logbook-workflow.md)

**Next Event:** [Presentation & Seminar](presentation-seminar.md)

---

## Overview

The final internship report (Laporan PKL) is a formal document that students write to summarise their internship experience. It follows a structured chapter outline, goes through teacher-guided revision rounds, and receives a final grade.

The report workflow is per-student, per-registration. It runs in parallel with daily operations and feeds into the final assessment.

## Trigger

- Internship has been active for a configured minimum duration (optional)
- Student begins drafting their report

## Pre-conditions

- Student has an **active** registration
- User is logged in with appropriate role

## Actors

| Actor | Role | Can create draft | Can submit | Can request revision | Can approve | Can grade |
|---|---|---|---|---|---|---|
| Student | STUDENT | Yes (own) | Yes (own) | No | No | No |
| Teacher | TEACHER | No | No | Yes | Yes | Yes |
| Admin | ADMIN, SUPER_ADMIN | No | No | Yes | Yes | Yes |
| Supervisor | SUPERVISOR | — | — | — | — | — |

> Supervisor input is not required. The `supervisor_notes` field exists on the report as an optional informational field, but it cannot block approval. Supervisors can add notes via `supervisor/reports/notes`.

---

## Report Status Lifecycle

```
DRAFT ──► SUBMITTED ──► APPROVED (terminal)
              │
              └──► REVISION_REQUIRED ──► DRAFT (resubmit loop)
```

Defined in `App\Enums\Report\ReportStatus`.

---

## Event A: Student Writes Report (DRAFT)

### Flow

```
Student → Reports → My Report → Write → Save as Draft
```

The student writes their report. The content is stored as JSON in the `content` field. Drafts can be saved and edited later.

| Action | Class |
|---|---|
| Create report | `App\Actions\Report\CreateReportAction` |
| Submit report | `App\Actions\Report\SubmitReportAction` |

---

## Event B: Teacher Reviews

### Flow

```
Teacher → Reports → Pending Review → Select Report → Grade / Request Revision
```

| Action | Class |
|---|---|
| Approve and grade | `App\Actions\Report\ApproveReportAction` |
| Request revision | `App\Actions\Report\RequestReportRevisionAction` |

### Grade

Teacher can approve with an optional score (0-100) and feedback. Score is not required — report can be approved without a numeric grade.

### Request Revision

Teacher sets status to `REVISION_REQUIRED` and provides feedback. A `ReportRevision` record is created with round number. Student revises and resubmits.

---

## Event C: Supervisor Notes (Optional)

Supervisors can add optional notes via `supervisor/reports/notes`:

| Action | Class |
|---|---|
| Add notes | `App\Actions\Report\AddSupervisorReportNotesAction` |

**This is purely informational.** The teacher can approve the report without any supervisor notes.

---

## Models

| Model | Table |
|---|---|
| `App\Models\Report` | `reports` |
| `App\Models\ReportRevision` | `report_revisions` |

## Actions

| Action | Purpose |
|---|---|
| `CreateReportAction` | Creates a new report in DRAFT status |
| `SubmitReportAction` | Submits report (DRAFT → SUBMITTED) with chapter content |
| `ApproveReportAction` | Approves and optionally grades (SUBMITTED → APPROVED) |
| `RequestReportRevisionAction` | Requests revision (SUBMITTED → REVISION_REQUIRED), logs round |
| `AddSupervisorReportNotesAction` | Saves optional supervisor notes |

## Livewire Components

| Component | Route | View |
|---|---|---|
| `App\Livewire\Report\Student\ReportWriter` | `student/reports` (name: `student.reports`) | `livewire.report.student.report-writer` |
| `App\Livewire\Report\Teacher\ReportReview` | `admin/reports` (name: `admin.reports`) | `livewire.report.teacher.report-review` |
| `App\Livewire\Report\Supervisor\ReportNotes` | `supervisor/reports/notes` (name: `supervisor.reports.notes`) | `livewire.report.supervisor.report-notes` |

## Key Rules

| Rule | Enforcement |
|---|---|
| **One report per registration** | Single report record per registration |
| **Approved is terminal** | `ReportStatus::APPROVED->isTerminal()` = true |
| **Score is optional** | Report can be approved without numeric score |
| **Supervisor notes optional** | Never blocks approval |
| **Revision history preserved** | Each revision round is logged in `report_revisions` |

## Supervisor Dependency

**None.** The teacher can fully approve and grade the report without any supervisor input.
