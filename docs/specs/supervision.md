# Supervision — Supervision Logs, Monitoring Visits, Cross-Role Proxy & Compliance

> **Last updated:** 2026-07-22 **Changes:** feat — split from journals.md; teacher/supervisor oversight:
> supervision log with multi-role review, monitoring visit scheduling, Cross-Role Proxy, compliance monitoring

## Description

Specification of the teacher and supervisor-facing oversight features: supervision logs (mentoring
session records with supervisor review), monitoring visits (field visit scheduling and verification),
Cross-Role Proxy (teacher verification when supervisors are inactive), and compliance monitoring
(automated missing-entry detection with notification escalation).

See also: [daily-activity.md](daily-activity.md) — student daily operations (logbook, attendance,
absence requests).

---

## 1. Problem Statements

### PS-1 — Mentor Supervision Session Tracking

Industry supervisors conduct mentoring sessions with students. These sessions must be documented
with topics, notes, and feedback. Without structured recording, the quality of mentorship is
invisible to school coordinators.

### PS-2 — Monitoring Visit Documentation

Teachers conduct field visits to student workplaces. Visit records (method, location, duration,
observations) must be tracked and verified. Without centralized visit data, program coordinators
cannot assess monitoring coverage.

### PS-3 — Cross-Role Proxy for Unavailable Supervisors

When industry supervisors are inactive (on leave, unresponsive), student logbook submissions and
supervision log reviews get blocked. Teachers must be able to step in as proxy verifiers to
prevent workflow stagnation.

### PS-4 — Compliance Monitoring for Missing Entries

If students skip logbook entries for multiple consecutive days, mentors must be notified. Without
automated compliance checks, missing entries go undetected until it's too late.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Track supervision logs with draft/submitted/reviewed/acknowledged/verified/completed lifecycle |
| G2  | Schedule and verify monitoring visits with multiple method types |
| G3  | Implement Cross-Role Proxy for teacher verification of logbook/supervision entries |
| G4  | Monitor compliance (missing entries) with automated notification escalation |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Video call integration for virtual monitoring visits |
| NG2  | Automated compliance escalation to school administration beyond notification |

---

## 3. User Stories / Use Cases

### UC-1 — Teacher Creates Monitoring Visit

**Actor:** Teacher
**Preconditions:** Teacher is assigned to a student's registration
**Flow:**
1. Teacher navigates to `/monitoring-visits/`
2. `VisitManager` shows existing visits
3. Teacher creates visit: selects student, date, method (site_visit/virtual_meeting/phone_call), location, duration, notes, observations
4. Calls `CreateVisitAction::execute(teacher, registrationId, data)`
**Postconditions:** Monitoring visit record created

### UC-2 — Supervisor Reviews Supervision Log

**Actor:** Industry Supervisor
**Preconditions:** SUBMITTED supervision log exists; supervisor is assigned
**Flow:**
1. Supervisor navigates to `/supervision/logs`
2. `SupervisorReviewManager` shows submitted logs
3. Supervisor reviews, adds feedback, calls `ReviewLogAction::execute(log, supervisor, feedback)`
4. Status transitions SUBMITTED → REVIEWED
**Postconditions:** Log reviewed with feedback

---

## 4. Functional Requirements

### SupervisionLog

| ID   | Requirement |
| ---- | ----------- |
| FR-SL1 | `SupervisionLogStatus` must define: DRAFT, SUBMITTED, REVIEWED, ACKNOWLEDGED, VERIFIED, COMPLETED |
| FR-SL2 | `SupervisionType` must define: GUIDANCE, SUPERVISORING (value='mentoring'), MONITORING |
| FR-SL3 | `CreateSupervisionLogAction` must be role-aware: teacher creates GUIDANCE (auto-verified, COMPLETED); non-teacher creates MENTORING (SUBMITTED) |
| FR-SL4 | `ReviewLogAction` must transition SUBMITTED → REVIEWED with supervisor feedback |
| FR-SL5 | `VerifySupervisionLogAction` must set `is_verified=true` and status to VERIFIED |
| FR-SL6 | `DeleteLogAction` must reject if status is not DRAFT |
| FR-SL7 | `SupervisionLogPolicy` must use `HasMentorProxy` for review authorization |

### MonitoringVisit

| ID   | Requirement |
| ---- | ----------- |
| FR-MV1 | `VisitMethod` must define: SITE_VISIT, VIRTUAL_MEETING, PHONE_CALL |
| FR-MV2 | `CreateVisitAction` must accept teacher, registrationId, and data array |
| FR-MV3 | `VerifyVisitAction` must reject if already verified |
| FR-MV4 | `VisitState` must provide `canBeEdited()`, `canBeDeleted()`, `isRecent()` (within 7 days) |
| FR-MV5 | `MonitoringVisitPolicy` must restrict verify to admin; create to admin/teacher |

### Cross-Role Proxy

| ID   | Requirement |
| ---- | ----------- |
| FR-CRP1 | `HasMentorProxy` trait must be used by AttendancePolicy, LogbookPolicy, and SupervisionLogPolicy |
| FR-CRP2 | Proxy verification must tag entries with `proxy_role = 'supervisor'` in activity log |
| FR-CRP3 | Proxy must activate after configurable inactivity window (default 48h for supervision logs) |

### Compliance Monitoring

| ID   | Requirement |
| ---- | ----------- |
| FR-CM1 | If student has no logbook entry for N consecutive days (default 3), mentor must be notified |
| FR-CM2 | At N+2 days, program coordinator must also be notified |
| FR-CM3 | `journals:check-compliance` command must run this check on demand or via scheduler |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-P1 | Compliance check command must process 100 students in < 30s |
| NFR-U1 | Pending supervision logs must be prominently visible on supervisor dashboard |
| NFR-M1 | All PHP files must declare `strict_types=1` and follow PSR-12 |
| NFR-L1 | All user-facing strings must use `__()` translation helper |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |

---

## 6. API / Data Contracts

### SupervisionLog Model

```
App\Journals\SupervisionLog\Models\SupervisionLog
  Table: supervision_logs (UUID PK)
  Fillable: registration_id, supervisor_id, type, date, topic, notes, status,
            supervisor_feedback, reviewed_by, reviewed_at, is_verified, verified_by, verified_at
  Casts: date → date, status → SupervisionLogStatus, reviewed_at → datetime
  Relations: registration() BelongsTo Registration, supervisor() BelongsTo User, reviewer() BelongsTo User
  Bridge: asSupervisionLogState() → SupervisionLogState
```

### MonitoringVisit Model

```
App\Journals\MonitoringVisit\Models\MonitoringVisit
  Table: monitoring_visits (UUID PK)
  Fillable: registration_id, teacher_id, visit_date, method, location, duration_minutes,
            notes, student_condition, company_feedback, follow_up_actions,
            is_verified, verified_by, verified_at
  Casts: visit_date → date, method → VisitMethod, is_verified → boolean
  Relations: registration() BelongsTo Registration, teacher() BelongsTo User, verifier() BelongsTo User
  Bridge: asVisitState() → VisitState
```

### Enums

| Enum | Cases |
| ---- | ----- |
| `SupervisionLogStatus` | DRAFT, SUBMITTED, REVIEWED, ACKNOWLEDGED, VERIFIED, COMPLETED |
| `SupervisionType` | GUIDANCE, SUPERVISORING (value='mentoring'), MONITORING |
| `VisitMethod` | SITE_VISIT, VIRTUAL_MEETING, PHONE_CALL |

### Actions (7 total)

**SupervisionLog (5):** `CreateLogAction`, `CreateSupervisionLogAction`, `DeleteLogAction`, `ReviewLogAction`, `VerifySupervisionLogAction`
**MonitoringVisit (2):** `CreateVisitAction`, `VerifyVisitAction`

### Policies

| Policy | Key Rules |
| ------ | --------- |
| `SupervisionLogPolicy` | create: student; review: supervisor/mentorProxy; delete: admin/owner(DRAFT) |
| `MonitoringVisitPolicy` | create: admin/teacher; verify: admin; update/delete: admin/owner(when editable) |

### Routes

| Route | Component | Name | Middleware |
| ----- | --------- | ---- | ---------- |
| `GET /student/supervision-logs` | `StudentLogManager` | `student.supervision-logs` | auth, student |
| `GET /student/monitoring-visits` | `StudentVisitList` | `student.monitoring-visits` | auth, student |
| `GET /supervision/logs` | `SupervisorReviewManager` | `supervision.logs` | auth, supervisor |
| `GET /monitoring-visits/` | `VisitManager` | `monitoring-visits.index` | auth, teacher/admin |

### Database Schema

```
supervision_logs:
  id: uuid (PK), registration_id: FK→registrations (cascade), supervisor_id: FK→users (null, set null)
  type, date, topic (nullable), notes (text), status (default 'pending')
  is_verified, verified_at, verified_by: FK→users (null, set null)
  supervisor_feedback, reviewed_by: FK→users (null, set null), reviewed_at

monitoring_visits:
  id: uuid (PK), registration_id: FK→registrations (cascade), teacher_id: FK→users (null, set null)
  visit_date, method, location (varchar 512), duration_minutes (unsigned int)
  notes, student_condition, company_feedback, follow_up_actions
  is_verified, verified_by: FK→users (null, set null), verified_at
```

---

## 7. Design Decisions

### DD-1 — SupervisionType Naming Inconsistency

**Decision:** `SupervisionType::SUPERVISORING` case maps to string value `'mentoring'`.
**Rationale:** The business concept is "supervising" but the database column stores 'mentoring' for backward compatibility with existing data. The enum case name reflects the business term; the backing value reflects the stored data.
**Trade-off:** Potential confusion when reading code vs. database. Documented in code comments.

### DD-2 — Cross-Role Proxy via Trait, Not Middleware

**Decision:** Cross-Role Proxy is implemented via `HasMentorProxy` trait on Policy classes, not via HTTP middleware.
**Rationale:** Proxy activation depends on the specific record (is the supervisor inactive for THIS student's registration?), not on the user's global role. Middleware operates at request level; traits on Policies operate at authorization level, which can inspect the target model.
**Trade-off:** Each Policy must explicitly use the trait. Rejected alternative: middleware would incorrectly apply proxy globally.

---

## 8. Success Metrics

### Data Integrity

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Unverified visits after admin review | 0 | VerifyVisitAction checks is_verified |

### Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Compliance check (100 students) | < 30s | Consecutive-day gap detection |

### Architecture Compliance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| No model mutations in Livewire (C1) | 0 violations | All mutations via Actions |
| No service locator (C2) | 0 violations | Constructor injection throughout |
| Entity purity (C5) | 0 violations | All entities import no Actions |
| Strict types (D1) | 100% files | `declare(strict_types=1)` in all PHP files |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [placement.md](placement.md) | Active placement records — supervision is scoped to placements |
| [daily-activity.md](daily-activity.md) | Logbook entries and attendance records — supervisors review and comment on these |

### Build Guide
After implementing this spec, supervisors can log monitoring visits, review student logbooks, and generate supervision reports. This closes the feedback loop between students (who log activities) and supervisors (who evaluate them). The next step is to build incident reporting for workplace issues.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [incident.md](incident.md) | Incident reports reference placement and supervision context |

---

## Quick References

- `app/Journals/SupervisionLog/` — SupervisionLog submodule (Actions, Entities, Enums, Livewire, Models, Policies)
- `app/Journals/MonitoringVisit/` — MonitoringVisit submodule (Actions, Entities, Enums, Livewire, Models, Policies)
- `database/migrations/2026_01_04_000007_create_supervision_logs_table.php` — SupervisionLog schema
- `database/migrations/2026_01_04_000008_create_monitoring_visits_table.php` — MonitoringVisit schema
- `routes/web/journals.php` — Route definitions
- `docs/modules/journals.md` — Module conceptual documentation
- **Related spec:** [daily-activity.md](daily-activity.md) — logbook, attendance, absence requests
