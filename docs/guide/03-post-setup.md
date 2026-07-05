# Chapter 3: Post-Setup — First Actions as Administrator

> **Last updated:** 2026-06-14 **Changes:** sync — initial metadata sync with new format

## Description

The setup wizard is complete. Now you'll configure Internara for daily use — add your school's
structure, create user accounts, set up internship programs, and go live.

This chapter is organised into four phases. Follow them in order.

---

## Phase 1: Foundation Setup

### Step 1 — Configure System Settings

Go to **Admin → Settings** and set up your institution's identity:

- Application name and favicon (the icon shown in browser tabs)
- Brand colours — changes apply immediately across the entire interface
- Time zone and language (`English` or `Bahasa Indonesia`)
- Default language for new users

### Step 1b — Configure Automatic Backups

Go to **Admin → Backups** to set up automated backups:

- **Enable auto-backup** — turn on scheduled backups
- **Frequency** — daily, weekly, or monthly
- **Retention** — how many days to keep backups (default: 30)
- **Include database** — include a dump of all data
- **Include storage** — include uploaded files and media

Backups are stored in `storage/app/backup/` and are only accessible to super administrators. You can
also run manual backups at any time from this page.

### Step 2 — Create Academic Years

Go to **School → Academic Years** and add the current and upcoming school years.

| Field      | Example                                         |
| ---------- | ----------------------------------------------- |
| Name       | `2025/2026`                                     |
| Start Date | 14 July 2025                                    |
| End Date   | 26 June 2026                                    |
| Active     | **Yes** (only one year can be active at a time) |

### Step 3 — Complete Departments

Go to **School → Departments** and add all study programs (jurusan). The wizard created one default
department — add the rest here:

- Software Engineering (Rekayasa Perangkat Lunak)
- Network Administration (Teknik Komputer dan Jaringan)
- Accounting (Akuntansi)
- ...any other programs your school offers

---

## Phase 2: People Management

### Step 4 — Register Partner Companies

Go to **Partnership → Companies** and add every company that hosts interns.

| Field     | Description                   |
| --------- | ----------------------------- |
| Name      | Official company name         |
| Address   | Company street address        |
| Email     | Contact email address         |
| Phone     | Contact phone number          |
| PIC       | Person in Charge name         |
| PIC Phone | Person in Charge phone number |

### Step 5 — Create Partnerships (MoU)

For each company, create a partnership record under **Partnership**:

- Agreement type and number
- Start and end dates
- Upload the signed MoU document (PDF)
- Set status to **Active**

> Only companies with an active partnership can host students.

### Step 6 — Create User Accounts

Go to **Admin → User Management** and create accounts for:

| Role           | Who                                                   |
| -------------- | ----------------------------------------------------- |
| **Teacher**    | School-based mentors who supervise and grade students |
| **Supervisor** | Company-based mentors who guide daily work            |
| **Student**    | Internship participants                               |

Students can also register themselves if you enable registration (see Phase 3).

### Step 7 — Assign Mentors

In each teacher's or supervisor's profile, toggle the **Is Mentor** flag. Active mentors appear in
placement and supervision flows.

---

## Phase 3: Program Configuration

### Step 8 — Create an Internship Period

Go to **Internship** and create the placement program:

| Field       | Example                                    |
| ----------- | ------------------------------------------ |
| Name        | `Work Placement 2025/2026`                 |
| Description | Industrial practice for grade XII students |
| Start Date  | 1 August 2025                              |
| End Date    | 19 December 2025                           |

### Step 9 — Configure Document Requirements

Go to **Internship → Requirements** and define what documents students must submit:

- Application letter
- Acceptance letter from company
- Parental consent form
- Health certificate
- ...any other documents your school requires

### Step 10 — Open Student Registration

Go to **Registration** and:

1. Set the registration start and end dates
2. Select the target internship period
3. Select required documents
4. Publish the registration

Students will now be able to register through the student portal.

---

## Phase 4: Go-Live

### Step 11 — Review & Approve Registrations

Go to **Registration → Review** to approve or reject student applications. Each approved student
becomes eligible for placement.

### Step 12 — Place Students at Companies

Go to **Placement** and assign each approved student to a company with:

- A supervisor mentor
- Start and end dates
- Placement slot at the company

> A student can only have one active placement at a time.

### Step 13 — Verify Daily Operations

Test that everything works by running through these workflows:

| Workflow            | How It Works                                                 |
| ------------------- | ------------------------------------------------------------ |
| **Attendance**      | Student clocks in → attendance record is created             |
| **Logbook**         | Student writes a journal entry → mentor can view and comment |
| **Assignment**      | Teacher creates task → student submits → teacher grades      |
| **Absence Request** | Student submits request → mentor approves or rejects         |

### Step 14 — Configure Notifications

Notifications appear in the in-app notification centre (bell icon in the top bar). For email
notifications, configure SMTP settings under **Admin → Settings → Mail**.

For real-time alerts (optional), set up Laravel Reverb — see the infrastructure documentation.

---

## Quick Reference Checklist

```
Phase 1 — Foundation
□ 1. System settings configured (name, branding, locale)
□ 2. Auto-backup configured (frequency, retention, scope)
□ 3. Academic years created (current + next)
□ 4. All departments added

Phase 2 — People
□ 4. Partner companies registered
□ 5. MoU/partnerships created
□ 6. Teacher, supervisor, and student accounts created
□ 7. Mentors assigned

Phase 3 — Program Configuration
□ 8. Internship period created
□ 9. Document requirements configured
□ 10. Student registration opened

Phase 4 — Go-Live
□ 11. Student registrations reviewed and approved
□ 12. Students placed at companies
□ 13. Daily workflows verified (attendance, logbook, assignment, absence)
□ 14. Notifications working
```

---

## Where to Go From Here

Your school is now live on Internara. Here are some resources for ongoing use:

| Topic                            | Where to Find It                              |
| -------------------------------- | --------------------------------------------- |
| User roles & permissions         | [RBAC Guide](../foundation/rbac.md)           |
| Managing academic years          | [Academics Module](../modules/academics.md)   |
| Managing companies               | [Partners Module](../modules/partners.md)     |
| Student registration & placement | [Enrollment Module](../modules/enrollment.md) |
| Internship programs              | [Program Module](../modules/program.md)       |
| Mentoring & supervision          | [Guidance Module](../modules/guidance.md)     |
| Daily journals & attendance      | [Journals Module](../modules/journals.md)     |
| Tasks & assignments              | [Assignment Module](../modules/assignment.md) |

---

---

**← Previous:** [Chapter 2: Setup Wizard](02-setup-wizard.md) **Next →**
[Chapter 7: Login & Dashboard](07-login-and-dashboard.md)
