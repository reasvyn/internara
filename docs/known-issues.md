# Known Issues and Gotchas
> Last updated: 2026-05-31
> Changes: docs: replace resolved infrastructure issues with design audit findings


## Documentation — Missing Design Principles

7 domain docs have no Design Principles section. These domains define operational workflows that need explicit design guidance.

| Domain | Why It Needs Principles |
|--------|------------------------|
| **Assessment** | Scoring methodology, rubric weighting rules, finalization policy, presentation exam ethics |
| **Assignment** | Submission deadline policy, grading rubric alignment, revision cycle rules, mandatory vs optional assignments |
| **Document** | Template versioning policy, renderer selection rules, data injection contract, file format decisions |
| **Evaluation** | Anonymity guarantees, score band calibration, multi-perspective aggregation, feedback ownership |
| **Incident** | Severity classification rules, escalation thresholds, investigation timeline, resolution outcome policy |
| **Mentee** | Dashboard data freshness, progress calculation formula, quick-action routing rules, mentor visibility policy |
| **Schedule** | Recurrence engine rules, conflict detection thresholds, reminder timing defaults, event immutability window |

---

## Documentation — Missing Domain Features

### Site Visit Scheduling & Logging 🔴

Teachers must conduct site visits to placement locations. Currently no domain documents site visit scheduling, travel planning, or visit result logging. Mentor domain has supervision logs but they are private notes, not formal visit records.

**Suggested domain:** Mentor or a dedicated SiteVisit sub-concern.
**Priority:** High — regulatory requirement for Indonesian vocational education.

### Daily Photo Documentation 🔴

Many vocational schools require daily photo evidence of student activities — not just text journals. Logbook domain should support photo capture and attachment as first-class feature, not just generic file upload.

**Suggested domain:** Logbook.
**Priority:** High — common regulatory requirement.

### Geo-fencing for Attendance Verification 🟠

Current GPS coordinate capture provides location but not verification. Geo-fencing would define allowed radius around placement locations and reject clock-ins outside the zone.

**Suggested domain:** Attendance.
**Priority:** Medium — strengthens regulatory compliance.

### Placement Agreement Generation 🟠

Schools need auto-generated administrative letters from placement data: surat tugas, MoU summaries, placement confirmation letters. Document domain handles rendering but no domain defines the letter templates or triggers.

**Suggested domain:** Document (templates) + Placement (data source).
**Priority:** Medium — reduces administrative burden.

### 360-Degree Evaluation 🟡

Current evaluation is student→mentor and student→company only. An ideal system includes self-evaluation, mentor→student assessment, and peer feedback.

**Suggested domain:** Evaluation.
**Priority:** Low — future enhancement.

### Offline Mode Design 🟡

Product definition states offline-capable but no domain doc designs how Attendance and Logbook work without connectivity. Should document offline queue, conflict resolution, and sync strategy.

**Suggested domain:** Attendance, Logbook.
**Priority:** Low — important for schools with unreliable internet.

### Bulk Communication to Segmented Groups 🟡

Announcements target by role only. Admins need to message specific segments: "all students at Company X", "all mentors for Program Y".

**Suggested domain:** Admin.
**Priority:** Low — future enhancement.

### Digital Signature / E-Sign 🟡

Mentor verification of logbooks and attendance currently relies on click-to-verify. Electronic signature via canvas or uploaded signature image would strengthen legal validity.

**Suggested domain:** Logbook, Attendance.
**Priority:** Low — future enhancement.

---

## Documentation — Domain Boundary Gaps

### Mentor: Mixed Responsibilities 🟠

Mentor domain currently mixes two distinct responsibilities under one domain: supervision logs (private mentor notes) and grading (assignment assessment). These serve different workflows and have different authorization rules. Supervision is a mentor-student private record; grading is an academic evaluation visible to the student.

### Evaluation: Limited Perspective Coverage 🟡

Evaluation collects only student→mentor and student→company feedback. Missing perspectives documented in product definition: mentor→student performance assessment, self-evaluation, and program quality evaluation by the school.

---

## Documentation — UX Feature Gaps

| UX Feature | Domain | Priority |
|------------|--------|----------|
| Mobile-responsive clock-in button (large touch target) | Attendance | 🟠 |
| Offline queue with background sync | Attendance, Logbook | 🟡 |
| Photo capture via device camera (not just upload) | Logbook | 🟠 |
| Canvas-based digital signature pad | Logbook, Attendance | 🟡 |
| Placement location map view | Placement, Mentor | 🟡 |
| Progress ring/badge widget on dashboards | Mentee, User | 🟡 |

---

## Infrastructure

### K5. SQLite for Production — No Concurrent Write Support 🔴

SQLite uses file-level locking. Under concurrent load, "database is locked" errors occur on simultaneous operations. Use MySQL 8+ or PostgreSQL 15+ in production. Status: documented guidance — engine choice.

### H6. Duplicate Livewire: ThemeSwitcher + LangSwitcher ×2 ⏳

ThemeSwitcher and LangSwitcher are mounted in both sidebar and navbar. Currently resolved by CSS media queries (desktop shows navbar, mobile shows sidebar) but they are still two component instances per page. Status: monitored, not critical.

---

## Backlog

### Feature Test Coverage

| Domain | Actions | Feature Tests | Gap |
|--------|---------|---------------|-----|
| Assessment | 17 | 0 | 🔴 |
| Internship | 21 | 7 | 🔴 |
| Auth | 12 | 0 | 🔴 |
| Attendance | 8 | 0 | 🔴 |
| Mentor | 8 | 0 | 🔴 |
| Assignment | 7 | 0 | 🔴 |
| School | 9 | 0 | 🔴 |
| Document | 4 | 0 | 🔴 |
| Logbook | 4 | 0 | 🔴 |
| Certificate | 4 | 0 | 🔴 |
| Incident | 3 | 0 | 🔴 |
| Mentee | 3 | 0 | 🔴 |
| Schedule | 3 | 0 | 🔴 |
| Registration | 6 | 2 | 🟡 |
| Evaluation | 3 | 1 | 🟡 |
| Admin | 14 | 9 | 🟢 |
| Guidance | 2 | 2 | 🟢 ✅ |
| Partnership | 8 | 8 | 🟢 ✅ |
| Placement | 7 | 7 | 🟢 ✅ |
| Setup | 9 | 9 | 🟢 ✅ |
| Settings | 6 | 6 | 🟢 ✅ |
| User | 8 | 5 | 🟢 |

### GD8. Acknowledgement Not Used as Gate ⏳

Handbook acknowledgement is purely informational — no action is blocked. Registration, attendance clock-in, and logbook submission work without acknowledged handbooks.

### Livewire Form Object Migration ⏳

~45 Livewire components still manage form state via flat public properties. Migration completed for: Setup, Auth, Profile, Settings, Internship, Guidance, Registration, Placement.

### Cross-Domain Event Flow Undocumented ⏳

Which events fire and which listeners react is not documented. Needed for understanding side effects when modifying Actions.

### Real-Time Features (Future) ⏳

Laravel Echo and Reverb installed but no real-time channels active. Candidates: notification delivery, dashboard updates, attendance confirmations.

### Translation Gaps

`lang/id/internship.php` missing 13 keys compared to `lang/en/internship.php`. `lang/en/placement.php` and `lang/id/placement.php` have keys in different orders.

### BaseAction Cannot Enforce execute() Signature ⏳

BaseAction has no abstract execute() method. Each Action defines its own signature. No way to enforce a consistent calling convention at the abstract level.
