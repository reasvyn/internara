# Certificate Domain

## Purpose

Certificate manages credentialing — template-based certificate issuance, revocation,
and serial number tracking.

---

## Design Principles

### 1. Unique Serial Numbers

Certificates use strictly sequential, unique serial numbers. Revoked serials are
permanently retired — never reused.

### 2. Batch Issuance

Cohort batch issuance is supported. One failure does not block the entire batch.

---

## Models

| Model | Key Fields |
|---|---|
| `Certificate` | registration_id, certificate_number, template_id, status, issued_at |
| `CertificateTemplate` | name, layout, content_template, is_active |

## Actions

| Action | Type |
|---|---|
| `CreateCertificateTemplateAction` | Command |
| `IssueCertificateAction` | Command |
| `BatchIssueCertificateAction` | Command |
| `RevokeCertificateAction` | Command |

## Where to Find It

- `app/Domain/Certificate/Models/`
- `app/Domain/Certificate/Actions/`
