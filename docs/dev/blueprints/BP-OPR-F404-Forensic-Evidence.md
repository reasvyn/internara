# Blueprint: Forensic Evidence (BP-OPR-F404)

**Blueprint ID**: `BP-OPR-F404` | **Requirement ID**: `SYRS-F-404` | **Scope**: Monitoring & Vocational Telemetry

---

## 1. Context & Strategic Intent

This blueprint defines the digital proof requirement for activities. It ensures that students can attach media as verifiable evidence of vocational execution, protected by cryptographic signatures.

---

## 2. Technical Implementation

### 2.1 Evidence Sovereignty (S1 - Secure)
- **Private Storage**: All activity media MUST be stored on the `private` filesystem disk.
- **Signed Exposure**: Attachments MUST only be accessible via Laravel Temporary Signed URLs.

---

## 3. Verification & Validation - TDD Strategy (3S Aligned)

### 3.1 Secure (S1) - Boundary & Integrity Protection
- **Feature (`Feature/`)**:
    - **Direct Access Audit**: Verify that a public URL guess for a private attachment returns a `404` or `403`.
    - **Signed URL Audit**: Test that an expired or tampered signed URL is rejected.

### 3.2 Sustain (S2) - Maintainability & Semantic Clarity
- **Unit (`Unit/`)**:
    - **PII Masking**: Verify that media metadata does not leak sensitive information in system logs.
- **Architectural (`arch/`)**:
    - **Contract Compliance**: Ensure `Media` module provides a stable abstraction for domain attachments.

### 3.3 Scalable (S3) - Structural Modularity & Performance
- **Architectural (`arch/`)**:
    - **Decoupling**: Ensure `Internship` module only holds media UUIDs, not direct filesystem paths.
- **Feature (`Feature/`)**:
    - **Batch Audit**: Verify that generating 10 signed URLs for a journal entry performs within 50ms.

---

## 4. Documentation Strategy
- **Infrastructure Guide**: Update `docs/dev/security.md` to document the private storage policy and temporary signed URL mechanism.
- **Developer Guide**: Update `modules/Media/README.md` to document the attachment lifecycle and authorization patterns.
- **Troubleshooting**: Update `docs/wiki/daily-monitoring.md` with common upload failure scenarios and file type restrictions.
