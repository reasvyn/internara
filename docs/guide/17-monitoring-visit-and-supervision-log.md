# Chapter 17: Monitoring Visit & Supervision Log

> **Last updated:** 2026-06-16 **Changes:** sync — initial metadata sync with new format

## Description

This chapter covers two guidance features: **Monitoring Visits** (teacher site visits to check on
students) and **Supervision Logs** (student mentoring records reviewed by industry supervisors).

---

## 17.1 Overview

The guidance system has two separate workflows depending on who fills the entry:

| Feature              | Filled By | Purpose                                                 | Visibility                                            |
| -------------------- | --------- | ------------------------------------------------------- | ----------------------------------------------------- |
| **Monitoring Visit** | Teacher   | Site visits to check student condition at the workplace | Teacher & Admin only (student sees read-only summary) |
| **Supervision Log**  | Student   | Records of mentoring sessions with industry supervisor  | Student & Supervisor                                  |

Both features share the same goal — ensuring quality mentorship — but serve different audiences and
have different privacy requirements.

---

## 17.2 Monitoring Visit (Teacher)

Teachers conduct monitoring visits to observe students at their internship placement. These visits
can be in-person site visits, virtual meetings, or phone calls. The teacher writes private notes
about the student's condition, company feedback, and any follow-up actions needed.

### 17.2.1 Recording a Visit

1. Navigate to **Supervision → Visits** from the sidebar
2. Click **New Visit**
3. Fill in the fields:

| Field                  | Description                                   | Example                                       |
| ---------------------- | --------------------------------------------- | --------------------------------------------- |
| **Student**            | Select the student you are visiting           | Only students assigned to you appear          |
| **Visit Date**         | Date of the visit                             | 16 June 2026                                  |
| **Method**             | How the visit was conducted                   | Site Visit / Virtual Meeting / Phone Call     |
| **Location**           | Where the visit took place                    | PT Teknologi Maju, Jl. Sudirman No. 10        |
| **Duration (minutes)** | How long the visit lasted                     | 60                                            |
| **Notes**              | Your private observations                     | Student adapting well to the work environment |
| **Student Condition**  | Notes on the student's condition at site      | Appears motivated, good attendance            |
| **Company Feedback**   | Feedback received from the company supervisor | Company is satisfied with performance         |
| **Follow-Up Actions**  | Any actions needed                            | Schedule follow-up in 2 weeks                 |

4. Click **Save**

### 17.2.2 Editing a Visit

You can edit a visit record as long as it has not been verified by an administrator. Once verified,
the record is locked and cannot be changed.

### 17.2.3 Visit Methods

| Method              | Description                                   | When to Use                                      |
| ------------------- | --------------------------------------------- | ------------------------------------------------ |
| **Site Visit**      | In-person visit to the company location       | Best practice — required at least once per month |
| **Virtual Meeting** | Video call with the student and/or supervisor | When distance makes site visits impractical      |
| **Phone Call**      | Phone conversation with the student           | Quick check-ins between formal visits            |

### 17.2.4 Verification

Administrators can verify visit records. Verification locks the record and confirms that the visit
was properly documented. Teachers cannot edit or delete verified visits.

### 17.2.5 Student Read-Only View

Students can see a read-only summary of visits made about them. They can view:

- Visit date
- Method (Site Visit, Virtual Meeting, or Phone Call)
- A sanitised summary of notes

They cannot see raw teacher notes, company feedback, or follow-up actions.

---

## 17.3 Supervision Log (Student)

Supervision logs allow students to document their mentoring sessions with their industry supervisor.
The student writes what material was covered, and the supervisor reviews it and provides feedback.

### 17.3.1 Creating a Supervision Log

1. Navigate to **Student Portal → Supervision Logs** from the sidebar
2. Click **New Entry**
3. Fill in the fields:

| Field     | Description                       | Example                                              |
| --------- | --------------------------------- | ---------------------------------------------------- |
| **Date**  | Date of the mentoring session     | 16 June 2026                                         |
| **Topic** | What was discussed                | Project progress review                              |
| **Notes** | What you learned from the session | Received feedback on code quality and best practices |

4. Click **Save as Draft** to continue later, or **Submit** to send to your supervisor

### 17.3.2 Editing a Log Entry

You can edit draft entries at any time. Once submitted, the entry is sent to your supervisor for
review and can no longer be edited.

### 17.3.3 Status Lifecycle

```
Draft ── Submit ──> Submitted ── Review ──> Reviewed ── Acknowledge ──> Acknowledged
                         │                          │
                         └── Request Revision ──────┘  (supervisor sends back)
```

| Status           | Meaning                                       | Who Can Act                               |
| ---------------- | --------------------------------------------- | ----------------------------------------- |
| **Draft**        | Saved but not yet submitted                   | Student can edit or submit                |
| **Submitted**    | Sent to supervisor for review                 | Supervisor can review or request revision |
| **Reviewed**     | Supervisor has reviewed and provided feedback | Student can acknowledge feedback          |
| **Acknowledged** | Student has read the feedback                 | Terminal state                            |

### 17.3.4 Submitting for Review

When you are ready for your supervisor to review your entry, click **Submit**. The entry status
changes to Submitted and becomes read-only. Your supervisor will receive a notification.

### 17.3.5 Acknowledging Feedback

After your supervisor reviews the entry, the status changes to **Reviewed** and you can see their
feedback. Click **Acknowledge** to confirm you have read the feedback. This changes the status to
**Acknowledged** and completes the cycle.

---

## 17.4 Supervisor: Reviewing Logs

Industry supervisors review supervision logs submitted by their assigned students.

1. Navigate to **Supervision → Logs** from the sidebar
2. The table shows submitted logs from students assigned to you
3. Click on a log entry to view the student's notes

**Actions:**

| Action                        | Description                                              |
| ----------------------------- | -------------------------------------------------------- |
| **Review & Provide Feedback** | Mark the log as reviewed and write your feedback         |
| **Request Revision**          | Send the log back to the student for revision with notes |

When reviewing, write constructive feedback about the student's mentoring progress. The student will
see your feedback and acknowledge it.

---

## 17.5 Troubleshooting

### Teacher cannot see a student in the visit form

The student must be assigned to you as a mentee. Check the student's group assignment in
**Internship → Groups**. Only students with you as their assigned teacher will appear.

### Student cannot submit a supervision log

- Ensure you have an **active internship registration**
- Draft entries can be edited; submitted entries cannot
- Only one entry per mentoring session

### Supervisor cannot see a student's logs

You must be assigned as the industry supervisor for that student. Check your assigned students in
the supervision dashboard. If a student is missing, contact the administrator.

### Visit record cannot be edited

Visit records that have been **verified** by an administrator are locked. Contact the administrator
if a correction is needed.

### Log entry is stuck at "Submitted"

The supervisor may not have reviewed it yet. Contact your industry supervisor to check their review
queue.

---

**← Previous: [Chapter 16: Attendance & Logbook](16-attendance-and-logbook.md)** **Next:
[Chapter 18: Assignment & Assessment](18-assignment-and-assessment.md)**
