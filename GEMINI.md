# Internara — Project Memory

## Table of Contents

- [Architecture & Design](#architecture--design)
- [Governance & Standards](#governance--standards)
- [Module Index](#module-index)

## Architecture & Design

- **Architecture Model:** Modular Monolith (documented in
  [docs/architecture.md](docs/architecture.md)).
- **Primary Stack:** Laravel, Livewire, Tailwind CSS.
- **Dependency Injection:** Automated service registration via `BindServiceProvider` scanning module
  contracts.
- **Cross-Module Communication:** Strictly via service contracts; no direct model imports or
  cross-module foreign key constraints.

## Governance & Standards

- **Global Governance:** Adheres to the
  [ISO/IEC Standardized Operational Manual](/home/reasnovynt/.gemini/GEMINI.md).
- **Critical Mandates:**
    - **Attribute Sovereignty:** Always evaluate the compliance and sovereign context of data
      attributes before granting editing access. Critical system-level attributes (like SuperAdmin
      identity, system-generated IDs, or audit-critical fields) MUST remain immutable or
      system-controlled. (S1 Secure / ISO-IEC Compliance).
- **Development Workflow:** Strictly follows **Domain-driven Design (DDD) Modular** and Clean Code
  standards (see [docs/standards.md](docs/standards.md)).
- **Localization:** Zero tolerance for hardcoded strings; all UI text must use translation keys.

- **Module Index**:
    - **Identity & Access**: Auth, User, Profile, Permission, Admin.
    - **Academic**: Student, Teacher, Mentor, School, Department, Assessment, Assignment, Journal,
      Attendance, Schedule, Guidance.
    - **Operations & Infra**: Internship, Setup, Shared, Core, UI, Status, Exception, Report,
      Notification, Log, Setting, Media, Support.

- **Infrastructure Services**:
    - **Support**: `SystemInstaller`, `InstallationAuditor`.
    - **Setup**: `AppSetupService`.
    - **Shared**: `HandlesWizardSteps` (Wizard UI concern).
