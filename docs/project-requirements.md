# Project Requirements — PKL Management System (SMA/SMK)

> Last updated: 2026-06-05 Changes: Expanded Indonesian SMA/SMK PKL requirements to include deep
> functional state machines, technical offline sync protocols, infrastructure size metrics, and
> security/compliance policies

This document specifies the functional, non-functional, and localized compliance requirements for
the **Internara** fieldwork management system, tailored to Indonesian vocational school (SMK/SMA)
regulations while upholding global enterprise engineering standards.

---

## 1. Context & Scale Analysis

Vocational schools (SMK) in Indonesia require mandatory industry work placement (PKL - _Praktik
Kerja Lapangan_) for a duration of 3 to 6 months. A typical medium-to-large SMK has a cohort of
**500 to 1,000 active students** placed simultaneously across **150 to 300 partner companies (DUDI -
_Dunia Usaha & Dunia Industri_)** during a single placement period.

### Peak Load Profiles

The system must be optimized to handle the following high-concurrency windows:

- **Morning Clock-In (07:00 – 08:30 WIB)**: High concurrent writes as 1,000 students log
  location-tagged attendance.
- **Afternoon Clock-Out (16:00 – 17:30 WIB)**: High concurrent write operations for check-out and
  daily journal submissions.
- **End-of-Period Grading (Last 2 Weeks)**: High read/write activity by teachers and supervisors
  compiling evaluations, final grades, and certificate issuance.

---

## 2. Indonesian Localization Requirements

To comply with the Indonesian Ministry of Education (Kemendikbudristek) regulations (specifically
_Kurikulum Merdeka_ and _K-13_ PKL guidelines), the system must support:

### 2.1 Regulatory Workflows & Personas

- **Dual-Mentor Supervision**: Explicit division of duties between the School Teacher and Industry
  Supervisor.
- **NPSN & NISN Integration**: Data models must store the School's National NPSN and the student's
  unique NISN.
- **Official Document Templates**: Automated rendering of standard compliance paperwork:
    - Placement Introduction Letter
    - Parental Consent Form
    - Attendance & Activity Logs
    - Completion Certificate with competency score sheets

### 2.2 Local Technical Constraints

- **Low-Bandwidth Mobile Optimization**: Students often access the app on budget mobile devices with
  unstable mobile data. UI/UX must be lightweight, utilizing minimal assets and gzip compression.
- **Offline-Capability**: System fallback mechanics to record attendance timestamps locally in the
  browser storage if mobile network connection drops during clock-in, syncing when connectivity
  resumes.
- **Geofencing & Fake GPS Detection**: Geolocation validation utilizing browser APIs to check if
  coordinate locations correspond to the designated partner company address. The system must flag or
  block spoofed coordinates (Fake GPS apps).

---

## 3. Functional Requirements

### 3.1 Program Setup & Administration

- **Academic Year & Phases**: Administrative configuration of academic years, departments (jurusan),
  and multi-stage placement phases.
- **Partnership Management**: Host company registry (industry sector, addresses, phone numbers,
  contact persons, and slot quotas).

### 3.2 Placement & Slot Allocations

- **Quota-based Placement**: Auto-allocation or manual assignation of students to company slots.
- **Placement Requests**: Student-initiated request workflows for change of placement due to
  structural conflicts, requiring teacher approval.

### 3.3 Daily Operations (Attendance & Journals)

- **Check-In/Out Verification**: Location-validated, timestamped records. Supports photo attachment
  verification.
- **Reflective Logbook (Jurnal Kegiatan)**: Daily narrative entry explaining tasks performed and
  competencies practiced. Must go through a dual-approval queue (Supervisor reads first, Teacher
  finalizes).

### 3.4 Assessment & Evaluation

- **Competency Rubric Matrix**: Mapping evaluation forms to specific educational competency
  standards (_Elemen Kompetensi_).
- **Multi-Weighted Grades**: Automatic calculation of composite scores:
    ```
    Grade = (Supervisor Score × 40%) + (Teacher Score × 20%) + (Exam Score × 40%)
    ```
- **Presentation Examination**: Student exam/presentation scheduling is managed offline by each
  school; only the final exam score is recorded in the system for final grade card compilation.

### 3.5 Certification & Issuance

- **Certificate Generator**: Dynamically compiled PDF certificates containing grades and signed
  seals.
- **QR Cryptographic Verification**: Public URL verification via a secure QR code on printed
  certificates, preventing forgery.

---

## 4. Detailed Functional Workflows (State Transitions)

The core lifecycle operations in Internara are governed by strict state machines. Developers must
ensure that all mutations validate these state boundaries to prevent data integrity anomalies.

### 4.1 Placement Lifecycle State Machine

Placements manage the allocation of a student to a specific corporate partner slot during a phase.

```
       +--------------+
       |   PENDING    | (Student assigned, awaiting corporate approval)
       +--------------+
              |
              | Corporate Approves
              v
       +--------------+
       |   APPROVED   | (Confirmed, awaiting starting calendar date)
       +--------------+
              |
              | First Clock-In recorded
              v
       +--------------+
       |    ACTIVE    | (Currently attending fieldwork on-site)
       +--------------+
         /          \
        /            \  End date reached (Normal Completion)
       /              \
      v                v
+------------+   +------------+
| TERMINATED |   | COMPLETED  | (Awaiting final reports and grading)
+------------+   +------------+
```

- **Transition Constraints**:
    - A placement can only transition to `ACTIVE` from `APPROVED`.
    - `TERMINATED` represents abnormal terminations (e.g., student misconduct, health issues). This
      state requires an associated `Incident` record link.
    - Transitions to `COMPLETED` trigger automated notification dispatches to the assigned `Teacher`
      to schedule the final thesis/report exam.

### 4.2 Reflective Journal Approval State Machine

Daily journals compiled by students require dual-mentor verification.

```
 +-------------+
 |    DRAFT    | (Saved locally or server-side by Student)
 +-------------+
        |
        | Student Submits
        v
 +-------------+
 |  SUBMITTED  | (Visible in Supervisor queue for review)
 +-------------+
    /       \
   /         \ Supervisor Approves
  / Rejected  v
 /       +-------------+
v        | SUPERVISOR  | (Visible in Teacher queue for final signoff)
+-----+  |  APPROVED   |
| REVISION_REQUIRED |  +-------------+
+-----+     /       \
           /         \ Teacher Approves
          / Rejected  v
         /       +-------------+
        v        |  FINALIZED  | (Locked, factored into grading)
                 +-------------+
```

- **Transition Constraints**:
    - Once a journal transitions to `FINALIZED`, the content becomes read-only for students,
      supervisors, and teachers.
    - If the Supervisor or Teacher rejects the entry (`REVISION_REQUIRED` - _Revision Required_), the status
      reverts, unlocking edit access for the student, and requires a mandatory feedback string.

---

## 5. Network & Offline Synchronization Protocols

Due to unreliable mobile data access at remote host locations (e.g., agricultural fields, rural
workshops), Internara implements a secure client-side buffering protocol for location tracking.

```
[Attendance Trigger]
       |
       v
[Detect Connectivity]
   /        \
  / Online   \ Offline
 v            v
[Post DB]  [Store in Browser IndexedDB/LocalStorage]
           * Encrypt coordinates with Session Hash
           * Seal payload with Device Timestamp
                  |
                  +---> [Worker Detects Network Restored]
                            |
                            v
                        [Decrypt & Validate Payload Hash]
                            |
                            v
                        [Sync with API Endpoint]
```

### 5.1 Verification Security against Clock Spoofing

To prevent students from bypassing attendance rules (e.g., disabling network connectivity to
manually back-date timestamps on their client devices), the offline sync payload must follow this
validation protocol:

1. **Payload Signing**: Every offline-logged coordinate pair (`lat`, `lng`) and timestamp
   (`logged_at`) is packaged with a client-generated checksum generated from the student's active
   session token.
2. **Network Integrity Verification**: Upon syncing with the server, the endpoint compares the
   client-reported `logged_at` timestamp with the server receipt timestamp. If the time delta
   exceeds the threshold (e.g., out-of-bounds syncing delay greater than 24 hours), the record is
   marked `PENDING_REVIEW` for the Supervisor to manually audit.
3. **Mock Location Detection**: Client-side JS scripts check browser APIs for the presence of mock
   location providers. If flags are active, the payload is labeled with a spoof indicator.

---

## 6. Infrastructure Dimensioning & Concurrency Optimization

The system is configured to support cohorts of **500 to 1,000 active students** placed
simultaneously. The following sizing configurations are recommended for server deployments:

### 6.1 Server Sizing & Virtualization (Production)

- **Compute Instance (VPS)**: Minimum 2 vCPUs (Intel Xeon / AMD EPYC optimized), 4GB RAM, and 50GB
  NVMe SSD storage.
- **PHP Pool Configuration (`php-fpm.conf`)**:
    - `pm = dynamic`
    - `pm.max_children = 50`
    - `pm.start_servers = 10`
    - `pm.min_spare_servers = 5`
    - `pm.max_spare_servers = 20`
- **Nginx Configuration**: Max client body size set to 10MB to accommodate student photo uploads for
  attendance verification, with `gzip` compression enabled for JSON and HTML formats.

### 6.2 Caching & Queue Worker Configuration

- **Cache Provider**: Redis (highly recommended over database cache driver for large cohorts to
  avoid write locking during peak periods).
- **Queue Strategy**: Separate queue pipelines:
    - `default`: For notifications, daily email reminders.
    - `documents`: Dedicated queue worker for compiling PDF report packages and certificates via
      headless chromium or wkhtmltopdf.
- **Supervisor Worker Allocation**: Allocate a minimum of 2 child processes for the `documents`
  queue to prevent processing congestion during the graduation period.

### 6.3 Database Settings (SQLite WAL Mode)

If SQLite is chosen as the database engine (for small-to-medium schools under 500 active students),
the developer must verify that SQLite runs in Write-Ahead Logging (WAL) mode to allow concurrent
reads during student check-ins:

```sql
PRAGMA journal_mode=WAL;
PRAGMA synchronous=NORMAL;
PRAGMA busy_timeout=5000;
```

---

## 7. Security, Compliance & Audit Trail Architecture

### 7.1 PII Redaction Mapping

To comply with the Indonesian Personal Data Protection Law (UU No. 27 Tahun 2022 - _UU PDP_),
student and supervisor data must be masked in log outputs. The following fields are labeled
sensitive and parsed through the system masker:

```
+--------------------+----------------------------+-----------------------+
| Database Field     | Classification             | Masking Algorithm     |
+--------------------+----------------------------+-----------------------+
| email              | Direct Identifier          | Redact user part      |
| phone              | Direct Identifier          | Mask mid digits       |
| national_id_number | Indirect Identifier (NISN) | Mask trailing chars   |
| password           | Credential                 | Full redaction        |
| address            | Indirect Identifier        | Generic city output   |
+--------------------+----------------------------+-----------------------+
```

### 7.2 Cryptographic PDF Seals

Printed certificates utilize a unique hash generated from the compiled grade record:

```
Certificate Hash = SHA-256(student_id + institutional_code + final_score + issuer_private_key)
```

The resulting hash is embedded in a QR code link pointed back to:
`https://internara.school.sch.id/verify/{hash}`

This verifier endpoint yields the authentic digital record, exposing any offline manipulation of the
printed document.

---

## 8. Disaster Recovery Targets

- **Recovery Point Objective (RPO)**: Maximum 4 hours of data loss. Database backups (SQLite files
  or MySQL dumps) must be scheduled via system cron tasks every 4 hours and mirrored to an off-site
  S3-compatible cloud storage bucket.
- **Recovery Time Objective (RTO)**: Under 1 hour to spin up a replacement Docker VM instance and
  restore the latest database state.

---

## 9. Dual Mentor Fallback & Optionality Protocol

To prevent operational bottlenecks caused by industry supervisors failing to access the system
regularly, Internara implements a **Dual Mentor Fallback Protocol**. This mechanism ensures that
while corporate supervisors are invited to collaborate, the academic timeline is never blocked by
industry inactivity.

### 9.1 Attendance & Daily Journal Verification Fallbacks

- **Optionality**: Daily clock-in/out and reflective logbooks do not require immediate supervisor
  sign-off to advance through the academic checks.
- **Bypass Window**: If a journal entry remains in the `SUBMITTED` state for more than a
  configurable period (default: 48 hours) without action from the corporate supervisor, the system
  raises an auto-escalation flag.
- **Teacher Sign-off Override**: The assigned `Teacher` is equipped with bypass permissions to
  directly transition journals from `SUBMITTED` to `FINALIZED`. When this bypass occurs:
    - The system records the action as `verified_by_fallback` in the journal model.
    - A log entry is appended to the audit trail identifying the teacher who authorized the bypass.
    - The supervisor's queue for that specific entry is automatically cleared.

### 9.2 End-of-Placement Competency Evaluation Fallbacks

At the completion of a student's placement, a rubric-based industry evaluation is required.

- **Dual Grading Paths**:
    1. **Standard Path**: The Industry Supervisor fills out the on-site evaluation form (weight:
       40%). The Teacher fills out the school evaluation (20%) and report/exam score (40%).
    2. **Bypass Path (Proxy Entry)**: If the supervisor is unresponsive, the `Teacher` can activate
       a "Proxy Evaluation" toggle. This enables the teacher to enter scores on behalf of the
       supervisor based on physical evaluation sheets or verbal feedback.
    3. **Bypass Path (Weight Redistribution)**: Alternatively, the administrator can configure the
       program to recalculate weights dynamically if no supervisor score is submitted:
        - _Supervisor Weight (40%)_ is redistributed: 20% is added to the Teacher's evaluation
          weight, and 20% to the Report/Exam weight. The new formula becomes:
            ```
            Grade = (Teacher Score × 40%) + (Report/Thesis Exam × 60%)
            ```
- **Verification Security**: Any certificate compiled using fallback weights or proxy scores is
  stamped with a metadata indicator tag to maintain transparent audit compliance.
