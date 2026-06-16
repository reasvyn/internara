# Chapter 15: Internship Registration & Placement

> **Last updated:** 2026-06-16

This chapter covers how students apply and register for internship programs, how administrators
manage placements (company slot assignments), and how placement changes are handled.

---

## 15.1 Student Registration Overview

There are two paths for a student to get registered in an internship program:

| Path | Who Starts | Process |
|------|------------|---------|
| **Guest Application** | Prospective student (no account yet) | Apply via public form → Admin approves → Account created + enrolled |
| **Direct Placement** | Admin | Select student → Assign to slot → Enrolled immediately |

---

## 15.2 Guest Application (Self-Registration)

Prospective students can apply for an internship program without having an account. This is useful
for new students who haven't been provisioned in the system yet.

### 15.2.1 Submitting an Application

1. Go to the Internara home page (`/`)
2. If registration is open, click **Register Now**
3. Fill in the application form:

| Field | Description | Required |
|-------|-------------|----------|
| **Full Name** | Your complete name | Yes |
| **Email** | Your email address (must be unique) | Yes |
| **Program** | Select the internship program | Yes |
| **Academic Year** | Your current academic year | Yes |
| **Placement** | Choose a company slot, or propose your own | Yes |
| **Proposed Company** | Company name (if not choosing an existing slot) | If proposing |
| **Proposed Company Address** | Company address (if proposing) | If proposing |

4. Click **Submit**

After submission, your application status is **Pending**. The application is sent to an
administrator for review. You cannot submit a duplicate application with the same email.

### 15.2.2 Admin: Reviewing Applications

Navigate to **Internship → Pending Registrations** or go directly to
`/admin/internships/registrations/pending`.

The page shows all applications awaiting review, with details about each applicant.

#### Approving an Application

1. Click **Process** on the pending application
2. Verify or update the placement assignment:

| Field | Description |
|-------|-------------|
| **Placement** | Select the company slot (only slots with available quota are shown) |
| **Assigned Mentors** | Optionally assign a teacher or supervisor to this student |

3. Click **Confirm** — the system atomically:

   - Creates a User account (with activation token)
   - Assigns the `student` role
   - Creates a profile with the applicant's details
   - Creates a Registration record linked to the selected placement
   - Sets the registration to Active
   - Increments the placement's filled quota

The student receives an email with activation instructions. See
[Chapter 7: Login & Dashboard](07-login-and-dashboard.md#74-account-activation-first-time-users)
for the activation flow.

#### Rejecting an Application

1. Click **Reject** on the pending application
2. Enter a reason for rejection
3. Confirm — the application status changes to Rejected and the applicant is notified

### 15.2.3 Registration Period

The home page shows different messages depending on the registration period configured by the
administrator:

| Status | What Students See |
|--------|-------------------|
| **Open** | "Register Now" button with the active date range |
| **Upcoming** | Notification that registration opens on a future date |
| **Closed** | Notification that registration is closed |
| **Not Configured** | Message that registration is unavailable |

Administrators configure the registration period in **System Settings**.

---

## 15.3 Direct Placement (Admin)

Administrators can bypass the application process and directly place students into a program.

1. Go to **Internship → Direct Placement** or `/admin/internships/placements/direct`
2. Select the student from the list (only students without active registrations are shown)
3. Fill in the placement details:

| Field | Description |
|-------|-------------|
| **Student** | The student to place |
| **Placement** | Select the company slot (only slots with available quota) |
| **Academic Year** | Current academic year |
| **Assigned Mentors** | Optionally assign a teacher or supervisor |

4. Click **Assign** — the student is immediately enrolled and the slot quota is updated

---

## 15.4 Student Registration Wizard (Logged-In Students)

Students who already have an account can register for a program through the Registration Wizard.

1. Navigate to **Student Portal → Register Internship** from the sidebar
2. Step 1: Select the internship program
3. Step 2: Review and confirm
4. Submit — your registration status is set to **Pending** until an administrator assigns a
   placement and verifies the registration

---

## 15.5 Placement Management

Placements represent company slots that students can be assigned to. Each placement has a quota
that limits how many students can be placed there.

Navigate to **Internship → Placements** or go directly to `/admin/internships/placements`.

### 15.5.1 Adding a Placement

1. Click **Add Placement**
2. Fill in the fields:

| Field | Description | Example |
|-------|-------------|---------|
| **Company** | The partner company | PT Teknologi Maju |
| **Program** | The internship program | PKL 2025/2026 |
| **Name** | Placement name / location | Development Team |
| **Address** | Placement address (if different from company) | |
| **Quota** | Maximum number of students | 5 |
| **Description** | Optional notes | |

3. Click **Save**

### 15.5.2 Editing a Placement

1. Find the placement in the table
2. Click **Edit**
3. Update the fields — reducing quota below the current filled count may be blocked
4. Click **Save**

### 15.5.3 Deleting a Placement

A placement can be deleted only if it has **no active registrations**. If students are currently
placed here, you must reassign them first.

### 15.5.4 Quota Enforcement

The system enforces placement quotas at the database level using pessimistic locking. This prevents
two concurrent requests from overselling a slot:

- Each placement has a **quota** (maximum) and **filled_quota** (current count)
- When a student is placed, `filled_quota` increments atomically
- When a placement change is approved, the old slot decrements and the new slot increments
- If a slot is full, it is hidden from placement selection dropdowns

---

## 15.6 Placement Change Requests

Students can request to change their company placement during the program if needed.

### 15.6.1 Student: Requesting a Change

1. Navigate to **Student Portal → Request Placement Change**
2. Select a new placement from the available options (only slots with available quota are shown)
3. Enter the reason for the change
4. Click **Submit**

A pending request is created. You cannot submit multiple pending requests — the existing one must
be processed first.

### 15.6.2 Admin: Reviewing Change Requests

Navigate to **Internship → Placement Changes** or go directly to
`/admin/internships/placements/changes`.

#### Approving a Change

1. Click **Approve** on the pending request
2. The system atomically:
   - Decrements the old placement's filled quota
   - Increments the new placement's filled quota
   - Updates the student's registration with the new placement
   - Marks the request as Approved

#### Rejecting a Change

1. Click **Reject** on the pending request
2. Enter a reason for rejection
3. Confirm — the request status changes to Rejected

---

## 15.7 Registration Documents

Students may be required to upload documents as part of their registration (e.g., signed MoU,
parent consent letter). These are defined by the program's required document checklist.

1. Navigate to **Student Portal → My Documents**
2. For each required document, upload the corresponding file (PDF, JPG, PNG — max 5 MB)
3. After upload, the document status is set to **Pending** for admin verification
4. Administrators can verify or reject uploaded documents

---

## 15.8 Troubleshooting

### Registration period is not showing on the home page

The registration period must be configured in **System Settings**. Ask your administrator to set
the registration start and end dates.

### Cannot register — "already registered"

Each student can have only one active or pending registration at a time. Complete or cancel the
existing registration before starting a new one.

### Placement shows as full

The slot has reached its maximum quota. Select a different placement or ask an administrator to
increase the quota.

### Cannot delete a placement

The placement has active student registrations. Reassign or complete those registrations first.

### Placement change request is stuck at "Pending"

The request may be waiting for admin review. Contact your administrator to process the request.

### Document upload fails

- Check that the file is under 5 MB
- Check that the file type is PDF, JPG, or PNG
- Ensure you have an active registration

---

**← Previous: [Chapter 14: Internship Management & Handbook](14-internship-management-and-handbook.md)**
**Next: [Chapter 16: Attendance & Logbook](16-attendance-and-logbook.md)**
