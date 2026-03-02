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

| Requirement ID | Requirement Name              | Design Blueprint | Scope | Mandatory NFR Linkage    |
| -------------- | ----------------------------- | ---------------- | ----- | ------------------------ |
| **SYRS-F-101** | Installation Wizard           | BP-SYS-F101      | `SYS` | SYRS-NF-501, SYRS-NF-701 |
| **SYRS-F-102** | Setup Protection              | BP-SYS-F102      | `SYS` | SYRS-NF-502, SYRS-NF-504 |
| **SYRS-F-201** | Unified Profile               | BP-ID-F201       | `ID`  | SYRS-NF-503, SYRS-NF-504 |
| **SYRS-F-202** | Academic Scoping              | BP-ORG-F202      | `ORG` | SYRS-NF-601              |
| **SYRS-F-203** | Hierarchical Account Creation | BP-ID-F203       | `ID`  | SYRS-NF-502              |

---

# 3. Internship Lifecycle Management (SyRS-F 300)

| Requirement ID | Requirement Name        | Design Blueprint | Scope | Mandatory NFR Linkage    |
| -------------- | ----------------------- | ---------------- | ----- | ------------------------ |
| **SYRS-F-301** | Pre-Placement Checklist | BP-REG-F301      | `REG` | SYRS-NF-601              |
| **SYRS-F-302** | Slot Atomic Integrity   | BP-PLC-F302      | `PLC` | SYRS-NF-601, SYRS-NF-701 |
| **SYRS-F-303** | Digital Guidance        | BP-OPR-F303      | `OPR` | SYRS-NF-403              |
| **SYRS-F-304** | Task Management         | BP-OPR-F304      | `OPR` | SYRS-NF-702              |

**Special Enforcement for SYRS-F-302:** Blueprint must explicitly document:

- Database unique composite constraint
- Transactional locking strategy
- Isolation level ≥ REPEATABLE READ.

---

# 4. Monitoring & Vocational Telemetry (SyRS-F 400)

| Requirement ID | Requirement Name          | Design Blueprint | Scope | Mandatory NFR Linkage    |
| -------------- | ------------------------- | ---------------- | ----- | ------------------------ |
| **SYRS-F-401** | Temporal Presence         | BP-OPR-F401      | `OPR` | SYRS-NF-401              |
| **SYRS-F-402** | Absence Orchestration     | BP-OPR-F402      | `OPR` | SYRS-NF-502              |
| **SYRS-F-403** | Dual-Supervision Journals | BP-OPR-F403      | `OPR` | SYRS-NF-502, SYRS-NF-503 |
| **SYRS-F-404** | Forensic Evidence         | BP-OPR-F404      | `OPR` | SYRS-NF-503              |
| **SYRS-F-405** | On-site Monitoring        | BP-OPR-F405      | `OPR` | SYRS-NF-701              |

---

# 5. Assessment & Performance Synthesis (SyRS-F 500)

| Requirement ID | Requirement Name        | Design Blueprint | Scope  | Mandatory NFR Linkage |
| -------------- | ----------------------- | ---------------- | ------ | --------------------- |
| **SYRS-F-103** | Authoritative Reporting | BP-DOC-F103      | `DOC`  | SYRS-NF-503           |
| **SYRS-F-501** | Rubric-Based Evaluation | BP-EVAL-F501     | `EVAL` | SYRS-NF-702           |
| **SYRS-F-502** | Compliance Automation   | BP-EVAL-F502     | `EVAL` | SYRS-NF-702           |
| **SYRS-F-503** | Readiness Auditing      | BP-EVAL-F503     | `EVAL` | SYRS-NF-701           |
| **SYRS-F-504** | Visual Analytics        | BP-EVAL-F504     | `EVAL` | SYRS-NF-401           |

---

# 6. Scope, Constraints & Branding (SyRS-C)

| Requirement ID | Constraint Name       | Directive Document               | Implementation Context |
| -------------- | --------------------- | -------------------------------- | ---------------------- |
| **SYRS-C-001** | Functional Scope      | `software-requirements.md`       | Section 1 & 6          |
| **SYRS-C-002** | Multi-Language        | `user-interface-design.md`       | Section 4              |
| **SYRS-C-003** | Service Layer Logic   | `engineering-standards.md`       | Section 3              |
| **SYRS-C-004** | Branding Attribution  | `branding-and-identity.md`       | Dual-Identity Invariant|

---

# 7. Non-Functional Blueprint Mapping (SyRS-NF)

To ensure quantification and auditability as recommended in ISO/IEC 25010,
all SyRS-NF have their own dedicated architectural blueprint.

| NFR Domain       | Requirement ID    | Blueprint ID  | Verification Artifact               |
| ---------------- | ----------------- | ------------- | ----------------------------------- |
| **UI/UX**        | SYRS-NF-401 – 405 | `BP-UX-001`   | UI Review + Media Expert Validation |
| **Security**     | SYRS-NF-501 – 504 | `BP-SEC-001`  | Security Test Suite                 |
| **Architecture** | SYRS-NF-601 – 603 | `BP-ARCH-001` | Architecture Review                 |
| **Quality**      | SYRS-NF-701 – 703 | `BP-QA-001`   | 3S Audit Checklist                  |

---

# 8. Verification & Validation Mapping (SyRS-V)

| Requirement ID | Blueprint  | Evidence Required           |
| -------------- | ---------- | --------------------------- |
| **SYRS-V-001** | BP-VNV-001 | Media Expert Report         |
| **SYRS-V-002** | BP-VNV-002 | Curriculum Compliance Audit |
| **SYRS-V-003** | BP-VNV-003 | User Acceptance Test Report |

---

# 9. Quantitative NFR Enforcement Matrix

| Category        | Enforcement Mechanism            | Evidence Type              |
| --------------- | -------------------------------- | -------------------------- |
| Performance     | Load Testing Report              | Performance Benchmark Log  |
| Reliability     | Backup & Recovery Simulation     | RTO/RPO Audit              |
| Maintainability | Static Analysis + Coverage ≥ 90% | CI Pipeline Report         |
| Security        | OWASP Scan + RBAC Audit          | Security Certification Log |

---

# 10. Systemic Policy Enforcement (Sections 13–15)

| Requirement ID | Policy Domain              | Directive Document               | Control Mechanism       |
| -------------- | -------------------------- | -------------------------------- | ----------------------- |
| **Section 13** | Data Gov & Retention       | `software-requirements.md`       | DB Partition & Soft-Del |
| **Section 14** | Technology Review          | `version-control-management.md`  | Annual Compliance Pass  |
| **Section 15** | Atomic Integrity           | `detailed-design.md`             | Transactional Locking   |

---

# 11. Core Operational Manuals (Engineering Baseline)

| Manual Name                 | Directive Document                | Implementation Context     |
| --------------------------- | --------------------------------- | -------------------------- |
| **Modular Construction**    | `modular-construction-guide.md`   | Modular Invariants (SRP)   |
| **Development Tooling**     | `development-tooling.md`          | Life-Cycle Orchestration   |
| **Engineering Standards**   | `engineering-standards.md`        | 3S Doctrine Enforcement    |
| **Conflict Resolution**     | `conflict-resolution-policy.md`   | Architectural Decisioning  |

---

# 12. Cross-Module Architectural Controls

To preserve Modular Monolith integrity (SYRS-NF-601):

- No cross-module concrete class references.
- Only public facade or explicitly exposed interfaces may be consumed.
- All business logic must reside in Service Layer.
- Cross-domain interaction must be event-driven or contract-based.

Architectural decisions must be documented in:

- `system-architecture.md`
- ADR repository
- Engineering Index

---

# 13. Compliance Invariants

The following invariants are non-negotiable:

1. No orphan requirement.
2. No blueprint without SyRS reference.
3. No implementation without blueprint.
4. No deployment without V&V evidence.
5. 3S Audit must pass prior to production release.

---

# 14. Architectural Evolution

System-wide governance documents:

- Architecture Description (`system-architecture.md`)
- Engineering Standards (`engineering-standards.md`)
- ADR Registry

All modifications must demonstrate backward traceability to SyRS.
