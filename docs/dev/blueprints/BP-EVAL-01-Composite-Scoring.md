# Blueprint: Composite Scoring & Rubric Assessment (BP-EVAL-01)

**Blueprint ID**: `BP-EVAL-01` | **Scope**: Intelligence & Delivery | **Compliance**: `ISO/IEC 12207`

---

## 1. Context & Strategic Intent

This blueprint defines the engine for student evaluation. It establishes the rules for how raw operational data (Journals, Attendance) is synthesized with formal supervisor rubric scoring to produce an authoritative academic grade.

---

## 2. The Evaluation Engine (S1 - Secure)

The `Assessment` module orchestrates the synthesis of multiple data points.

### 2.1 The Composite Score Invariant
The final grade is calculated using a weighted algorithm to ensure fairness and institutional standards.
- **Components**:
    1. **Participation (Automated)**: Derived from `Attendance` compliance and `Journal` submission volume.
    2. **Rubric Evaluation (Manual)**: Qualitative scores provided by the **Industry Mentor** and **Monitoring Teacher**.
- **Weights**: Configured at the Internship Program level (e.g., 20% Attendance, 30% Journal, 50% Rubric).
- **PEP Enforcement**: Every state-changing method (e.g., `evaluate`) MUST explicitly invoke **`Gate::authorize()`**.
- **Audit Trail**: Every evaluation change MUST be recorded in the `ActivityLog`.

### 2.2 Participation Telemetry (S3 - Scalable)
- The `ComplianceService` calculates the "Participation Score" based on the ratio of actual vs expected presence and logbook entries.
- **Performance**: High-volume telemetry retrieval MUST utilize memory-efficient patterns (`cursor()` or `chunk()`).

---

## 3. Rubric & Competency Framework (S2 - Sustain)

### 3.1 Rubric Strategy
- **Flexibility**: Rubrics are stored as **JSON** blobs linked to departments, allowing for dynamic curriculum changes without schema migrations.
- **Competency Mapping**: Claimed skills from student journals are displayed alongside rubric indicators to provide supervisors with forensic evidence during the scoring process.
- **PHP 8.4 Excellence**: Use **Property Hooks** for virtual score representations (e.g., `letter_grade`).

### 3.2 Asynchronous Side-Effects (S3)
- **Domain Event**: Upon assessment finalization, an `AssessmentFinalized` event is dispatched.
- **Consequence**: Triggers the `CertificateService` to queue document synthesis asynchronously.

---

## 4. Technical Contracts

### 4.1 `Modules\Assessment\Services\Contracts\AssessmentService`
- `evaluate(string $registrationId, array $marks)`: Records formal supervisor scores.
- `getFinalResult(string $registrationId)`: Synthesizes automated and manual scores into a **Readonly DTO**.

### 4.2 `Modules\Assessment\Services\Contracts\ComplianceService`
- `calculate(string $registrationId)`: Computes the real-time participation percentage.

---

## 5. Verification & Validation (V&V) - TDD Strategy

### 5.1 Scoring Engine Tests (S2 - Sustain)
- **Mathematical Weighted Verification**: 
    - **Scenario**: Create a test case with specific engagement metrics (e.g., 80% Attendance, 90% Journal) and manual rubric scores (e.g., 85/100).
    - **Assertion**: Verify that the calculated composite score matches the expected weighted average defined in the program config.
- **Participation Capping Audit**: Verify that if a student has >100% participation (overtime), the score is capped at 100.00 in the final calculation.

### 5.2 Rubric Integrity Tests
- **JSON Blob Schema Audit**: Verify that saving a rubric with an invalid JSON structure (e.g., missing mandatory criteria fields) triggers a validation error.
- **Forensic Consistency**: Verify that `getFinalResult()` returns a **Readonly DTO** containing both raw telemetry and aggregated scores.

### 5.3 Architectural Testing (`tests/Arch/AssessmentTest.php`)
- **Isolation**: Verify `Assessment` module does not query `attendance_logs` table directly (must use `ComplianceService` or `AttendanceService` contract).
- **Events**: Verify `AssessmentFinalized` is dispatched only when all components (manual + auto) are populated.

### 5.4 Quantitative & Performance
- **Complexity**: Ensure the scoring algorithm maintains Cyclomatic Complexity < 10.
- **Coverage**: 100% unit test coverage for the `WeightedCalculator` class.

_This blueprint records the current state of evaluation intelligence. Evolution of the scoring algorithms must be reflected here._
