# Certificate QR Verification — Public Authenticity Check

> **Spec ID:** J0M05

## Description

Specification for the public-facing certificate QR verification endpoint: an anonymous web page that
accepts a certificate hash and returns the certificate status (issued, revoked, not found) without
requiring authentication. The admin certificate lifecycle (template CRUD, issuance, batch, download,
revocation) is defined in [certification.md](J0M04-certification.md).

---

## 1. Problem Statements

### PS-1 — Employers Cannot Verify Certificate Authenticity

Employers and institutions receiving PKL certificates need to verify their authenticity. Without a
verification mechanism, forged certificates are indistinguishable from genuine ones. The system must
provide a public URL that accepts a certificate hash and returns the authenticity status.

### PS-2 — No Authentication Required for Verification

Verification must work for anyone with the certificate in hand — the employer, institution, or
student themselves. Requiring login would exclude verification by third parties who do not have an
Internara account.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide a public (unauthenticated) verification page that accepts a certificate hash |
| G2  | Return certificate status: ISSUED, REVOKED, or NOT FOUND |
| G3  | Display basic certificate metadata: student name, school name, issuance date |
| G4  | QR codes embedded on certificates contain a URL that resolves to the verification page |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Full certificate details or PDF download from the verification page |
| NG2  | Verification requires the full certificate number (hash is sufficient) |
| NG3  | Historical verification (revoked certificates retain their revocation status) |

---

## 3. User Stories / Use Cases

### UC-J0M05-1 — Employer Verifies a Certificate via QR Code

**Actor:** Employer / HR / Institution (anonymous)
**Preconditions:** Certificate has a QR code; employer has the certificate in hand
**Flow:**
1. Employer scans the QR code on the certificate
2. QR code resolves to `https://{school}.internara.app/verify/{qr_hash}`
3. Page displays: student name, school name, certificate number, issuance date, and status
4. Status shows ISSUED (green) or REVOKED (red) or NOT FOUND (gray)
**Postconditions:** Employer knows whether the certificate is authentic and current

### UC-J0M05-2 — Student Shares Verification Link

**Actor:** Student
**Preconditions:** Student has an issued certificate
**Flow:**
1. Student views their certificate in the student portal
2. Student copies the verification link (URL containing `qr_hash`)
3. Student shares link with employer or includes it in their portfolio
**Postconditions:** Verification link is shareable and works without login

---

## 4. Functional Requirements

| ID   | Requirement |
| ---- | ----------- |
| FR-J0M05-VR1 | `VerifyCertificateController` must handle `GET /verify/{qr_hash}` without authentication |
| FR-J0M05-VR2 | Controller must look up `Certificate::where('qr_hash', $qr_hash)->first()` |
| FR-J0M05-VR3 | If certificate not found: return view with status `not_found` |
| FR-J0M05-VR4 | If certificate found and ISSUED: return view with status `issued` and metadata (student name, school name, certificate number, issuance date) |
| FR-J0M05-VR5 | If certificate found and REVOKED: return view with status `revoked` and revocation date |
| FR-J0M05-VR6 | QR codes on certificates must embed the URL `https://{domain}/verify/{qr_hash}` |
| FR-J0M05-VR7 | The verification page must not expose any other student data beyond name, school, and certificate metadata |
| FR-J0M05-VR8 | The verification page must render without authentication middleware |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-J0M05-S1 | The verification page must not expose personally identifiable data beyond the certificate holder's name |
| NFR-J0M05-S2 | Rate limiting must prevent brute-force enumeration of valid QR hashes |
| NFR-J0M05-A1 | Verification page must meet WCAG 2.1 Level AA accessibility standards |
| NFR-J0M05-A2 | Status indicators must be color-blind accessible (icon + text, not color alone) |
| NFR-J0M05-L1 | All UI strings must use `__()` translation helper |
| NFR-J0M05-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |

---

## 6. API / Data Contracts

### Route

```php
// routes/web/public.php (no auth middleware)
Route::get('/verify/{qr_hash}', VerifyCertificateController::class)
    ->name('certificates.verify')
    ->middleware('throttle:5,1');  // 5 attempts per minute per IP
```

### Controller

```php
class VerifyCertificateController extends Controller
{
    public function __invoke(string $qr_hash): View
    {
        $certificate = Certificate::where('qr_hash', $qr_hash)->first();

        if (! $certificate) {
            return view('certificates.verify', ['status' => 'not_found']);
        }

        return view('certificates.verify', [
            'status' => $certificate->status->value,
            'studentName' => $certificate->registration->user->name,
            'schoolName' => brand('name'),           // via Brand helper
            'certificateNumber' => $certificate->certificate_number,
            'issuedAt' => $certificate->issued_at,
            'revokedAt' => $certificate->revoked_at,
        ]);
    }
}
```

---

## 7. Design Decisions

### DD-1 — QR Hash as Verification Token, Not Full Certificate Number

**Decision:** The QR code contains the `qr_hash` (random unique string), not the `certificate_number`.

**Rationale:** The hash is format-agnostic and shorter than the certificate number. It serves as a
proof-of-existence token — anyone with the hash can verify the certificate exists. A URL containing
the hash is easier to encode in a QR code than the longer certificate number.

**Trade-off:** The hash is meaningless to humans; the certificate number is the human-readable
identifier. Both should be displayed on the certificate for different use cases.

### DD-2 — Public Page Without Authentication

**Decision:** The verification page requires no authentication.

**Rationale:** Employers and institutions cannot be expected to have an Internara account. The
verification page is public by design.

**Trade-off:** Without authentication, the page is subject to brute-force enumeration of QR hashes.
Mitigated by rate limiting (5 attempts per minute per IP) and the fact that valid hashes are
unpredictable (cryptographically random).

---

## 8. Success Metrics

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Verification page load | < 500ms | Response time for valid/invalid hash |
| Rate limit enforcement | Blocked after 5 attempts/min | Throttle middleware |
| Accessibility | WCAG 2.1 AA | Automated accessibility scan |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [certification.md](J0M04-certification.md) | `Certificate` model, `qr_hash` generation, `CertificateStatus` enum |

### Build Guide
After implementing this spec, each certificate includes a QR code that links to a public verification
page. The page shows ISSUED, REVOKED, or NOT FOUND status without requiring login.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [certification.md](J0M04-certification.md) | Certificate issuance generates the QR hash and embeds the verification URL |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |

## Quick References
