# Chapter 16: Attendance & Logbook

> **Last updated:** 2026-06-16
> **Changes:** sync — initial metadata sync with new format

## Description
This chapter covers daily student activities: clocking in/out for attendance, recording logbook
entries, submitting absence requests, and how mentors verify these records.

---


## 16.1 Student Attendance

Attendance tracking allows students to clock in and out each day, with optional GPS location
capture. One attendance record is created per student per day.

### 16.1.1 Clocking In

1. Navigate to **Student Portal → Attendance** from the sidebar
2. Click **Clock In**
3. The system records:
   - The current date and time
   - Your IP address
   - GPS coordinates (if your browser shares location)

You cannot clock in if you don't have an active internship registration.

### 16.1.2 Clocking Out

1. From the same attendance page, click **Clock Out**
2. The system records your clock-out time

**Rules:**
- You must clock in before you can clock out
- You can only clock in once per day
- You can only clock out once per day

### 16.1.3 Attendance Status

After clocking in, the system assigns a status:

| Status | Meaning |
|--------|---------|
| Present | Clocked in on time |
| Late | Clocked in after the scheduled start time |
| Early Out | Clocked out before the scheduled end time |

If you are absent with a valid reason, submit an **Absence Request** instead (see below).

---

## 16.2 Student Logbook

The logbook is your daily journal where you record what you did, what you learned, and any
challenges you faced during your internship. You can create exactly **one entry per day**.

### 16.2.1 Creating a Logbook Entry

1. Navigate to **Student Portal → Logbook** from the sidebar
2. Click **New Entry**
3. Fill in the fields:

| Field | Description |
|-------|-------------|
| **Date** | The date of the entry (defaults to today) |
| **Activities** | What you did today — describe your tasks and activities |
| **Learning Outcomes** | What you learned or achieved |
| **Photos** | Optional photos to attach (JPEG, PNG, WebP, HEIC — max 10 MB each) |

4. Click **Save as Draft** to continue editing later, or **Submit** to send for mentor review

### 16.2.2 Editing an Entry

You can edit a draft entry at any time. Once submitted, the entry is locked and cannot be edited.
If your mentor requests revisions, the entry status changes to **Revision Required** and you can
edit it again.

### 16.2.3 Entry Status Lifecycle

```
Draft → Submitted → Verified (by mentor)
                   → Revision Required (by mentor) → back to Draft
```

| Status | Meaning | Can Edit? |
|--------|---------|-----------|
| **Draft** | Saved but not yet submitted | Yes |
| **Submitted** | Sent to mentor for review | No |
| **Verified** | Reviewed and approved by mentor | No |
| **Revision Required** | Mentor requested changes | Yes (reverts to Draft-like state) |

### 16.2.4 Attaching Photos

When creating or editing an entry, you can attach up to 10 photos (each up to 10 MB). Supported
formats: JPEG, PNG, WebP, HEIC, HEIF. Photos are stored via the Media Library.

---

## 16.3 Absence Requests

If you are unable to attend your internship due to illness, personal matters, or emergencies,
submit an absence request.

### 16.3.1 Submitting an Absence Request

1. Navigate to **Student Portal → Absence Request** from the sidebar
2. Fill in the fields:

| Field | Description |
|-------|-------------|
| **Date** | The date of the absence (today or a past date) |
| **Reason Type** | Sick, Permission, Emergency, or Other |
| **Description** | Explanation of the absence (min 10 characters) |
| **Attachment** | Optional supporting document (PDF, JPG, PNG — max 2 MB) |

3. Click **Submit**

The request status is set to **Pending** and sent to your mentor for approval.

### 16.3.2 Absence Request Status

| Status | Meaning |
|--------|---------|
| **Pending** | Waiting for mentor review |
| **Approved** | Mentor has approved the absence |
| **Rejected** | Mentor has rejected the absence with a reason |

### 16.3.3 Reason Types

| Reason | Requires Attachment? |
|--------|---------------------|
| Sick | Recommended (medical certificate) |
| Permission | No |
| Emergency | Recommended |
| Other | No |

---

## 16.4 Mentor: Managing Attendance & Logbook

Mentors (teachers and supervisors) can view and verify attendance logs and logbook entries for
their assigned students.

### 16.4.1 Verifying Attendance

1. Navigate to **Admin → Attendance** or go to `/admin/attendance`
2. Select a **date** to view attendance records
3. Mark attendance status for each student:

| Status | Description |
|--------|-------------|
| Present | Student attended on time |
| Late | Student arrived late |
| Early Out | Student left early |
| Absent | Student did not attend |
| Permission | Student had permission to be absent |
| Sick | Student was sick |

4. Click **Save** to record attendance

You can also verify existing attendance records by clicking the **Verify** button next to each
record.

### 16.4.2 Managing Pending Absence Requests

The Attendance Manager page shows a **Pending Absences** tab where you can:

- **Approve** — confirms the absence as valid
- **Reject** — denies the absence; enter a reason

### 16.4.3 Reviewing Logbook Entries

1. Navigate to **Admin → Logbook** or go to `/admin/logbook`
2. The table shows entries from students assigned to you (teachers see their mentored students;
   supervisors see the students they supervise)
3. Click on an entry to view its full content

**Actions:**

| Action | Description |
|--------|-------------|
| **Verify** | Mark an entry as verified |
| **Add Feedback** | Provide mentor feedback on the entry |
| **Request Revision** | Send the entry back to the student with revision notes |
| **Add Supervisor Note** | (Supervisor only) Add private notes without changing entry status |

### 16.4.4 Supervisor Notes

Supervisors can add private notes to any logbook entry. These notes do not affect the entry's
status or verification state — they are informational only, visible to other supervisors and
administrators.

### 16.4.5 Logbook Report

You can generate a PDF report of all verified logbook entries for a specific student:

1. Go to **Admin → Logbook**
2. Find the student and click **Report**
3. The system generates a PDF with all verified entries in chronological order, including
   supervisor notes (optional)

---

## 16.5 Compliance Monitoring

The system automatically monitors logbook compliance. If a student has no logbook entry for
**3 consecutive days**, the mentor receives a notification. At **5 consecutive days** without an
entry, the program coordinator is also notified.

Administrators can run the compliance check manually:

```bash
php artisan journals:check-compliance
```

---

## 16.6 Troubleshooting

### Cannot clock in

- Ensure you have an **active internship registration**
- If you already clocked in today, you cannot clock in again — only clock out
- If you see "Already clocked in," you have already clocked in for today

### Cannot clock out

- You must clock in first before you can clock out
- If you already clocked out, check your attendance record for the day

### Logbook entry says "already submitted"

You can only have one submitted entry per day. If you need to make changes, ask your mentor to
request a revision.

### Mentor cannot see a student's entries

The mentor must be assigned to the student's group as a teacher or supervisor. Check the student's
group membership in **Internship → Groups**.

### Absence request is stuck at "Pending"

Contact your mentor or administrator to review the request. Pending absence requests appear in the
mentor's Attendance Manager under the **Pending Absences** tab.

---

**← Previous: [Chapter 15: Internship Registration & Placement](15-internship-registration-and-placement.md)**
**Next: [Chapter 17: Monitoring Visit & Supervision Log](17-monitoring-visit-and-supervision-log.md)**
