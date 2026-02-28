# Blueprint: Compliance Automation (BP-EVAL-F502)

**Blueprint ID**: `BP-EVAL-F502` | **Requirement ID**: `SYRS-F-502` | **Scope**: Assessment & Performance Synthesis

---

## 1. Context & Strategic Intent

This blueprint defines the automated participation scoring engine. It ensures that student engagement, derived from real-time telemetry (Attendance and Journals), is automatically synthesized into a numerical score.

---

## 2. Technical Implementation

### 2.1 The Scoring Algorithm
- **Participation Telemetry**: The `ComplianceService` calculates scores based on the ratio of actual vs. expected presence and logbook submissions.
- **Weighted Synthesis**: Automated scores are combined with manual rubric marks based on program-level weight configurations (e.g., 20% Attendance, 30% Journal).

---

## 3. Verification & Validation - TDD Strategy (3S Aligned)

### 3.1 Secure (S1) - Boundary & Integrity Protection
- **Unit (`Unit/`)**:
    - **Participation Capping**: Verify that if a student has >100% participation (overtime), the score is capped at 100.00.
- **Feature (`Feature/`)**:
    - **Data Integrity**: Verify that the scoring engine cannot be triggered for registrations in `inactive` status.

### 3.2 Sustain (S2) - Maintainability & Semantic Clarity
- **Unit (`Unit/`)**:
    - **Mathematical Verification**: Create test cases with specific engagement metrics and verify that the calculated score matches the expected weighted average.
- **Architectural (`arch/`)**:
    - **Standard Compliance**: Ensure the scoring logic is encapsulated within a dedicated `WeightedCalculator` utility.

### 3.3 Scalable (S3) - Structural Modularity & Performance
- **Architectural (`arch/`)**:
    - **Isolation**: Verify the `Assessment` module does not query telemetry tables directly (must use Service Contracts).
- **Unit (`Unit/`)**:
    - **Memory Efficiency**: Verify retrieval of 10,000 telemetry records uses `cursor()` to maintain memory stability.

---

## 4. Documentation Strategy
- **Admin Guide**: Update `docs/wiki/assessment-and-evaluation.md` to explain the participation scoring algorithm and weighting configuration.
- **Developer Guide**: Update `modules/Assessment/README.md` to document the `ComplianceService` and its dependency on `Attendance` and `Journal` contracts.
- **Architecture**: Document the telemetry aggregation strategy in `docs/dev/architecture.md` (Process View).
