# Application Blueprint: Intelligence & Delivery (ARC01-INTEL-01)

**Series Code**: `ARC01-INTEL` | **Scope**: `Phase 5: The RESULTS` | **Compliance**: `ISO/IEC 12207`

---

## 1. Context & Strategic Intent

This blueprint authorizes the construction of the **Assessment and Reporting Subsystem**. It 
finalizes the value loop by synthesizing operational telemetry into formalized competency 
evaluations and certified documents.

- **SyRS Traceability**:
    - **[SYRS-F-501]**: Competency-Based Assessment (Rubrics).
    - **[SYRS-F-502]**: Digital Certificate Synthesis (PDF/QR).
    - **[SYRS-F-601]**: Institutional Analytical Dashboards.

---

## 2. User Roles & Stakeholders

- **Instructors/Mentors**: Scoring students against rubric indicators.
- **School Administrator**: Oversight of institutional performance and certificate issuance.
- **Students**: Retrieval of final transcripts and certificates.

---

## 3. Modular Impact Assessment

1.  **Assessment**: Owners of the scoring rubrics and evaluation records.
2.  **Report**: Orchestrator for background document generation and scheduling.
3.  **UI**: Specialized analytical components and "Component Islands" for telemetry.

---

## 4. Logic & Architecture (The Intelligence Engine)

### 4.1 The Composite Score Invariant
- **Algorithm**: Final Grade = `(Attendance Weight * A) + (Journal Weight * B) + (Rubric Score * C)`.
- **Weights**: Configured at the `Internship Program` level to provide institutional 
  flexibility.

### 4.2 Document Synthesis (PDF)
- **Engine**: DomPDF / Snappy orchestrated via the `Report` module.
- **Security**: All certificates MUST include a unique **Signed URL** encoded in a QR Code 
  for third-party verification.

---

## 5. Contract & Interface Definition

### 5.1 `Modules\Assessment\Services\Contracts\AssessmentService`
- `evaluate(string $registrationUuid, array $scores): Assessment`: Formal scoring.
- `getTranscript(string $registrationUuid): array`: Synthesizes raw scores into a DTO.

### 5.2 `Modules\Report\Services\Contracts\ReportGenerator`
- `generate(string $type, array $params): string`: Returns the UUID of the generated file.
- `queue(string $type, array $params): void`: Asynchronous generation for bulk exports.

---

## 6. Data Persistence Strategy

### 6.1 Rubric Flexibility
- **JSON Invariant**: Assessment scores and rubric indicators are stored as **JSON** 
  to support dynamic curriculum changes across departments without schema migrations.

### 6.2 Forensic Immutability
- Once a certificate is issued, the underlying assessment record MUST be locked from 
  further modifications.

---

## 7. Verification Plan (V&V View)

### 7.1 Mathematical Verification
- **Test 1**: Unit tests verifying the composite score calculation against known inputs.
- **Test 2**: Verify that the QR Code URL correctly resolves to the public verification endpoint.

### 7.2 Performance Audit
- **Stress Test**: Verify that the system can handle 100+ concurrent PDF generations 
  without blocking the web worker (using Queue verification).

---

_This blueprint constitutes the authoritative engineering record for Intelligence & Delivery. 
The certificate is the ultimate artifact of the Internara ecosystem._
