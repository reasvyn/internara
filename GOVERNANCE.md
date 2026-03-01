# Project Governance: Internara Open Source

This document formalizes the **Project Governance Framework** for Internara, ensuring transparency, accountability, and professional oversight in alignment with **ISO/IEC 38500** (Corporate governance of information technology).

---

## 1. Governance Model: Strategic Oversight

Internara follows a **Benevolent Dictatorship** model, where the Lead Maintainer has the final authority on architectural and strategic decisions to preserve the system's high-fidelity engineering standards (3S Doctrine).

### 1.1 Decision-Making Process

- **Architectural Decisions**: Must be documented via Architectural Decision Records (ADR) and align with the `docs/system-architecture.md`.
- **Feature Approval**: Requires alignment with the `docs/software-requirements.md` (SyRS).
- **Security Decisions**: Handled privately via the Lead Maintainer and Security Team as per `SECURITY.md`.

---

## 2. Roles & Responsibilities

| Role | Responsibility | Authority |
| :--- | :--- | :--- |
| **Lead Maintainer** | Strategic direction, final PR approval, and 3S Doctrine enforcement. | Final decision. |
| **Maintainers** | Code review, module ownership, and issue triage. | Reviewer & merger. |
| **Contributors** | Feature development, bug fixes, and documentation improvements. | Proposal & implementation. |

---

## 3. Contribution Gatekeeping

To maintain the system's integrity, all contributions must pass the following gates:

1.  **3S Audit**: Every PR is audited for Security, Sustainability, and Scalability.
2.  **TDD Compliance**: Every functional change must have >90% behavioral coverage.
3.  **Static Analysis**: Zero high-severity violations in PHPStan and Pint.
4.  **ISO-Alignment**: Documentation must evolve alongside code changes.

---

## 4. Communication Channels

Governance discussions occur in public via:
- GitHub Issues (Technical decisions)
- GitHub Discussions (Strategic planning)
- Official Support Channels (Operational feedback)

---

## 5. Conflict Resolution

Conflicts are resolved through technical consensus. If consensus is not reached, the Lead Maintainer provides the final authoritative decision based on the **3S Doctrine**.
