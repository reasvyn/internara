# Blueprint: Dual-Supervision Journals (BP-OPR-F403)

**Blueprint ID**: `BP-OPR-F403` | **Requirement ID**: `SYRS-F-403` | **Scope**: Monitoring & Vocational Telemetry

---

## 1. Context & Strategic Intent

This blueprint defines the dual-verification protocol for daily journals. It ensures that student activities are validated by both industry and academic supervisors to guarantee high-fidelity logging.

---

## 2. Technical Implementation

### 2.1 Verification Workflow (S1 - Secure)
- **Multi-Party Approval**: A journal entry MUST be verified by both the **Field Mentor** and the **Academic Teacher** before being used for final scoring.
- **Edit Lock**: Approved journals are immutable to prevent retroactive manipulation.

---

## 3. Verification & Validation - TDD Strategy (3S Aligned)

### 3.1 Secure (S1) - Boundary & Integrity Protection
- **Feature (`Feature/`)**:
    - **Edit Lock Audit**: Verify that a student cannot update a journal after one supervisor has approved it.
    - **Window Enforcement**: Verify that submissions > 7 days after the activity are rejected.

### 3.2 Sustain (S2) - Maintainability & Semantic Clarity
- **Unit (`Unit/`)**:
    - **Metric Logic**: Test the `Responsiveness` calculation algorithm with varied submission/approval timestamps.
- **Architectural (`arch/`)**:
    - **Standards**: Verify `JournalEntry` uses hooks for `word_count` and `is_verified`.

### 3.3 Scalable (S3) - Structural Modularity & Performance
- **Architectural (`arch/`)**:
    - **Independence**: Ensure `Journal` module remains isolated from `Assessment` scoring logic.
- **Unit (`Unit/`)**:
    - **Query Efficiency**: Verify `getEngagementStats()` uses `cursor()` for large datasets.

---

## 4. Documentation Strategy
- **Student Guide**: Update `docs/wiki/daily-monitoring.md` to explain the daily journal submission process and the 7-day window.
- **Supervisor Guide**: Update `docs/wiki/daily-monitoring.md` on how to verify and approve student journal entries.
- **Developer Guide**: Update `modules/Journal/README.md` to document the dual-supervision state machine and responsiveness scoring logic.
