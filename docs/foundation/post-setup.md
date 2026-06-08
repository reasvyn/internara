# Post-Setup: First Actions as Administrator

> **Last updated:** 2026-06-08

The setup wizard is complete. This guide walks through essential first steps to prepare Internara
for daily use by students, teachers, and supervisors.

---

## Phase 1: Foundation Setup

### 1. Configure System Settings

**Admin → Settings** — set your institution's identity:

- Application name and favicon
- Brand colors (primary, secondary, accent) — changes apply immediately
- Time zone and locale (`en` or `id`)
- Default language for new users

### 2. Create Academic Years

**School → Academic Years** — add current and upcoming school years.

| Field | Example |
|-------|---------|
| Name | "2025/2026" |
| Start Date | July 14, 2025 |
| End Date | June 26, 2026 |
| Active | Yes (only one year can be active at a time) |

### 3. Complete Departments

**School → Departments** — add all study programs (jurusan). The wizard created one default
department. Add the rest here (e.g., "Software Engineering", "Network Administration").

---

## Phase 2: People Management

### 4. Register Partner Companies

**Partnership → Companies** — add every company that hosts interns.

| Field | Example |
|-------|---------|
| Name | "PT Teknologi Maju" |
| Address | Company street address |
| Email | Contact email |
| Phone | Contact phone number |
| PIC | Person in charge name |
| PIC Phone | Person in charge phone |

### 5. Create Partnerships (MoU)

**Partnership** — for each company, create a partnership record with agreement type, start/end
dates, signed document upload, and status. Only companies with an active partnership can host
students.

### 6. Create User Accounts

**Admin → User Management** — create accounts for:

| Role | Description | Created By |
|------|-------------|------------|
| Teacher | School-based mentor, supervises and grades | Admin |
| Supervisor | Company-based mentor, guides daily work | Admin |
| Student | Internship participant | Admin or via Registration |

### 7. Assign Mentors

**Mentor** — toggle the `Is Mentor` flag on teachers and supervisors. Active mentors appear in
placement and supervision flows.

---

## Phase 3: Program Configuration

### 8. Create Internship Period

**Internship** — create the placement program.

| Field | Example |
|-------|---------|
| Name | "Work Placement 2025/2026" |
| Description | "Industrial practice for grade XII students" |
| Start Date | August 1, 2025 |
| End Date | December 19, 2025 |

### 9. Configure Document Requirements

**Internship → Requirements** — define documents students must submit: application letter,
acceptance letter, parental consent form, health certificate.

### 10. Open Student Registration

**Registration** — set start/end dates, select target internship, configure required documents,
publish.

---

## Phase 4: Go-Live

### 11. Review & Verify Registrations

**Registration → Review** — approve or reject applications. Each approved student becomes eligible
for placement.

### 12. Place Students at Companies

**Placement** — assign each approved student to a company with supervisor mentor, start/end dates.
One active placement per student at a time.

### 13. Verify Daily Operations

| Workflow | How to Verify |
|----------|--------------|
| Attendance | Student clocks in → attendance record created |
| Logbook | Student creates entry → mentor can view and comment |
| Assignment | Teacher creates → student submits → teacher grades |
| Absence | Student submits → mentor approves/rejects |

### 14. Configure Notifications

Notifications delivered via in-app notification center (bell icon). Optional email (SMTP) and
WebSocket (Reverb) for real-time alerts.

---

## Quick Reference Checklist

```
Phase 1 — Foundation
□ 1. System settings (name, branding, locale)
□ 2. Academic years (current + next)
□ 3. All departments added

Phase 2 — People
□ 4. Partner companies registered
□ 5. MoU/partnerships created
□ 6. Teacher, supervisor, student accounts created
□ 7. Mentors assigned

Phase 3 — Program Configuration
□ 8. Internship period created
□ 9. Document requirements configured
□ 10. Registration opened for students

Phase 4 — Go-Live
□ 11. Student registrations verified
□ 12. Students placed at companies
□ 13. Daily workflows verified (attendance, logbook, assignment, absence)
□ 14. Notifications working
```

---

## References

| Document | What It Covers |
|----------|---------------|
| [RBAC](rbac.md) | Role hierarchy, permissions, policies |
| [Academics](../modules/academics.md) | Schools, departments, academic years |

  | [Partners](../modules/partners.md) | Company and MoU management |

  | [Enrollment](../modules/enrollment.md) | Registration, placement, change requests |

  | [Program](../modules/program.md) | Internship periods, requirements |

  | [Guidance](../modules/guidance.md) | Mentor assignment, supervision logs |

  | [Journals](../modules/journals.md) | Attendance, logbook, absence |

  | [Assignment](../modules/assignment.md) | Tasks, submissions, grading |
