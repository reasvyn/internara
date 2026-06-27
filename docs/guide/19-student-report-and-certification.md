# Chapter 19: Student Report & Certification

> **Last updated:** 2026-06-16

This chapter covers the final stage of the internship program: the **final grade card** (Nilai Raport
PKL) which aggregates scores from all assessment sources, and the **completion certificate** that
is issued after the grade is finalised.

---

## 19.1 Final Grade Card Overview

The final grade card (rapor) is the official record of a student's internship performance. It
aggregates scores from multiple sources into a composite final grade. Unlike the daily logbook or
individual assignments, the grade card is a **system-compiled report** — not a document written by
the student.

The grade card is calculated per registration (1:1 with each student's internship enrolment).

### 19.1.1 Score Sources

The final grade draws from these assessment sources:

| Source | What It Represents | Who Provides It |
|--------|-------------------|-----------------|
| **Assessment rubric** | Competency evaluation (skills, attitude) | Teacher / Supervisor |
| **Assignment submissions** | Task grades (including the final report) | Teacher |
| **Attendance rate** | Clock-in/out compliance | System (auto) |
| **Logbook compliance** | Journal submission rate | System (auto) |

### 19.1.2 Grade Formula

The composite final grade uses weights configured per internship program. A typical formula:

```
Final Grade = (Supervisor Assessment × 40%)
            + (Teacher Assessment × 20%)
            + (Assignment Average × 20%)
            + (Exam / Presentation × 20%)
```

Weights are defined in the **Internship Program** settings and can be customised per batch.

---

## 19.2 Grade Card Data (What It Stores)

When a grade card is finalised, it captures:

| Field | Description | Example |
|-------|-------------|---------|
| **Supervisor Score** | Score from industry supervisor assessment | 85.5 |
| **Teacher Score** | Score from school teacher assessment | 90.0 |
| **Exam Score** | Exam or presentation score | 78.3 |
| **Final Score** | Weighted composite score | 84.6 |
| **Grade Letter** | Letter grade (A/B/C/D/E) | A |
| **Industry Feedback** | Feedback from the host company | "Excellent performance" |
| **Status** | Current workflow state | Approved |

### 19.2.1 Archived Snapshot

On finalisation, the system captures a full **archived snapshot** of:
- Student identity (name, email, NISN)
- Internship program name and academic year
- Company name and address
- Department name
- Teacher and supervisor names
- All component scores and the composite score

This ensures the grade card remains readable even if the original records (student account,
company, program) are later deleted.

---

## 19.3 Grade Card Workflow

The grade card follows a workflow driven by the student's assessment results, not by student
input.

```
Score Sources ──> System compiles ──> Draft ──> Submitted
                                                  │
                              ┌───────────────────┼───────────────────┐
                              │                   │                   │
                         Teacher fills       Supervisor fills    System auto-imports
                         rubric scores       rubric scores       attendance & logbook
                              │                   │                   │
                              └───────────────────┼───────────────────┘
                                                  │
                                             Coordinator
                                             reviews & approves
                                                  │
                                             Finalised
                                                  │
                                        Certificate eligible
```

### 19.3.1 Draft / Submitted

When assessment data is available (rubric scores, assignment grades), the grade card is created
automatically. It starts in **Draft** status and transitions to **Submitted** when all required
scores are present.

### 19.3.2 Approved

A teacher or coordinator reviews the submitted grade card and can **Approve** it to accept the calculated scores and provide feedback.

### 19.3.3 Finalised

An administrator finalises the approved grade card. This:

1. Locks all scores permanently — no further changes
2. Captures the archived snapshot of all related metadata
3. Marks the registration as **certificate-eligible**

A finalised grade card **cannot be edited** under normal circumstances.

### 19.3.4 Grade Card Status Lifecycle

```
Draft ──> Submitted ──> Approved ──> Finalised
```

| Status | Meaning | Editable? |
|--------|---------|-----------|
| **Draft** | Scores are being collected | Yes (system) |
| **Submitted** | All scores present, awaiting review | No |
| **Approved** | Reviewed and accepted | No |
| **Finalised** | Locked permanently | No |

---

## 19.4 Cross-Role Proxy & Scoring

The grade card supports the Cross-Role Proxy model (see [ADR-014](../adr/adr-cross-role-proxy.md)):

- **Teacher** scores competencies assigned to the `teacher` role
- **Supervisor** scores competencies assigned to the `supervisor` role
- If the supervisor is unavailable, the teacher can **proxy** as supervisor — scoring their
  competencies and performing any supervisor action. The action is logged with
  `proxy_role = 'supervisor'` in the audit trail
- If no proxy acts and the supervisor has not scored, their assessment weight is redistributed
  to the teacher and exam components
- **Admin** can proxy for both teacher and supervisor roles

The grade card records the proxy status for audit transparency. See
[Chapter 18: Assignment & Assessment](18-assignment-and-assessment.md) for details on how
individual competencies are scored.

---

## 19.5 Certificate Overview

After a student's grade card is finalised, they become eligible for a **completion certificate**.
Certificates are digitally signed with a QR code for authenticity verification.

| Role | What They Can Do |
|------|------------------|
| **Admin** | Create certificate templates, issue certificates (single or batch), revoke certificates |
| **Student** | View and download own certificates |

---

## 19.6 Certificate Templates (Admin)

Before certificates can be issued, at least one certificate template must be created.

Navigate to **Admin → Certificate Templates** or go directly to `/admin/certificates/templates`.

### 19.6.1 Creating a Template

1. Click **Add Template**
2. Fill in the fields:

| Field | Description | Example |
|-------|-------------|---------|
| **Name** | Template name | Sertifikat PKL 2025/2026 |
| **Layout** | Page orientation | Portrait or Landscape |
| **Content Template** | HTML content with placeholders | See below |
| **Active** | Toggle visibility | Yes |

3. Click **Save Template**

### 19.6.2 Template Placeholders

The content template supports these placeholders which are replaced with actual student data:

| Placeholder | Replaced With |
|-------------|---------------|
| `{student_name}` | Student's full name |
| `{student_nis}` | Student's NIS/NISN |
| `{school_name}` | School name |
| `{department_name}` | Department (jurusan) name |
| `{company_name}` | Host company name |
| `{internship_name}` | Internship program name |
| `{start_date}` | Program start date |
| `{end_date}` | Program end date |
| `{duration}` | Program duration in months |
| `{score}` | Final grade score |
| `{score_letter}` | Letter grade (A/B/C/D/E) |
| `{certificate_number}` | Unique certificate number |
| `{issued_date}` | Date of issuance |
| `{supervisor_name}` | Industry supervisor name |

Example template:

```html
<div style="text-align: center; padding: 40px;">
    <h1>INTERNSHIP COMPLETION CERTIFICATE</h1>
    <p>This is to certify that</p>
    <h2>{student_name}</h2>
    <p>has successfully completed an internship program at</p>
    <h3>{company_name}</h3>
    <p>from {start_date} to {end_date}</p>
    <p>Final Score: {score} ({score_letter})</p>
    <p>Certificate Number: {certificate_number}</p>
    <p>Issued: {issued_date}</p>
</div>
```

---

## 19.7 Issuing Certificates (Admin)

Navigate to **Admin → Certificates** or go directly to `/admin/certificates`.

### 19.7.1 Single Issuance

1. Click **Issue Certificate**
2. Select the **student registration** (only students with finalised grade cards are shown)
3. Select the **certificate template**
4. Click **Issue** — the system:
   - Generates a unique certificate number
   - Creates a QR hash for verification
   - Renders the certificate PDF using the template
   - Stores the certificate record

### 19.7.2 Batch Issuance

For issuing certificates to an entire cohort at once:

1. Click **Batch Issue**
2. Select the **certificate template**
3. Choose the filter: students with finalised grade cards
4. Click **Batch Issue** — the system issues certificates to all eligible students
   (those who do not already have a certificate)

### 19.7.3 Revoking a Certificate

If a certificate needs to be invalidated:

1. Find the certificate in the list
2. Click **Revoke**
3. Confirm — the certificate status changes to **Revoked**

Revocation is permanent. The serial number is retired and cannot be reused.
The certificate remains on record for audit purposes.

---

## 19.8 Student: Viewing Certificates

1. Navigate to **Student Portal → My Certificates** from the sidebar
2. All issued certificates are displayed with:
   - Certificate number
   - Internship program name
   - Issue date
   - Download button

### 19.8.1 Downloading a Certificate

Click **Download** on any certificate to save the PDF. The certificate includes:
- Student name and details
- Internship program information
- Final score
- QR code for verification
- Certificate number

---

## 19.9 Troubleshooting

### Grade card shows incorrect score

Finalised grade cards cannot be edited. If a score correction is needed, contact an
administrator who can create a new assessment round. The original grade card remains as a
historical record.

### Certificate template not appearing

Templates must be set to **Active** to appear in the issuance dropdown. Ask an administrator
to activate the template.

### Student not appearing in certificate issuance list

The student must have a **finalised grade card** to be eligible for a certificate. Check the
student's grade card status first.

### Certificate download fails

- Ensure the certificate has been **issued** (not revoked)
- Check that the certificate template has valid content
- Contact an administrator if the PDF was not generated

### Certificate number shows duplicate

Certificate numbers are generated sequentially. If two certificates show the same number,
contact the system administrator (see RC-10 in known issues).

---

**← Previous: [Chapter 18: Assignment & Assessment](18-assignment-and-assessment.md)**
**Next: [Chapter 20: Evaluation & Incident](20-evaluation-and-incident.md)**
