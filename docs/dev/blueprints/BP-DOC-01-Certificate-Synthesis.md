# Blueprint: Certificate Synthesis & Verification (BP-DOC-01)

**Blueprint ID**: `BP-DOC-01` | **Scope**: Intelligence & Delivery | **Compliance**: `ISO/IEC 25010`

---

## 1. Context & Strategic Intent

This blueprint defines the infrastructure for generating authoritative institutional records. It establishes the protocols for synthesizing final student outcomes into certified PDF documents and provides a secure mechanism for third-party verification of student achievements.

---

## 2. Document Synthesis Engine (S3 - Scalable)

The `Report` module acts as the orchestrator for all document generation.

### 2.1 The Synthesis Pipeline
1. **Data Collection**: Retrieves the final result via **Readonly DTO** from the `Assessment` module.
2. **Template Resolution**: Selects the institutional PDF template based on the School and Department.
3. **Asynchronous Generation**: Utilizes the `ReportGenerator` (backed by **ShouldQueue**) to render the PDF.
4. **Private Storage**: Persists the artifact in the `Media` module using the `private` disk.

### 2.2 Forensic QR-Code Verification (S1 - Secure)
To prevent certificate forgery, every document MUST include a unique verification anchor.
- **Signed URL**: A Laravel **temporarySignedRoute** that points to a public verification endpoint.
- **QR-Code**: The signed URL is encoded into a QR code.
- **Access Protection**: Document downloads MUST be gated via **Signed URLs** (OWASP A01).

---

## 3. Integrity & Immutability (S2 - Sustain)

- **Issuance Lock**: Once a certificate is generated, the underlying `Assessment` and `Registration` records are locked. Mutative attempts MUST trigger a `DomainException`.
- **i18n**: Generated PDFs MUST respect the active system locale for all labels and date formats.

---

## 4. Technical Contracts

### 4.1 `ReportGenerator` Responsibilities
- `generate(string $type, array $params)`: Synchronous generation for small documents.
- `queue(string $type, array $params)`: Asynchronous synthesis for large cohorts.

---

## 5. Verification & Validation (V&V) - TDD Strategy

### 5.1 Document Integrity Tests (S1 - Secure)
- **QR Signature Tampering Audit**: 
    - **Scenario**: Generate a valid QR-code URL. Modify one character in the UUID or the signature hash.
    - **Assertion**: Scanning the tampered URL MUST return a `403 (Invalid Signature)` or show a "Document Not Verified" state.
- **Signed Download Audit**: Verify that the direct PDF file path on the `private` disk is NOT accessible without a valid Laravel temporary signature.

### 5.2 PDF Standard & A11y Tests
- **A11y Metadata Audit**: Use a PDF parser in tests to verify that generated documents contain the required `Title`, `Author`, and `Language` metadata (WCAG 2.1 AA).
- **Immutability Audit**: Verify that after a certificate is issued, calling `AssessmentService::update()` for that student throws a `DomainException`.

### 5.3 Architectural Testing (`tests/Arch/ReportingTest.php`)
- **Queueing Invariant**: Ensure the `queue()` method in `ReportGenerator` actually pushes a job to the `default` or `reports` queue using `Queue::fake()`.
- **Private Sovereignty**: Verify that generated reports are stored exclusively on the `private` filesystem disk.

### 5.4 Functional & i18n
- **Branding Priority**: Verify that the PDF header correctly uses the institutional logo from the `School` module via the `Media` service.
- **i18n**: Verify that a student with `en` locale receives a certificate with English labels, and `id` locale receives Indonesian.

_This blueprint records the current state of document synthesis. Evolution of the certification and verification protocols must be reflected here._
