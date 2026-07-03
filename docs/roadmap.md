# Roadmap — Reports Module Overhaul: Grade Card Purification

> **Last updated:** 2026-07-03
> **Changes:** replace completed Core + Settings roadmaps with active Reports overhaul

> **Status:** In progress
> **Target:** Report module — remove all student thesis/written-report concepts, simplify to pure final grade card with DRAFT->FINALIZED workflow
> **Dependencies:** Completed: Core module hardening (12 issues), Settings module hardening (15 issues)

---

## Completed Roadmaps

| Initiative | Issues | Status |
|------------|--------|--------|
| Core module hardening | 12 issues (#208-#219) | All resolved |
| Settings module hardening | 15 issues (#220-#234) | All resolved |

---

## 1. Overview

The Report module audit revealed that the module conflates two distinct concepts:
final grade card (correct) and student written report/thesis (belongs in Assignment).
This roadmap strips all written-report infrastructure and restores the module to its
intended function: storing final scores, grade letter, and student snapshot.

Per AGENTS.md Critical Rules: Report module is grade card only.

---

## 2. Current State (Open Issues)

### 2.1 HIGH

| # | Issue | File | Impact |
|---|-------|------|--------|
| #244 | Overhaul: hybrid grade-card/writing to pure grade card | Module-wide | Conceptual pollution |
| #235 | Missing FinalizeReportAction | (created) | Workflow stuck at APPROVED -- FIXED |

### 2.2 MEDIUM

| # | Issue | File | Impact |
|---|-------|------|--------|
| #236 | Nested 3-level whereHas in mount() | ReportWriter.php:37 | Deleted in overhaul |
| #237 | CreateReportAction raw array | CreateReportAction.php:13 | No type safety |
| #238 | event() instead of dispatchEvent() | 3 Action files | Events fire before txn commit -- FIXED |
| #239 | ReportController redirect to non-existent route | ReportController.php:34 | Crashes on file-not-found -- FIXED |

### 2.3 LOW

| # | Issue | File |
|---|-------|------|
| #243 | Redundant abort_unless in boot() | ReportWriter.php:32 |
| #240 | json_encode(null) shows "null" in editor | ReportWriter.php:49 |
| #241 | Orphaned ReportFinalized event | Events/ReportFinalized.php -- FIXED |
| #242 | Notification link to placeholder route | Listeners/HandleReportApproved.php:26 |
| #245 | Docs still reference thesis concepts | docs/modules/reports*.md |

---

## 3. Implementation Phases

### Phase 1: Strip Written-Report Infrastructure (P0)

#### Task 1.1 -- Delete obsolete actions and files

| Field | Value |
|-------|-------|
| **Pipeline** | refactor |
| **Module** | Reports |
| **Effort** | Medium |
| **Issue** | #244 |

**Target state:** Only CalculateFinalGradeAction, FinalizeReportAction, GradeCalculated,
ReportFinalized remain. Status is DRAFT to FINALIZED. Fields are grade-card only.

**Design decisions:**
- Delete instead of deprecate: files have zero callers outside the module
- Drop columns title, content, chapter_structure, supervisor_notes from migration
- Move snapshot capture from observer to FinalizeReportAction

**Delete files:**
- Actions: SaveReportDraftAction, SubmitReportAction, ApproveReportAction, AddSupervisorReportNotesAction
- Events: ReportSubmitted, ReportApproved
- Listeners: HandleReportApproved
- Livewire: ReportWriter + report-writer.blade.php
- Tests: SubmitReportActionTest, SaveReportDraftActionTest, ApproveReportActionTest, AddSupervisorReportNotesActionTest, ReportWriterTest

**Update files:**
- Models/Report.php: remove title, content, chapter_structure, supervisor_notes from Fillable/casts
- Enums/ReportStatus.php: keep only DRAFT, FINALIZED
- Observers/ReportObserver.php: snapshot only on FINALIZED
- Actions/FinalizeReportAction.php: add captureSnapshot()
- Routes: remove student routes, placeholder redirect
- Config/event.php: remove ReportApproved mapping
- Migration: drop columns title, content, chapter_structure, supervisor_notes

#### Task 1.2 -- Simplify ReportStatus enum

Two states only: DRAFT, FINALIZED. isTerminal() returns true for FINALIZED.
validTransitions(): DRAFT -> [FINALIZED], FINALIZED -> [].

#### Task 1.3 -- Move snapshot to FinalizeReportAction

Observer currently calls captureSnapshot() on every save. Move to FinalizeReportAction.
Observer only triggers snapshot on FINALIZED status transition.

### Phase 2: Fix Remaining Bugs (P1)

#### Task 2.1 -- Create DTO for CreateReportAction

| Field | Value |
|-------|-------|
| **Pipeline** | refactor |
| **Module** | Reports |
| **Effort** | Small |
| **Issue** | #237 |

Accept typed DTO instead of raw array.

#### Task 2.2 -- Fix minor bugs

| Field | Value |
|-------|-------|
| **Issues** | #240, #243 |

Files already deleted in Phase 1 (ReportWriter and boot issues are moot now).

### Phase 3: Documentation & Cleanup (P2)

#### Task 3.1 -- Update documentation

| Field | Value |
|-------|-------|
| **Pipeline** | docs |
| **Module** | Reports |
| **Effort** | Small |
| **Files** | docs/modules/reports.md, docs/modules/reports-reference.md |
| **Issue** | #245 |

Remove all thesis/written-report references. Update action list, event list, status flow.

#### Task 3.2 -- Close fixed issues

Close #235, #238, #239, #241 with resolution comments.

---

## 4. Testing Strategy

| Test | Type | What It Verifies |
|------|------|------------------|
| FinalizeReportActionTest | Feature | Finalization workflow, snapshot capture |
| CalculateFinalGradeActionTest | Feature | Grade calculation with simplified status |
| ReportStatusTest | Unit | DRAFT to FINALIZED transitions |
| ReportModelTest | Unit | No deleted fields in fillable/casts |

---

## 5. Integration Order

| # | Phase | Task | Depends On |
|---|-------|------|------------|
| 1 | 1 | Delete obsolete files + update model/enum/observer/migration/routes/config | - |
| 2 | 2 | Create DTO for CreateReportAction | 1 |
| 3 | 3 | Update docs, close issues | 1 |

---

## 6. No-Change Zones

| Feature / Area | Reason |
|----------------|--------|
| CalculateFinalGradeAction | Core grade aggregation logic |
| FinalizeReportAction | Already working with snapshot |
| archived_data + industry_feedback fields | Grade-card related |

---

## 7. Next Steps

1. Execute Phase 1 -- Bulk deletion of written-report files, model/enum cleanup, migration edit
2. Execute Phase 2 -- DTO for CreateReportAction
3. Execute Phase 3 -- Documentation sync + close issues
