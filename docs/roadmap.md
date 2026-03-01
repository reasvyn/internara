# Internara Engineering Roadmap: Requirement Mapping Matrix

This document serves as the authoritative traceability matrix between the **System Requirements
Specification (SyRS)** and all **Design Blueprints, Test Artifacts, and Architectural Decisions**.

It ensures end-to-end traceability from stakeholder intent to verifiable implementation.

---

# 1. Traceability Governance Model

All mappings must satisfy the following invariant:

```
Stakeholder Requirement (StRS)
        ↓
System Functional Requirement (SyRS-F)
        ↓
Non-Functional Constraint (SyRS-NF)
        ↓
Design Blueprint (BP-*)
        ↓
Test Artifact (TC-*)
        ↓
Validation Evidence (VNV-*)
```

No requirement may exist without:

- At least one Design Blueprint.
- At least one Test Artifact.
- A defined verification strategy.

---

# 2. System Core & Identity Mechanics (SyRS-F 100–200)

| Requirement ID | Requirement Name              | Design Blueprint | Scope | Mandatory NFR Linkage  |
| -------------- | ----------------------------- | ---------------- | ----- | ---------------------- |
| **SYRS-F-101** | Installation Wizard           | BP-SYS-F101      | `SYS` | NF-501, NF-503, NF-701 |
| **SYRS-F-102** | Setup Protection              | BP-SYS-F102      | `SYS` | NF-502, NF-504         |
| **SYRS-F-201** | Unified Profile               | BP-ID-F201       | `ID`  | NF-503, NF-504         |
| **SYRS-F-202** | Academic Scoping              | BP-ORG-F202      | `ORG` | NF-601                 |
| **SYRS-F-203** | Hierarchical Account Creation | BP-ID-F203       | `ID`  | NF-502                 |

---

# 3. Internship Lifecycle Management (SyRS-F 300)

| Requirement ID | Requirement Name        | Design Blueprint | Scope | Mandatory NFR Linkage |
| -------------- | ----------------------- | ---------------- | ----- | --------------------- |
| **SYRS-F-301** | Pre-Placement Checklist | BP-REG-F301      | `REG` | NF-601                |
| **SYRS-F-302** | Slot Atomic Integrity   | BP-PLC-F302      | `PLC` | NF-601, NF-701        |
| **SYRS-F-303** | Digital Guidance        | BP-OPR-F303      | `OPR` | NF-403                |
| **SYRS-F-304** | Task Management         | BP-OPR-F304      | `OPR` | NF-702                |

**Special Enforcement for SYRS-F-302:** Blueprint must explicitly document:

- Database unique composite constraint
- Transactional locking strategy
- Isolation level ≥ REPEATABLE READ

---

# 4. Monitoring & Vocational Telemetry (SyRS-F 400)

| Requirement ID | Requirement Name          | Design Blueprint | Scope | Mandatory NFR Linkage |
| -------------- | ------------------------- | ---------------- | ----- | --------------------- |
| **SYRS-F-401** | Temporal Presence         | BP-OPR-F401      | `OPR` | NF-401                |
| **SYRS-F-402** | Absence Orchestration     | BP-OPR-F402      | `OPR` | NF-502                |
| **SYRS-F-403** | Dual-Supervision Journals | BP-OPR-F403      | `OPR` | NF-502, NF-503        |
| **SYRS-F-404** | Forensic Evidence         | BP-OPR-F404      | `OPR` | NF-503                |
| **SYRS-F-405** | On-site Monitoring        | BP-OPR-F405      | `OPR` | NF-701                |

---

# 5. Assessment & Performance Synthesis (SyRS-F 500)

| Requirement ID | Requirement Name        | Design Blueprint | Scope  | Mandatory NFR Linkage |
| -------------- | ----------------------- | ---------------- | ------ | --------------------- |
| **SYRS-F-103** | Authoritative Reporting | BP-DOC-F103      | `DOC`  | NF-503                |
| **SYRS-F-501** | Rubric-Based Evaluation | BP-EVAL-F501     | `EVAL` | NF-702                |
| **SYRS-F-502** | Compliance Automation   | BP-EVAL-F502     | `EVAL` | NF-702                |
| **SYRS-F-503** | Readiness Auditing      | BP-EVAL-F503     | `EVAL` | NF-701                |
| **SYRS-F-504** | Visual Analytics        | BP-EVAL-F504     | `EVAL` | NF-401                |

---

# 6. Non-Functional Blueprint Mapping (SyRS-NF)

To ensure quantification and auditability as recommended in ISO/IEC 25010,
all SyRS-NF must have their own blueprint.

| NFR ID          | Blueprint Reference | Verification Artifact               |
| --------------- | ------------------- | ----------------------------------- |
| NF-401 – NF-405 | BP-UX-\*            | UI Review + Media Expert Validation |
| NF-501 – NF-504 | BP-SEC-\*           | Security Test Suite                 |
| NF-601 – NF-603 | BP-ARCH-\*          | Architecture Review                 |
| NF-701 – NF-703 | BP-QA-\*            | 3S Audit Checklist                  |

---

# 7. Verification & Validation Mapping (SyRS-V)

| Requirement ID | Blueprint  | Evidence Required           |
| -------------- | ---------- | --------------------------- |
| **SYRS-V-001** | BP-VNV-001 | Media Expert Report         |
| **SYRS-V-002** | BP-VNV-002 | Curriculum Compliance Audit |
| **SYRS-V-003** | BP-VNV-003 | User Acceptance Test Report |

---

# 8. Quantitative NFR Enforcement Matrix

| Category        | Enforcement Mechanism            | Evidence Type              |
| --------------- | -------------------------------- | -------------------------- |
| Performance     | Load Testing Report              | Performance Benchmark Log  |
| Reliability     | Backup & Recovery Simulation     | RTO/RPO Audit              |
| Maintainability | Static Analysis + Coverage ≥ 90% | CI Pipeline Report         |
| Security        | OWASP Scan + RBAC Audit          | Security Certification Log |

---

# 9. Cross-Module Architectural Controls

To preserve Modular Monolith integrity (NF-601):

- No cross-module concrete class references.
- Only public facade or explicitly exposed interfaces may be consumed.
- All business logic must reside in Service Layer.
- Cross-domain interaction must be event-driven or contract-based.

Architectural decisions must be documented in:

- `architecture.md`
- ADR repository
- Engineering Index

---

# 10. Compliance Invariants

The following invariants are non-negotiable:

1. No orphan requirement.
2. No blueprint without SyRS reference.
3. No implementation without blueprint.
4. No deployment without V&V evidence.
5. 3S Audit must pass prior to production release.

---

# 11. Architectural Evolution

System-wide governance documents:

- Architecture Description
- Engineering Index
- ADR Registry

All modifications must demonstrate backward traceability to SyRS.
