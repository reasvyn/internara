# Application Blueprint: Curriculum Expert Validation (BP-VNV-002)

**Blueprint ID**: `BP-VNV-002` | **Requirement ID**: `SYRS-V-002` | **Scope**:
`Verification & Validation`

---

## 1. Strategic Context

- **Spec Alignment**: This blueprint authorizes the operational workflow audit required to satisfy
  **[SYRS-V-002]** (Curriculum Expert Validation) and **[SYRS-C-001]** (Logic Scope).
- **Objective**: Ensure that the system's business logic, scoring algorithms, and reporting formats
  faithfully replicate official vocational internship regulations.
- **Rationale**: The system operates within a regulated educational environment. By requiring formal
  curriculum expert validation, we ensure that the software facilitates academic compliance and that
  generated certificates are legally and institutionally valid.

---

## 2. Logic & Architecture (Systemic View)

### 2.1 Workflow Fidelity

The audit MUST verify the following "Ground Truth" logic:

1.  **Dual-Supervision Enforcement**: The system physically prevents final assessment without both
    Mentor and Teacher input.
2.  **Scoring Weighting**: The mathematical weights defined in the `ComplianceService` precisely
    match the current year's curriculum requirements.

### 2.2 Reporting Completeness

- **Mandatory Identifiers**: Verification that competency achievement reports include student NISN,
  NIP of signatories, and official institutional logos as per regulatory standards.

---

## 3. Verification Strategy (V&V View)

### 3.1 Unit Verification

- **Math Accuracy**: Verification of the `AssessmentService` outputs against manual calculation
  spreadsheets provided by the curriculum experts.

### 3.2 Feature Validation

- **Cohort Sovereignty**: Integration tests verifying that academic scoping correctly segregates
  multi-cycle internship programs as mandated by the curriculum.
- **Terminology Audit**: Verification that all user-facing domain terms (e.g., "Industrial Work
  Practice") align with the institution's Ubiquitous Language.

---

## 4. Compliance & Standardization (Integrity View)

### 4.1 Regulatory Alignment

- **Archival Policy**: Verification that the `SoftDeletes` implementation satisfies institutional
  data retention requirements for student vocational records.

---

## 5. Documentation Strategy (Knowledge View)

### 5.1 Engineering Record

- **Audit Records**: Create `docs/dev/audits/curriculum-validation-v1.md` to store formal expert
  feedback and compliance sign-offs.

### 5.2 Stakeholder Manuals

- **User Guide**: Update `docs/wiki/assessment-and-evaluation.md` with verified curriculum workflows
  and scoring rules.

---

## 6. Exit Criteria & Quality Gates

- **Acceptance Criteria**: Workflow compliance verified; Scoring parity achieved; Reporting fidelity
  confirmed.
- **Verification Protocols**: Formal sign-off by the Curriculum Expert in the release notes.
- **Quality Gate**: Comparison audit between manual and automated scores for 10 test subjects
  confirms zero variance.

---

_Application Blueprints prevent architectural decay and ensure continuous alignment with the
foundational specifications._
