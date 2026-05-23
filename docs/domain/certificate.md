# Certificate Domain

## Purpose

Certificate handles the credentialing outcome of a completed internship. A certificate is a legal 
and reputational artifact — it represents an institutional endorsement that a student has met 
all requirements and successfully finished their internship program. This domain manages the full 
lifecycle: defining certificate templates (layout, branding, field mapping), issuing certificates 
(with unique serial numbers and secure document generation), managing revocations (with 
documented reasons and audit trails), and providing third-party verification (a public endpoint 
for employers to confirm authenticity).

## Boundary

**In scope:** Certificate template management (layout definition, branding elements, field 
positions, versioning), certificate issuance (generating certificates for completed registrations 
with unique serial numbers), batch issuance for cohort completions, certificate revocation (with 
required reason category and detailed explanation, logged with revoker identity and timestamp), 
certificate number serialization and uniqueness enforcement across the entire system, public 
certificate verification endpoint (no authentication required, returns status and limited 
metadata), certificate regeneration (same data on a new template or format).

**Out of scope:** Completion eligibility determination (Registration domain owns the completion 
status computation — Certificate consumes it as a gate), document rendering engine (Document 
domain provides PDF generation, file storage, and serving), internship program definitions and 
requirements (Internship domain), assessment scoring and rubric data (Assessment domain), 
attendance minimum threshold checks (Attendance domain), assignment completion rates (Assignment 
domain).

## Key Concepts

**Certificate Templates.** Templates define the complete visual and structural specification for 
a certificate. They specify: page layout (portrait or landscape, margins, section positioning), 
text field mapping (student full name, program title, institution name, issuance date, 
certificate number, graduation date — each mapped to a specific position on the page), branding 
elements (institution logo, official seal, signature line images, background artwork or 
watermark), typography (font family, sizes, colors for each text element), and format (paper 
size, color space, print vs. digital optimization). Templates are versioned: when the institution 
updates its branding or certificate design, a new template version is created. Existing 
certificates retain their original template version reference, so the exact design is always 
traceable.

**Certificate Issuance.** Issuance creates a permanent, immutable certificate record. The 
issuance process follows a strict sequence: (1) verify the source registration is in a COMPLETED 
status — no other status qualifies; (2) resolve the correct template by program and locale, 
selecting the current active version; (3) generate a unique, non-repeating serial number in the 
configured format (e.g., INST-YYYY-SEQ, where SEQ is a zero-padded sequential number that never 
resets); (4) invoke the Document domain's rendering pipeline to generate the certificate PDF; (5) 
store the rendered file via the media library; (6) create the Certificate record with all 
metadata — serial number, template version, issuance timestamp, linked registration, and file 
reference. Once created, the certificate record and its generated file are completely immutable.

**Certificate Serialization.** Each certificate receives a globally unique serial number 
following a configurable numbering scheme. Serial numbers are strictly sequential (no gaps filled 
by later issuances) and are never reused. If a certificate is revoked, its serial number is 
permanently retired — a replacement certificate receives a new serial number. This zero-reuse 
policy ensures that verification queries return unambiguous results: every serial number maps to 
exactly one certificate, past or present.

**Revocation.** A certificate can be revoked when issuance was in error or when post-completion 
issues emerge. Revocation requires: identification of the certificate to revoke, a reason 
category (ADMINISTRATIVE_ERROR — issued to wrong person or with incorrect data; 
POLICY_VIOLATION — post-completion discovery of violation; FRAUDULENT_ACTIVITY — evidence of 
fraud in the internship; OTHER — documented explanation for any other reason), a detailed 
written explanation, and the identity of the revoking administrator. The Certificate record is 
updated with status REVOKED, the reason, the explanation, the revoker's identity, and the 
revocation timestamp. The generated file remains stored but is no longer served as a valid 
certificate. Revocation is irreversible — no mechanism exists to un-revoke. If a valid 
certificate is needed, a new one must be issued with a new serial number.

**Third-Party Verification.** Employers, educational institutions, and other third parties can 
verify certificate authenticity without requiring any account or authentication. They visit a 
public verification page and enter the certificate's serial number (or scan a QR code printed on 
the certificate that encodes the serial number and a verification URL). The verification endpoint 
returns: certificate status (VALID — active and authentic; REVOKED — was valid but has been 
revoked; NOT_FOUND — serial number does not exist), holder name, program title, issuance date, 
and if revoked, the revocation date and reason category. No additional personal data is exposed. 
The verification endpoint is rate-limited to prevent abuse but otherwise unrestricted.

## Requirements

### User Stories & Rules

| Role | Story |
|------|-------|
| Admin | As an admin, I want to create certificate templates with branding and layout so that certificates reflect the institution's identity |
| Admin | As an admin, I want to issue certificates individually or in batch so that completed students receive their credentials |
| Admin | As an admin, I want to revoke a certificate when necessary so that the credentialing system remains trustworthy |
| Student | As a student, I want to download my certificate so that I can present it to employers |
| Third-party | As an employer, I want to verify a certificate's authenticity via a public endpoint so that I can confirm a candidate's credentials |
| System | As the system, I want to generate unique serial numbers so that every certificate is unambiguously identifiable |
| System | As the system, I want to permanently retire revoked serial numbers so that verification remains unambiguous |

### Process Flow

```
Issuance Sequence:

1. Verify registration is COMPLETED
2. Resolve active certificate template
3. Generate unique serial number
4. Render PDF via Document domain
5. Store rendered file via media library
6. Create Certificate record

Certificate Lifecycle:

ISSUED ──→ REVOKED (terminal, irreversible)
```

- Certificates can only be issued for registrations with COMPLETED status
- Serial numbers are unique, strictly sequential, and never reused after revocation
- Issued certificates are entirely immutable — corrections require revocation + new issuance
- Revocation is irreversible — no un-revoke mechanism exists
- Public verification endpoint requires no authentication, exposes limited metadata

### Key Operations

| Action | Description |
|--------|-------------|
| `CreateCertificateTemplateAction` | Creates a new certificate template with layout and field mapping |
| `IssueCertificateAction` | Issues a single certificate for a completed registration |
| `BatchIssueCertificateAction` | Issues certificates for multiple completed registrations in one operation |
| `RevokeCertificateAction` | Revokes a certificate with required reason category and explanation |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `Certificate`, `CertificateTemplate` |
| **Enums** | `CertificateStatus` — `ISSUED`, `REVOKED` (terminal) |
| **Livewire** | `CertificateTemplateManager`, `CertificateList`, `StudentCertificates` |
| **Controller** | `CertificateDownloadController` (authenticated download) |
| **Support** | `CertificateRenderer` (PDF rendering pipeline) |

## Dependencies

| Dependency | Reason |
|---|---|
| Registration | COMPLETED registration status is the prerequisite gate for certificate issuance |
| Document | PDF rendering pipeline produces the certificate file; media library stores it |
| Internship | Program name, description, and dates appear on the certificate content |
| Core | BaseAction, BaseModel, SmartLogger |


- Certificates can only be issued for registrations with COMPLETED status — no other status 
qualifies, no overrides permitted.
- Serial numbers are unique, strictly sequential, and permanently retired after revocation — no 
reuse, no gaps filled.
- Issued certificates are entirely immutable — any correction requires revocation of the old 
certificate and issuance of a new one with a new serial number.
- Revocation is irreversible — there is no undo, no un-revoke mechanism, no grace period.
- Certificate templates cannot be deleted if any issued certificate references that template 
version.
- The public verification endpoint requires no authentication and must remain accessible without 
login.
- Each issued certificate records the exact template version used, enabling perfect visual 
reconstruction for audit.
- Batch issuance processes each certificate independently — one failure does not block the 
entire batch.
