# Known Issues & Limitations

> **Last updated:** 2026-06-12

This document catalogs resolved issues and closed items from previous audit sessions.

---

## Resolved Issues

### Auth

| ID | Issue | Severity | Resolution |
|----|-------|----------|------------|
| A15 | Role enum description updated to 7 cases including MENTOR/MENTEE | Medium | `auth-reference.md` updated |

### SysAdmin

| ID | Issue | Severity | Resolution |
|----|-------|----------|------------|
| C11 | `AnnouncementStatus` missing `StatusEnum` — added `isTerminal()` and `validTransitions()` | Low | Enum updated |

### Assessment

| ID | Issue | Severity | Resolution |
|----|-------|----------|------------|
| A14 | `EvaluatorRole` aligned with user roles (admin, teacher, supervisor, system) | Low | By Design |

### Assignment

| ID | Issue | Severity | Resolution |
|----|-------|----------|------------|
| C9 | View naming mismatch — confirmed correct | Low | Verified |

### Attendance / Journals

| ID | Issue | Severity | Resolution |
|----|-------|----------|------------|
| C9 | Attendance view naming — all references resolve correctly | Low | Verified |

### Certificate

| ID | Issue | Severity | Resolution |
|----|-------|----------|------------|
| E5 | CertificateTemplate missing migration — created | Critical | Model + migration + factory added |

### Evaluation

| ID | Issue | Severity | Resolution |
|----|-------|----------|------------|
| A48 | Old evaluations schema replaced with generic Google Forms-like design | High | 5 new tables, all old code removed |
| A39 | EvaluationCategory removed | Low | Replaced by `evaluation_forms.target_type` |
| E6 | `evaluations.mentor_id` eliminated | Low | Replaced by generic `target_type`+`target_id` |

### Program

| ID | Issue | Severity | Resolution |
|----|-------|----------|------------|
| E7 | `internships.required_document_ids` undocumented | Low | Added to ERD |

### Reports

| ID | Issue | Severity | Resolution |
|----|-------|----------|------------|
| E1 | Report phantom fillable columns removed | Critical | 8 columns removed from `$fillable` |
| E2 | Reports `archived_data` note clarified | Low | ERD optimization note updated |

### Cross-Cutting

| Issue | Resolution |
|-------|------------|
| Routes sections in module ref docs | Added to all 16 reference files |
| Views sections in module ref docs | Added to all 16 reference files |
| Tests sections in module ref docs | Added to all 16 reference files |
| Factories sections in module ref docs | Added to all 16 reference files |
| Migrations sections in module ref docs | Added to all 16 reference files |

### Infrastructure

| ID | Issue | Resolution |
|----|-------|------------|
| C1 | Read Actions extending BaseAction | Verified — all 9 were already plain classes |
| C2 | Actions missing `execute()` | Added to `GenerateAccountSlipAction` and `CompileLogbookReportAction` |
| C4 | AnnouncementManager → BaseRecordManager | Refactored (RubricManager/AttendanceManager left as-is — not CRUD lists) |
| C8 | `config/mary.php` non-existent class | Created `app/Core/Support/Spotlight.php` |
| C10 | Missing entity accessor methods | Verified — both already existed |
