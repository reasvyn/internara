# Student Registration

**Event:** Enrolling students into internships through three available paths.

**Phase:** 3 — Registration & Placement

**Previous Event:** [Company & Placement Management](company-placement.md)

**Next Events:** [Logbook Workflow](logbook-workflow.md), [Attendance Tracking](attendance-tracking.md)

---

## Overview

There are three distinct paths for a student to be registered in an internship. The choice depends on whether the student already has an account and whether the registration is initiated by the student or the admin.

```
                         ┌──────────────────────────┐
                         │  How does the student    │
                         │  join the internship?    │
                         └──────────────────────────┘
                                  │
                    ┌─────────────┼──────────────┐
                    │             │              │
                    ▼             ▼              ▼
              New Student    Existing        Admin
              (no account)   Student      (direct)
                    │             │              │
                    ▼             ▼              ▼
            Path A: Apply   Path B: Self-   Path C: Direct
            (public form)   Registration   Placement
```

---

## Pre-conditions (All Paths)

- At least one internship is **Published** or **Active**
- The internship's registration window is open (or admin is bypassing)
- Placement slots with available capacity exist (for Paths B and C)
- For Path B: student is logged in with role STUDENT
- For Path C: admin is logged in with SUPER_ADMIN or ADMIN role

---

## Path A: Account Application (New Students)

**Use case:** A prospective student who does not yet have a system account wants to join an internship.

### Flow

```
Public Form → Submit Application → Admin Reviews → Approved/Rejected
```

### Step 1: Submit Application

The prospective student fills out a public form at `/apply`:

| Field | Validation | Description |
|---|---|---|
| **Full Name** | Required | Student's legal name |
| **Email** | Required, valid email, unique | Login email (will be used for account) |
| **Phone** | Required | Contact number |
| **School** | Required | Institution name |
| **Department** | Required | Academic department |
| **Internship Period** | Required | Desired internship |
| **Company Preference** | Optional | Desired company or placement |
| **Address** | Required | Home address |
| **Emergency Contact Name** | Required | Emergency contact |
| **Emergency Contact Phone** | Required | Emergency phone |

The `ApplyAccountAction` creates an `AccountApplication` with status `pending`. Duplicate applications (same email with pending/approved status) are prevented.

### Step 2: Admin Reviews

Admin navigates to **Admin → Applications** to review pending applications:

- Views applicant details and preferences
- Checks for any existing accounts or duplicate applications

### Step 3a: Approve

Admin clicks **Approve**. The `VerifyAccountAction::approve()` executes a transaction:

1. Sets application status to **approved**
2. Creates a **User** account:
   - Role: student
   - Status: PROVISIONED (user needs to log in and complete setup)
   - `setup_required = true`
   - Random password generated
3. Creates a **Profile** with applicant's personal data
4. Creates a **Mentee** record linked to the user
5. Creates an **active Registration**:
   - Links to the selected internship
   - Links to the selected placement (if specified)
   - Sets start/end dates from placement
   - Status: active (no pending phase — account application bypasses registration verification)
6. Attaches **mentors** to the registration:
   - A school teacher (if assigned to the placement)
   - An industry supervisor (from the company)
7. Increments placement `filled_quota`
8. Sends welcome notification with login instructions

### Step 3b: Reject

Admin clicks **Reject**:

1. Sets application status to **rejected**
2. Records rejection reason
3. Records admin who processed it
4. Applicant is notified

### State Transitions

```
Application: Pending → Approved | Rejected
User:         (new)  → PROVISIONED
Registration: (new)  → Active
```

---

## Path B: Self-Registration (Existing Students)

**Use case:** An existing student with a system account wants to register for an available internship.

### Flow

```
Student Login → Browse Internships → Register → Admin Verifies → Active
```

### Step 1: Browse and Register

Student logs in and browses available internships. Clicking **Register** on an internship calls `RegisterInternshipAction`:

1. Validates the internship is accepting registrations
2. Checks no existing active/pending registration
3. Creates a **Mentee** record (if not already one)
4. Creates a **Registration** with status **pending**
5. Sends `RegistrationNotification` to the student

### Step 2: Admin Verifies

Admin navigates to **Admin → Internships → Pending Registrations** to review:

1. Views the registration details and student information
2. Selects an available **Placement** with capacity
3. Optionally assigns a school teacher and industry supervisor
4. Clicks **Verify**

The `VerifyRegistrationAction` executes:

1. Validates the registration is in **pending** status
2. Validates the selected placement has available capacity
3. Assigns the placement to the registration
4. Sets registration start/end dates from the placement
5. Transitions registration status to **active**
6. Increments placement `filled_quota`
7. Attaches mentors to the registration (via `registration_mentor` pivot)
8. Notifies the student of successful placement

### State Transitions

```
Registration: Pending → Active
Placement: filled_quota +1
```

---

## Path C: Direct Placement (Admin-Initiated)

**Use case:** An admin directly assigns a student to an internship without going through the application or self-registration flow.

### Flow

```
Admin → Select Student → Select Internship → Select Placement → Confirm
```

Navigate to **Admin → Internships → Placements → Direct Placement**.

| Field | Validation | Description |
|---|---|---|
| **Student** | Required, exists | Select from existing users |
| **Internship** | Required, exists | Target internship |
| **Placement** | Required, has available slots | Company placement |
| **School Teacher** | Optional | Assigned academic supervisor |
| **Industry Supervisor** | Optional | Assigned industry mentor |

The `DirectPlacementAction` executes:

1. Validates the placement has available capacity
2. Creates a **Mentee** record (if not already one)
3. Creates an **active Registration** (skips pending state entirely)
4. Increments placement `filled_quota`
5. Attaches mentors to the registration
6. Logs audit

### State Transitions

```
Registration: (new) → Active (skips pending)
Placement: filled_quota +1
```

---

## Registration States

Registration follows the state machine defined in the [System Lifecycle](system-lifecycle.md#6-phase-3-registration-engine). Paths A and C create registrations directly as ACTIVE; Path B uses PENDING → ACTIVE.

### `RegistrationState` Entity

The `RegistrationState` entity provides business rules:

| Method | Purpose |
|---|---|
| `isActive()` | Status is active |
| `isPending()` | Status is pending |
| `isCurrentlyOngoing()` | Today falls between start and end dates |
| `canBeApproved()` | Must be pending AND have a placement assigned |
| `daysRemaining()` | Days until registration end date |
| `totalDuration()` | Total days of the registration period |

---

## Document Verification

During registration (Path B), students may need to upload documents:

1. Admin defines document requirements on the internship (see [Internship Creation](internship-creation.md))
2. Student uploads documents through the registration wizard
3. Each document gets a status: `pending`, `verified`, `rejected`
4. Admin verifies documents through the registration verification screen

Required documents (mandatory ones) must be verified before the registration can be activated.

---

## Key Guards

| Guard | Enforcement |
|---|---|
| **No duplicate active registration** | Student cannot have >1 active/pending registration |
| **Placement capacity** | Cannot place student in a full placement |
| **Registration must be pending** | `VerifyRegistrationAction` and `VerifyAccountAction` enforce this |
| **Registration window** | Internship must be accepting registrations (Path B only) |
| **Duplicate application prevention** | Same email cannot have multiple pending/approved applications |
| **Mentor assignment** | School teacher + industry supervisor attached on activation |

## Seamless Connection

Once registration is active, the student can begin operational activities:

- **[Logbook Workflow](logbook-workflow.md)** — record daily activities
- **[Attendance Tracking](attendance-tracking.md)** — record presence
- **[Assignment Workflow](assignment-workflow.md)** — complete tasks
- **[Supervision Process](supervision-process.md)** — receive mentoring
