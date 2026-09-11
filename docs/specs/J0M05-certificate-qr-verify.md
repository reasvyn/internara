# Certificate QR Verification — Public Authenticity Check

> **Spec ID:** J0M05
> **Status:** Planned
> **Owner:** Certification
> **Depends on:** J0M04

## Description

Employers receiving a PKL certificate need one question answered in seconds: is this document genuine and still current. This spec defines the anonymous verification page behind every certificate QR code — a hash arrives, a status comes back (issued, revoked, or not found), and nothing else leaks. Issuance, templates, batch printing, and revocation themselves belong to [certification](J0M04-certification.md); this page only reads what issuance wrote.

---

## 1. Problem Statements

### PS-1 — Employers Cannot Tell a Forged Certificate from a Genuine One

A printed certificate is just paper and ink. Without a verification channel, a forged PKL certificate is indistinguishable from a genuine one, and the school's signature becomes decoration. Every certificate must carry a QR code that resolves to an authoritative status answer.
**→ Requirement:** FR-QR-001/002 (public lookup path), FR-QR-006 (QR URL embedded at issuance).

### PS-2 — Verifiers Have No Account and Never Will

The person checking the certificate is an HR officer at a company the school has never dealt with. Asking them to register, log in, or install anything guarantees the check never happens. Verification must work for a stranger with a phone camera and nothing else.
**→ Requirement:** FR-QR-001 (no authentication), UC-QR-001 (anonymous scan journey).

### PS-3 — Revoked Certificates Keep Circulating

Certificates get revoked — fraud discovered after issuance, administrative error, withdrawn sign-off. The paper copy keeps circulating regardless. A verifier who sees a revoked document must see REVOKED, not a stale ISSUED cached somewhere or a bare error page.
**→ Requirement:** FR-QR-004 (revocation display), NFR-QR-005 (no stale caching).

---

## 2. Goals & Non-Goals

### Goals

- **Anonymous verification page** — any holder of the QR link gets a status answer without login. *Why:* verifiers are strangers; friction kills the check.
- **Three honest answers** — ISSUED, REVOKED, NOT FOUND, each with exactly the metadata that answer justifies. *Why:* ambiguity is what forgers exploit.
- **Minimal disclosure** — name, school, certificate number, dates; nothing further about the student. *Why:* a public page is the worst place for a privacy leak.
- **Abuse-resistant by construction** — unpredictable hashes plus throttling so the page cannot be harvested. *Why:* a public lookup is also a public oracle.
- **Issuance-owned QR contract** — the URL format is frozen at issuance time so printed codes never rot. *Why:* paper outlives deployments.

### Non-Goals

- **Full certificate dossier or PDF download from the verification page**. *Why:* the page answers authenticity, it does not re-issue the document.
- **Lookup by certificate number or student name**. *Why:* the hash is the capability; searchable lookup would turn the page into a student directory.
- **Historical audit display for anonymous visitors**. *Why:* revocation history is an internal record; the public answer is current status only.
- **Authenticated verifier features (saved checks, bulk verification)**. *Why:* post-MVP depth with no requesting school behind it.

---

## 3. User Stories / Use Cases

Anonymous journeys are verified through the browser; there is no account to act with, so the page itself is the subject under test.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-QR-001 | Employer scans the QR code on a paper certificate and learns whether it is genuine and current | P0 | B | Planned |
| UC-QR-002 | Student shares the verification link with an employer who opens it without an account | P1 | — | — |
| UC-QR-003 | Employer checks a forged or mistyped link and receives a clean not-found answer that reveals nothing | P0 | B | Planned |

### 3.1 Anonymous Verification Journeys

#### UC-QR-001 — Employer Scans a Certificate at a Job Fair

At a regional job fair, an HR officer collects forty PKL certificates by noon and photographs the QR code on each. One of them belongs to a student who never completed placement — the paper looks perfect, same layout, same signatures. The scan resolves to NOT FOUND while the genuine ones resolve to ISSUED with matching names and dates, and the forgery is caught before an offer letter goes out. That afternoon is the entire justification for this page: the check has to work on a stranger's phone, over fairground mobile data, with zero onboarding.

#### UC-QR-002 — Student Shares a Link Instead of Paper

A graduate applying to a company in another province cannot hand over paper, so from the student portal they copy the verification link printed alongside their certificate and paste it into the application form. The employer's click lands on the same public page the QR code would have opened — same status, same metadata, no login wall in between. The link is shareable precisely because it carries no authority beyond readability: forwarding it to five more people changes nothing about what each of them can see.

#### UC-QR-003 — A Forged Link Fails Cleanly

Someone alters one character of a genuine verification URL, hoping to land on another student's record, and the page answers NOT FOUND with the same layout and the same response shape as any other miss. No hint distinguishes "this hash never existed" from "this hash is malformed", no timing difference worth measuring, no suggestion to try again with the certificate number. The edge that matters here is the near-miss: an attacker one character away from a valid hash must learn exactly as much as an attacker guessing at random, which is nothing.

---

## 4. Functional Requirements

Reads go through a Read Action; the controller stays thin and never queries the model directly. `Priority` ranks criticality; `Layer` declares the verifying test layer; `Status` tracks this requirement's implementation.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-QR-001 | `GET /verify/{qr_hash}` serves the verification page with no authentication middleware | P0 | F | Planned |
| FR-QR-002 | Lookup resolves through a Read Action by `qr_hash` and maps the result to a view model, never a raw model | P0 | F | Planned |
| FR-QR-003 | An ISSUED certificate renders status plus student name, school name, certificate number, and issuance date | P0 | F | Planned |
| FR-QR-004 | A REVOKED certificate renders revoked status with the revocation date and no success styling | P0 | F | Planned |
| FR-QR-005 | An unknown hash renders the not-found answer with identical shape and no existence hints | P0 | F | Planned |
| FR-QR-006 | Every issued certificate carries a QR code embedding the verification URL for its hash | P0 | F | Planned |
| FR-QR-007 | The page exposes no student data beyond holder name, school, and certificate metadata | P0 | A | Planned |
| FR-QR-008 | Malformed hashes are rejected by validation and answered as not-found, never as errors | P1 | F | Planned |
| FR-QR-009 | Verification lookups are logged with PII masking; unexpected failures render a generic page | P1 | F | Planned |

### 4.1 Public Verification Path

#### FR-QR-001 — Open Route, No Login Wall

Earlier drafts flirted with a lightweight "verifier account" so the school could track who checks what. That idea died the first time anyone described the actual user: an HR officer who will check exactly one certificate, once, on a phone, and will abandon anything that asks for registration. The route therefore carries throttling but no authentication of any kind, and any future middleware added here must preserve anonymous access or come back through a spec amendment.

#### FR-QR-002 — Lookup Through a Read Action

When the request arrives, the controller does almost nothing: it hands the hash to a Read Action, receives a small view model (status plus display fields), and passes it to the Blade view. The query itself, the status mapping, and the decision of what a stranger may see all live in the action, which means the page cannot accidentally gain a second query path when someone adds "just one more lookup" to the controller. Reads stay lock-free and log-free by construction, so a morning rush of verification traffic never contends with enrollment writes.

#### FR-QR-003 — What ISSUED Shows

The issued answer is deliberately boring: a clear issued marker, the holder's name as printed, the school name, the certificate number, and the issuance date. Each field exists because an employer cross-checks it against the paper in hand — a name mismatch is how the job-fair forgery in UC-QR-001 gets caught even when the status itself reads ISSUED. Anything the employer cannot cross-check against paper (grades, placement company, supervisor names) stays out; the page is a comparator, not a transcript.

#### FR-QR-004 — Revocation Must Read as a Warning, Never as Fine Print

The failure this requirement exists to prevent is a revoked certificate rendering with a gentle gray note that a hurried HR officer skims past. Revoked status renders unmissable — warning styling, explicit wording, the revocation date beside it — because the entire cost of the revocation machinery in [certification](J0M04-certification.md) is wasted if the public answer soft-pedals it. Success styling, celebratory colors, and congratulatory copy are all forbidden on this branch, and the template enforces that by using a separate visual treatment rather than a parameter.

#### FR-QR-005 — One Shape for Every Miss

Whether the hash never existed, belongs to a deleted draft, or was mangled in transit, the visitor sees the same not-found answer: same layout, same fields absent, same wording. The reason is adversarial rather than aesthetic — any variation between "invalid format" and "valid format, unknown record" becomes an oracle that tells an enumerator when their guesses are well-formed. Uniformity here is a security property disguised as a design choice.

### 4.2 Issuance Contract and Integrity

#### FR-QR-006 — The QR Code Is Printed Once and Must Keep Working

Long before this page existed, issuance started embedding verification URLs into printed certificates, and paper does not get firmware updates. The URL format is therefore frozen as a contract owned at issuance: the hash is generated when the certificate is issued, the QR encodes the full verification URL, and this page honors every URL that contract ever produced. A future URL redesign must keep the old shape resolving, because the cost of breaking it is measured in reprinted certificates across every graduating class.

#### FR-QR-007 — The Page Knows Less Than It Could

The certificate record is joined to registrations, users, placements, and assessments — the view layer could display all of it with one eager load. It must not. If this row is violated, the failure mode is not a crash but a quiet privacy breach: grades or placement histories readable by anyone with a link, indexed by scrapers, screenshot into group chats. The allowed field set is closed — holder name, school, certificate number, issuance and revocation dates — and anything beyond it requires a spec amendment, not a template edit.

#### FR-QR-008 — Garbage Hashes Get Manners, Not Stack Traces

A hash containing slashes, null bytes, or three hundred characters is not a lookup, it is an attack probe or a broken copy-paste, and it is validated before any query runs. The violation surfaces as a `RejectedException` carrying a translatable message, and the visitor sees the standard not-found page, identical to a well-formed miss. What must never happen is the framework's default error rendering — a 500 page, let alone an exception message — because on a public page every error detail is reconnaissance.

#### FR-QR-009 — Quiet Logging for a Loud Page

The page itself shows nothing about being watched, but every lookup writes a masked activity entry: timestamp, outcome class, and request metadata with names and hashes redacted. When a school later asks why one certificate was checked four hundred times in a night, that trail is the answer — and because PII never reaches the log, the trail itself cannot become a second disclosure. Unexpected failures (database unreachable, view miscompiled) render the same generic page visitors always see while the full context lands in the system log for operators.

---

## 5. Non-Functional Requirements

`Target` is the concrete SLO. Architectural targets read `N/A` and are verified via scans/tests rather than measurement.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-QR-001 | Anonymous verification requests are throttled per IP so hashes cannot be enumerated | 5/min/IP, HTTP 429 beyond | P0 | F | Planned |
| NFR-QR-002 | No personally identifying data beyond the holder's name reaches the page or its logs | 0 unmasked PII fields | P0 | F | Planned |
| NFR-QR-003 | Every user-facing string on the page renders through the translation helper in both locales | 0 hardcoded strings | P1 | A | Planned |
| NFR-QR-004 | Status is conveyed by icon plus wording, never by color alone | AA contrast + non-color cue | P1 | B | Planned |
| NFR-QR-005 | Verification responses are never cached by browsers or shared caches | `no-store` on all outcomes | P0 | F | Planned |

### 5.1 Abuse Resistance and Disclosure

#### NFR-QR-001 — Throttling Against the Patient Guesser

Somebody will eventually point a script at this page and walk the hash space. Throttling does not make that impossible — nothing can, on a public page — but it makes it pointless: a handful of attempts per minute per address turns an enumeration campaign into a geological process, while a human tapping one link never notices the limit. The hashes themselves carry the real weight here, drawn from a space no script can meaningfully dent; the throttle exists to make sure nobody gets to find out cheaply.

#### NFR-QR-002 — The Public Page as the Worst Place for a Leak

A logged-in student portal that shows one extra field is a bug; a public page that shows one extra field is an incident, because the audience is literally everyone. Holder name is the single permitted identity disclosure — it is what the employer is verifying — and everything adjacent (contact details, birth dates, grades, log excerpts) must be absent from the HTML, the JSON-LD, the meta tags, and the logs. Review treats any new field on this page as guilty until proven necessary.

#### NFR-QR-003 — The Bilingual Verifier

A hiring manager in Jakarta reads Indonesian; a crewing agent placing graduates on foreign-flagged vessels reads English. Both meet the same page, and both must understand the difference between ISSUED and REVOKED without ambiguity, because a mistranslated revocation is a wrongful hire or a wrongful rejection. Every string therefore passes through the translation helper with mirrored keys in both locale files, and dynamic values arrive as placeholders rather than string surgery.

### 5.2 Presentation Integrity

#### NFR-QR-004 — Status a Color-Blind Stranger Cannot Misread

Roughly one in twelve men cannot distinguish the red-green pairing this page would naturally reach for, and the person checking the certificate is a stranger the school will never get to brief. Status is therefore carried twice: an icon and explicit wording alongside whatever color the theme applies, with contrast meeting the same bar as the rest of the application. The employer who misreads a revocation as an issuance because both were "a colored badge" is the failure this row retires.

#### NFR-QR-005 — A Revocation Must Not Die in a Cache

Picture the sequence: a certificate is revoked at 10:00 after fraud is confirmed, but an employer who opened the verification link at 09:55 re-opens it from browser cache at 11:00 and sees ISSUED. Every verification response therefore carries headers forbidding storage at every layer, and the lookup itself bypasses any read cache the application might otherwise apply. Freshness here is not performance tuning — it is the difference between the revocation machinery working and theater.

---

## 6. API / Data Contracts

### Route

```php
// routes/web/public.php (no auth middleware)
Route::get('/verify/{qr_hash}', VerifyCertificateController::class)
    ->name('certificates.verify')
    ->where('qr_hash', '[A-Za-z0-9]{32,64}')
    ->middleware('throttle:5,1');
```

### Read Action

```php
final class ReadCertificateStatusAction extends BaseReadAction
{
    public function execute(string $qrHash): CertificateStatusView;
}
```

The action validates the hash shape, loads the certificate with its registration bridge, maps the status enum to the public answer (`issued`, `revoked`, `not_found`), and returns only the display fields FR-QR-007 permits. Unknown or malformed input yields the not-found view model rather than an exception the web layer must interpret.

### Controller (thin)

```php
final class VerifyCertificateController extends BaseController
{
    public function __invoke(string $qr_hash, ReadCertificateStatusAction $action): View
    {
        return view('certificates.verify', [
            'verification' => $action->execute($qr_hash),
        ]);
    }
}
```

### View Model Shape

| Field | Type | Present when |
|-------|------|--------------|
| `status` | `issued` / `revoked` / `not_found` | always |
| `studentName` | string | issued, revoked |
| `schoolName` | string | issued, revoked |
| `certificateNumber` | string | issued, revoked |
| `issuedAt` | date | issued, revoked |
| `revokedAt` | date | revoked only |

### QR URL Contract (owned at issuance)

```
https://{school-domain}/verify/{qr_hash}
```

The hash is a cryptographically random token stored alongside the certificate at issuance; it is not derived from the certificate number and cannot be recomputed from visible fields.

---

## 7. Design Decisions

Decisions are recorded reasoning, not test rows; `Layer` / `Status` stay `—`.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-QR-001 | The QR code carries a random hash token, not the human-readable certificate number | P0 | — | — |
| DD-QR-002 | The page is public by design; enumeration risk is carried by hash entropy plus throttling | P0 | — | — |
| DD-QR-003 | Lookup logic lives in a Read Action, not in the controller | P0 | — | — |

### 7.1 Token and Access Design

#### DD-QR-001 — Hash Token Instead of the Certificate Number

Certificate numbers are human artifacts: sequential, guessable, meaningful to administrators and meaningless to QR scanners. The hash is the opposite — compact in a QR matrix, format-agnostic, and drawn from a space where the next valid token cannot be computed from the previous one. Both appear on the printed certificate, serving different readers: the number for the administrator on the phone, the hash for the camera lens.

#### DD-QR-002 — Public on Purpose

Every proposal to gate this page — a verifier code, an employer registry, a CAPTCHA maze — trades away the one property that makes verification happen at all: zero friction for a stranger. The exposure this creates is real but bounded — the page reveals only what the paper already shows — and it is fenced by two independent mechanisms, unpredictable tokens and per-address throttling, so that no single failure opens enumeration.

#### DD-QR-003 — An Action, Not a Controller Query

The fastest implementation queries the model straight from the controller, and it works right up until the second lookup requirement arrives — PII masking, then status mapping, then the not-found uniformity rule — at which point the controller owns business logic it cannot unit-test without HTTP. Placing the lookup in a Read Action from the start keeps the controller at three lines, gives the status mapping millisecond tests with no database ceremony, and leaves the door open for the verification page to gain behavior without growing a second brain.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Forgery detection | Every forged sample in a test set resolves NOT FOUND | Seeded forgery drill per release |
| Revocation freshness | Revoked status visible on next request after revocation | Manual revoke-then-verify check |
| Throttle enforcement | Bursts beyond the window receive HTTP 429 | Automated burst test |
| Disclosure audit | No fields beyond the closed set in page source | Review checklist per change |
| Translation completeness | Zero hardcoded strings on the page | Locale scan (D3) |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [certification](J0M04-certification.md) | `Certificate` model, `qr_hash` generation, status enum, revocation flow |

### Build Guide

Build after certification issuance is complete: the hash column and QR embedding must exist before there is anything to verify. Keep the page read-only throughout — any write requirement discovered here (analytics, verifier feedback) belongs in a follow-up spec, not smuggled into this one.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [certification](J0M04-certification.md) | Issuance embeds the verification URL this page resolves |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| R-1 | If a certificate hash leaks into server logs or screenshots at scale, possession of the link equals verification — revocation remains the only remedy | Accepted | Maintainer | — |
| A-1 | We assume hashes are generated with cryptographic randomness at issuance, making enumeration infeasible within the throttle envelope | Accepted | Maintainer | — |

---

## Quick References

- [Certification](J0M04-certification.md) — issuance, QR hash generation, status enum, revocation
- [RBAC & authorization](T4B26-rbac-and-authorization.md) — dual-layer authorization (issuance side)
- [Logging & error handling](89SRA-logging-and-error-handling.md) — masked logging, rejection contract
- [Branding, theme & locale](52O1I-branding-theme-locale.md) — bilingual strings, school name resolution
- [Job & queue infrastructure](8FVZA-job-queue-infrastructure.md) — throttle and queue primitives
- [Spec registry](index.md) — Certification phase ordering
