# Blueprint: Forensic Journals & Geofenced Attendance (BP-OPR-02)

**Blueprint ID**: `BP-OPR-02` | **Scope**: Operational Telemetry | **Compliance**: `ISO/IEC 25010`

---

## 1. Context & Strategic Intent

This blueprint defines the mechanisms for high-fidelity progress tracking. It establishes the rules for "Forensic" logging, where student activities and presence are validated against temporal and spatial constraints to ensure institutional accountability.

---

## 2. The Forensic Journal Protocol (S1 - Secure)

### 2.1 Integrity & Immutability
- **Submission Window**: Students MUST submit journals within a configurable window (default: 7 days) from the activity date.
- **Edit Lock**: Once a journal is `approved`, it is immutable. Mutative attempts MUST trigger a `DomainException`.
- **Authorization**: Only assigned Teachers/Mentors (verified via **Policy Pattern 11.2.2**) can verify entries.
- **Evidence Protection**: Attachments MUST be accessed via **Signed URLs** to prevent unauthorized data exposure.

### 2.2 Constructing the Record (S2 - Sustain)
- **PHP 8.4 Excellence**: Use **Property Hooks** for virtual attributes (e.g., `word_count`, `has_attachments`).
- **i18n**: All journal feedback and status labels MUST resolve via translation keys.
- **Soft-Delete Invariant**: Deleting a journal MUST dispatch a `JournalArchived` event for cross-module synchronization.

---

## 3. Real-Time Attendance Telemetry (S3 - Scalable)

### 3.1 Validation Invariants
- **Temporal Guard**: Attendance cannot be recorded outside the internship start/end dates.
- **Zero-Trust**: Lat/Long metadata and network details MUST be validated at the Livewire component boundary.
- **Precision**: Use `DateTime` immutable DTOs for temporal checks.

---

## 4. Advanced Intelligence: The Engagement Engine (ISO/IEC 5055)

The `JournalService` implements specialized algorithms to calculate participation quality:

### 4.1 Responsiveness Metric
Calculates how quickly and consistently a student's logs are being approved.
- **Formula**: `(Approved Logs / Submitted Logs) * 100`.
- **Optimization**: The calculation is performed via optimized SQL `COUNT(CASE...)` queries to handle large datasets efficiently.
- **Performance**: Reporting services MUST utilize **`cursor()`** or **`chunk()`** when calculating compliance across large student cohorts.

---

## 5. Security & Privacy

### 5.1 Masking Invariants
PII within log descriptions or metadata is subject to automated masking before being written to system-level logs via the **`PiiMaskingProcessor`**.

---

## 6. Verification & Validation (V&V) - TDD Strategy

### 6.1 Telemetry Integrity Tests (S1 - Secure)
- **Edit Lock Audit**: 
    - **Scenario**: Use a supervisor account to `approve` a journal entry. Then, attempt to update the same entry via the student account.
    - **Assertion**: Must return `403 Forbidden` or throw a `DomainException`.
- **Window Enforcement Audit**: 
    - **Scenario**: Attempt to submit a journal for an activity date that is 8 days in the past (where window = 7).
    - **Assertion**: Must be rejected with a `journal::exceptions.submission_window_expired` message.
- **Signed URL Audit**: Test that accessing journal attachments without a valid signature returns a `403`.

### 6.2 Architectural Testing (`tests/Arch/TelemetryTest.php`)
- **Property Hooks Audit**: Ensure `JournalEntry` and `AttendanceLog` do not contain legacy `get...Attribute` methods.
- **Soft-Delete Invariant**: Verify that calling `delete()` on a journal dispatches the `JournalArchived` event.

### 6.3 Performance & Quantitative (S3 - Scalable)
- **N+1 Detection**: Run the attendance summary for 100 students and assert that the number of DB queries is less than 10.
- **Memory Efficiency**: Test `getEngagementStats()` with 10,000 journal records to ensure it completes within the PHP memory limit using `cursor()` patterns.

### 6.4 A11y (WCAG 2.1 AA)
- **Error Feedback**: Verify that form validation errors for Jurnal/Attendance are announced correctly by screen readers using ARIA live regions.
- **Keyboard Nav**: Verify that the one-click clock-in widget is fully keyboard-operable.

_This blueprint records the current state of operational telemetry. Evolution of the tracking algorithms must be reflected here._
