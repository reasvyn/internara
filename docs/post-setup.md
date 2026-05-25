# Post-Setup: First Actions as Administrator

Congratulations — the setup wizard is complete and Internara is running.
This guide walks through the essential first steps to prepare the system
for daily use by students, teachers, and supervisors.

---

## Phase 1: Foundation (Day 1)

These steps configure the core institutional data that everything else
depends on.

### 1. Configure System Settings

**Admin → Settings** — set your institution's identity:

- Application name and favicon
- Brand colors (primary, secondary, accent) — changes apply immediately
- Time zone and locale (`en` or `id`)
- Default language for new users

### 2. Create Academic Years

**School → Academic Years** — add the current and upcoming school years.

| Field | Example |
|---|---|
| Name | "2025/2026" |
| Start Date | July 14, 2025 |
| End Date | June 26, 2026 |
| Active | Yes (only one year can be active at a time) |

### 3. Complete Departments

**School → Departments** — add all study programs your school offers.

The wizard created one department. Add the rest here, each with a name
and optional description (e.g., "Software Engineering", "Network
Administration", "Accounting").

### 4. Register Partner Companies

**Partnership → Companies** — add every company that hosts interns.

| Field | Example |
|---|---|
| Name | "PT Teknologi Maju" |
| Address | Company street address |
| Email | Contact email |
| Phone | Contact phone number |
| PIC | Person in charge name |
| PIC Phone | Person in charge phone |

### 5. Create Partnerships (MoU)

**Partnership** — for each company, create a partnership record with:

- Agreement type (e.g., MoU)
- Start and end dates
- Signed document upload (optional)
- Status (`active` / `expired`)

Only companies with an active partnership can host students.

---

## Phase 2: People & Roles (Day 1–2)

### 6. Create User Accounts

**Admin → User Management** — create accounts for:

| Role | Description | Created By |
|---|---|---|
| **Teacher** | School-based mentor who supervises students and grades assignments | Admin |
| **Supervisor** | Company-based mentor who supervises daily work at the internship site | Admin |
| **Student** | Internship participant | Admin or via Registration |

Each account needs: name, email, username, and an initial password.

> See [Authentication & RBAC](blueprints/09-authentication-rbac.md) for
> the full role hierarchy and permission model.

### 7. Assign Mentors

**Mentor** — designate which teachers and supervisors act as mentors:

- **Teacher mentors** — school-side mentors who visit students at companies
- **Supervisor mentors** — company-side mentors who guide daily work

Toggle the `Is Mentor` flag on each user. Active mentors appear in
placement and supervision flows.

---

## Phase 3: Internship Setup (Day 2–3)

### 8. Create Internship Periods

**Internship** — add one or more internship periods if not already done
in the wizard:

| Field | Example |
|---|---|
| Name | "PKL 2025/2026" |
| Description | "Industrial practice for grade XII students" |
| Start Date | August 1, 2025 |
| End Date | December 19, 2025 |

### 9. Configure Document Requirements

**Internship → Requirements** — define what documents students must submit:

- Application letter
- Acceptance letter from company
- Parental consent form
- Health certificate

Attach file templates if available. Requirements can be mandatory or
optional.

### 10. Open Student Registration

**Registration** — open the registration period so students can apply:

- Set start and end dates
- Select the target internship period
- Configure which documents students must upload
- Publish → students see the registration form on their dashboard

---

## Phase 4: Placements (Day 3–5)

### 12. Review & Verify Registrations

**Registration → Review** — approve or reject student applications.
Verify uploaded documents. Each approved student becomes eligible for
placement.

### 13. Place Students at Companies

**Placement** — assign each approved student to a company:

- Select student → Select company → Select supervisor mentor
- Set start and end dates for the placement
- A student can only have one active placement at a time

### 14. Handle Placement Changes

If a student needs to change companies mid-program, use the
**Placement Change Request** flow. The request goes through approval
before the change takes effect.

---

## Phase 5: Going Live

### 15. Verify Daily Operations

Before the first day of PKL, confirm these workflows work:

| Workflow | How To Verify |
|---|---|
| Attendance | Student clocks in → attendance record created |
| Logbook | Student creates entry → mentor can view and comment |
| Assignment | Teacher creates assignment → student submits → teacher grades |
| Absence | Student submits absence request → mentor approves/rejects |

### 16. Configure Notifications

Notifications are delivered via the in-app notification center (bell icon).
If email or WebSocket (Reverb) is configured, users receive real-time
alerts and/or email delivery.

---

## Quick Reference Checklist

```
Phase 1 — Foundation
□ 1. System settings (name, branding, locale)
□ 2. Academic years (current + next)
□ 3. All departments added
□ 4. Partner companies registered
□ 5. MoU/partnerships created

Phase 2 — People
□ 6. Teacher accounts created
□ 7. Supervisor accounts created
□ 8. Mentors assigned (teachers + supervisors)

Phase 3 — Internship Setup
□ 9. Internship period created
□ 10. Document requirements configured
□ 11. Registration opened for students

Phase 4 — Placements
□ 13. Student registrations verified
□ 14. Students placed at companies
□ 15. Placement change request flow tested

Phase 5 — Live
□ 16. Attendance workflow verified
□ 17. Logbook workflow verified
□ 18. Assignment workflow verified
□ 19. Absence request workflow verified
□ 20. Notifications working
```

---

## References

| Document | What It Covers |
|---|---|
| [RBAC & Roles](blueprints/09-authentication-rbac.md) | Role hierarchy, permissions, policies |
| [Domain Index](domain/domain-index.md) | All domain documentation |
| [School](domain/school.md) | Managing schools, departments, academic years |
| [Partnership](domain/partnership.md) | Company and MoU management |
| [Registration](domain/registration.md) | Student registration and verification |
| [Placement](domain/placement.md) | Student placement and change requests |
| [Internship](domain/internship.md) | Periods, document requirements |
| [Mentor](domain/mentor.md) | Mentor assignment and supervision logs |
| [Attendance](domain/attendance.md) | Clock-in/out, absence requests |
| [Assignment](domain/assignment.md) | Assignments, submissions, grading |
| [Logbook](domain/logbook.md) | Daily activity journal |
