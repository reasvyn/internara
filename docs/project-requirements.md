# Project Requirements — PKL Management System (SMA/SMK)

> Last updated: 2026-06-05
> Changes: Created comprehensive requirements specification for Indonesian SMA/SMK PKL lifecycle under global standards at 500–1000 user scale

This document specifies the functional, non-functional, and localized compliance requirements for the **Internara** fieldwork management system, tailored to Indonesian vocational school (SMK/SMA) regulations while upholding global enterprise engineering standards.

---

## 1. Context & Scale Analysis

Vocational schools (SMK) in Indonesia require mandatory industry work placement (PKL - *Praktik Kerja Lapangan*) for a duration of 3 to 6 months. A typical medium-to-large SMK has a cohort of **500 to 1,000 active students** placed simultaneously across **150 to 300 partner companies (DUDI - *Dunia Usaha & Dunia Industri*)** during a single placement period.

### Peak Load Profiles
The system must be optimized to handle the following high-concurrency windows:
* **Morning Clock-In (07:00 – 08:30 WIB)**: High concurrent writes as 1,000 students log location-tagged attendance.
* **Afternoon Clock-Out (16:00 – 17:30 WIB)**: High concurrent write operations for check-out and daily journal submissions.
* **End-of-Period Grading (Last 2 Weeks)**: High read/write activity by teachers and supervisors compiling evaluations, final grades, and certificate issuance.

---

## 2. Indonesian Localization Requirements

To comply with the Indonesian Ministry of Education (Kemendikbudristek) regulations (specifically *Kurikulum Merdeka* and *K-13* PKL guidelines), the system must support:

### 2.1 Regulatory Workflows & Personas
* **Dua-Mentor Supervision**: Explicit division of duties between the *Guru Pembimbing* (School Mentor) and *Pembimbing Lapangan* (Industry Supervisor).
* **NPSN & NISN Integration**: Data models must store the School's National NPSN (*Nomor Pokok Sekolah Nasional*) and the student's unique NISN (*Nomor Induk Siswa Nasional*).
* **Official Document Templates**: Automated rendering of standard compliance paperwork:
  * *Surat Pengantar PKL* (Placement Introduction Letter)
  * *Surat Persetujuan Orang Tua* (Parental Consent Form)
  * *Daftar Hadir & Jurnal Kegiatan* (Attendance & Activity Logs)
  * *Sertifikat PKL* (Completion Certificate with competency score sheets)

### 2.2 Local Technical Constraints
* **Low-Bandwidth Mobile Optimization**: Students often access the app on budget mobile devices with unstable mobile data. UI/UX must be lightweight, utilizing minimal assets and gzip compression.
* **Offline-Capability**: System fallback mechanics to record attendance timestamps locally in the browser storage if mobile network connection drops during clock-in, syncing when connectivity resumes.
* **Geofencing & Fake GPS Detection**: Geolocation validation utilizing browser APIs to check if coordinate locations correspond to the designated partner company address. The system must flag or block spoofed coordinates (Fake GPS apps).

---

## 3. Functional Requirements

### 3.1 Program Setup & Administration
* **Academic Year & Phases**: Administrative configuration of academic years, departments (jurusan), and multi-stage placement phases.
* **Partnership Management**: Host company registry (industry sector, addresses, phone numbers, contact persons, and slot quotas).

### 3.2 Placement & Slot Allocations
* **Quota-based Placement**: Auto-allocation or manual assignation of students to company slots. 
* **Placement Requests**: Student-initiated request workflows for change of placement due to structural conflicts, requiring teacher approval.

### 3.3 Daily Operations (Attendance & Journals)
* **Check-In/Out Verification**: Location-validated, timestamped records. Supports photo attachment verification.
* **Reflective Logbook (Jurnal Kegiatan)**: Daily narrative entry explaining tasks performed and competencies practiced. Must go through a dual-approval queue (Supervisor reads first, Teacher finalizes).

### 3.4 Assessment & Evaluation
* **Competency Rubric Matrix**: Mapping evaluation forms to specific educational competency standards (*Elemen Kompetensi*).
* **Multi-Weighted Grades**: Automatic calculation of composite scores:
  ```
  Grade = (Supervisor Score × 40%) + (Teacher Score × 20%) + (Report/Thesis Exam × 40%)
  ```
* **Presentation Examination**: Logbook for scheduled exams, including assessors, slide files, and revision requirements.

### 3.5 Certification & Issuance
* **Certificate Generator**: Dynamically compiled PDF certificates containing grades and signed seals.
* **QR Cryptographic Verification**: Public URL verification via a secure QR code on printed certificates, preventing forgery.

---

## 4. Non-Functional Requirements (Global Industry Standards)

### 4.1 Performance & Scalability (At 1,000 Users Scale)
* **Concurrency**: Must sustain up to 100 requests per second (RPS) during peak clock-in windows without exceeding a response time of 500ms.
* **Caching**: Aggressive caching of static queries (e.g., student dashboard statistics, theme styles) using a central registry (`app/Support/CacheKeys.php`).
* **Database Strategy**: For 500–1000 active users, SQLite is suitable for read-heavy local deployments. However, the system must support simple migration to PostgreSQL or MySQL for production scalability.

### 4.2 Security & Data Protection
* **PII Redaction**: All student personal details (NISN, emails, phone numbers) must be masked in application log channels using `PiiMasker`.
* **Action Logs (Audit Trail)**: Every mutation (e.g., changing placement, editing scores) must be recorded in an immutable audit trail using Spatie Activity Log.
* **Content Security Policy (CSP)**: Strict headers to prevent XSS and clickjacking.

### 4.3 Reliability & Asynchronous Processing
* **Background Queuing**: Heavy tasks (PDF certificate compiling, email notifications, media uploads) must be delegated to queue workers (`queue:work` with Redis/Database drivers) to keep the HTTP response loop fast and responsive.

---

## 5. Summary of Compliance Matrix

| Requirement | SMK Regulations | Global Engineering Standard | Internara Implementation |
|---|---|---|---|
| **Identity** | NISN, NPSN, Jurusan | UUID Primary Keys, Relational Integrity | BaseModel + UUIDs, customized attributes |
| **Attendance** | Validated Presence | Geofencing, Cryptographic Timestamps | Browser Location API + timezone logs |
| **Audit Log** | Supervisor Approvals | Immutable Action Audit Logs | Spatie activitylog + SmartLogger |
| **Certification** | Grade Sheet PDF | Cryptographic Verification QR Code | Document module + QR verification route |
