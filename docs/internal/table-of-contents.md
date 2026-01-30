# Internal Engineering: System Documentation Index

This index centralizes the formal technical records for the Internara project, standardized
according to **ISO/IEC 12207** and **ISO/IEC 42010**. It defines the authoritative standards,
architectural views, and engineering processes used to build and maintain the system.

---

## 1. Authoritative Baselines & Architecture

- **[System Requirements Specification](system-requirements-specification.md)**: **Single Source of
  Truth (SSoT)** - Formal ISO/IEC 29148 requirements baseline.
- **[Architecture Description](architecture-description.md)**: Formal ISO/IEC 42010 deep-dive into
  structural views, invariants, and design rationale.
- **[Foundational Module Philosophy](foundational-module-philosophy.md)**: System decomposition
  strategy based on functional specialization and portability.
- **[Version Management](version-management.md)**: ISO 10007 standards for configuration baseline
  identification and lifecycle classification.

## 2. Engineering Standards & Conventions

- **[Conventions and Rules](conventions-and-rules.md)**: Construction standards for naming,
  namespaces, and domain layer invariants.
- **[Logic Orchestration Patterns](logic-orchestration-patterns.md)**: Advanced service
  orchestration, immutable DTO usage, and transactional integrity.
- **[Cross-Module Event Governance](cross-module-event-governance.md)**: Standards for decoupled
  asynchronous side-effects and event-driven orchestration.
- **[Data Integrity Protocols](data-integrity-orchestration-protocols.md)**: Maintenance of
  software-level referential integrity within modular boundaries.
- **[UI/UX Development Guide](ui-ux-development-guide.md)**: Human-centered design standards
  adhering to ISO 9241-210 and WCAG 2.1.
- **[Exception Handling Guidelines](exception-handling-guidelines.md)**: System reliability
  standards for secure fault tolerance and localized feedback.

## 3. Engineering Workflows & Design Baselines

- **[Feature Engineering Workflow](feature-engineering-workflow.md)**: ISO/IEC 12207 implementation
  processes and verification protocols.
- **[Software Life Cycle Processes](software-lifecycle-processes.md)**: Formal engineering framework
  from conceptualization to baseline retirement.
- **[Blueprint Design Guidelines](blueprints-guidelines.md)**: IEEE 1016 standards for the
  elicitation and formalization of design intent.
- **[Application Blueprints Index](blueprints/table-of-contents.md)**: Strategic design records
  bridging requirements and construction.
- **[Release Publication Protocols](release-publication-protocols.md)**: ISO/IEC 12207 baseline
  promotion and deployment standards.
- **[Repository Configuration Protocols](repository-configuration-protocols.md)**: ISO 10007
  standards for collaboration and baseline management.
- **[Automated Tooling Reference](automated-tooling-reference.md)**: ISO/IEC 12207 infrastructure
  and support tooling catalog.

## 4. Security & Quality Assurance

- **[Role & Permission Management](role-permission-management.md)**: Implementation of ISO/IEC 27001
  access control and modular RBAC.
- **[Policy Patterns](policy-patterns.md)**: Governance standards for policy enforcement points
  (PEP).
- **[Code Quality Standardization](code-quality-standardization.md)**: ISO/IEC 25010/5055 quality
  models and quantitative technical gates.
- **[Testing & Verification Guide](testing-verification-guide.md)**: IEEE 1012 and ISO/IEC 29119
  standards for V&V activities.

## 5. Advanced Engineering & Supporting Ecosystem

- **[Advanced Engineering Index](advanced/table-of-contents.md)**: Protocols for systemic evolution
  and specialized domain orchestrations.
- **[Supporting Ecosystem Index](packages/table-of-contents.md)**: Technical records for third-party
  baseline integration and orchestration.
- **[Technical Debt Index](debts/table-of-contents.md)**: Evolutionary governance of refactoring and
  architectural upgrades.

---

_This registry is reserved for engineering use only. Stakeholder-facing records can be found in the
**[Documentation Hub](../main/main-documentation-overview.md)**._
