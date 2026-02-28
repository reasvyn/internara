# Blueprint: Authoritative Reporting (BP-DOC-F103)

**Blueprint ID**: `BP-DOC-F103` | **Requirement ID**: `SYRS-F-103` | **Scope**: Intelligence & Delivery

---

## 1. Context & Strategic Intent

This blueprint defines the generation of QR-signed institutional records and competency reports. It ensures that output documents are verifiable, accessible, and correctly branded.

---

## 2. Technical Implementation

### 2.1 QR Verification Loop (S1 - Secure)
- **Signature Hash**: Every PDF MUST contain a QR code encoding a Signed URL that links back to the system's verification endpoint.
- **Tamper Evidence**: Modifying the document metadata or the QR code payload MUST result in a "Verification Failed" state on the portal.

### 2.2 Branded Synthesis (S2 - Sustain)
- **Branding Injection**: The PDF generator MUST automatically inject the school logo and brand colors from the `School` and `Setting` modules.
- **i18n Metadata**: Generated files MUST contain metadata (Title, Author, Lang) matching the system locale (WCAG 2.1 AA).

---

## 3. Verification & Validation - TDD Strategy (3S Aligned)

### 3.1 Secure (S1) - Boundary & Integrity Protection
- **Feature (`Feature/`)**:
    - **Signature Audit**: Test that scanning a QR code with a modified UUID or tampered hash returns a `403` or "Invalid" state.
    - **Private Disk Sovereignty**: Verify that generated reports are stored on the `private` disk and are NOT accessible via public URLs.

### 3.2 Sustain (S2) - Maintainability & Semantic Clarity
- **Unit (`Unit/`)**:
    - **Metadata Audit**: Use a PDF parser to verify that generated documents contain correct `Lang` and `Author` tags.
- **Architectural (`arch/`)**:
    - **Contract Compliance**: Ensure all Report providers implement the `ReportProvider` interface.

### 3.3 Scalable (S3) - Structural Modularity & Performance
- **Feature (`Feature/`)**:
    - **Queue Invariant**: Verify that calling the report generation service pushes a job to the `default` queue instead of blocking the request.
- **Architectural (`arch/`)**:
    - **Isolation**: Verify that the `Report` module does not depend on UI-specific Livewire state for generation logic.

---

## 4. Documentation Strategy
- **Admin Guide**: Update `docs/wiki/reporting.md` to document the types of authoritative reports available and the QR verification process.
- **Developer Guide**: Update `modules/Report/README.md` to document the PDF template system and QR signature hashing algorithm.
- **User Guide**: Add instructions in `docs/wiki/reporting.md` for external stakeholders on how to verify documents using the QR code.
