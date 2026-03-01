# Problem Resolution Log (PRL): Defect Tracking

This document formalizes the **Problem Resolution Log (PRL)** for the Internara system, standardized according to **ISO/IEC 12207** (Problem resolution process).

---

## 1. Active Issues & Defects

| ID | Module | Severity | Description | Status |
| :--- | :--- | :--- | :--- | :--- |
| **PRL-001** | Internship | High | Race condition in slot allocation (Resolved via `lockForUpdate`). | CLOSED |
| **PRL-002** | Shared | Medium | UUID hydration failure in legacy environments. | CLOSED |
| **PRL-003** | UI | Low | Sidebar overflow on extra-small mobile devices. | OPEN |

---

## 2. Problem Identification & Classification

Problems are classified following the **[Incident Response Procedure](incident-response-procedure.md)** severity matrix.

### 2.1 Resolution Workflow
1.  **Detection**: Issue identified via V&V or user feedback.
2.  **Reproduction**: Creation of a failing Pest test case.
3.  **Correction**: Code modification ensuring the test passes.
4.  **Verification**: 3S Audit of the correction baseline.
5.  **Closure**: Log updated and issue closed.

---

## 3. Root Cause Analysis (RCA) Summary

For major defects (High/Critical), a summary of the root cause must be recorded here to prevent systemic recurrence.

- **Example (PRL-001)**: Lack of pessimistic locking allowed two students to claim the same placement slot simultaneously. Corrected by introducing `lockForUpdate` at the service transaction boundary.
