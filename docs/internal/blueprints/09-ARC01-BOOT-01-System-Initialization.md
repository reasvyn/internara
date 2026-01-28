# Application Blueprint: System Initialization (ARC01-BOOT-01)

**Series Code**: `ARC01-BOOT-01` **Status**: `Completed`

> **Spec Alignment:** This blueprint implements the **Platform and Technology** requirements
> (Section 5) and **Administrative Management** (Section 1) of the
> **[Internara Specs](../../internara-specs.md)**. It focuses on reducing deployment friction.

---

## 1. Version Goals and Scopes (Core Problem Statement)

**Purpose**: Automate the system initialization process to ensure a reliable and fast deployment
experience.

**Objectives**:

- Eliminate manual configuration errors during setup.
- Provide a professional onboarding experience for institutional administrators.

**Scope**: Deploying Internara currently requires manual CLI steps (migrations, seeding, env
configuration) which is prone to human error. This version introduces automated tools to handle
these tasks.

---

## 2. Functional Specifications



**Feature Set**:



- **Automated Installer CLI**: A single command (`app:install`) to bootstrap the entire environment.

- **Web Setup Wizard**: A 8-step graphical interface for initial configuration with state persistence.

- **Pre-flight System Auditor**: Automated check for directory permissions, PHP extensions, and DB connectivity.

- **Setup Token Security**: CLI-generated authorization token to prevent unauthorized web access.



**User Stories**:



- As an **IT Staff**, I want the system to verify my server environment before I start configuration so that I don't encounter errors halfway through.

- As an **IT Staff**, I want to run a single command to install the system so that I don't miss critical setup steps.

- As a **Principal/Admin**, I want a web interface to configure my school's logo and name during the first run so that the system reflects our identity immediately.



---



## 3. Technical Architecture (Architectural Impact)



**Modules**:



- **Setup**: Dedicated module for installation and initialization logic.



**Data Layer**:



- **Seeding**: Orchestrated execution of Core and Shared seeders.

- **UUID Identity**: All created records utilize UUIDs.

- **State Persistence**: Current setup step is persisted to the `settings` table to allow resumption after disconnects.



**Security Logic**:



- **Middleware Protection**: `ProtectSetupRoute` and `RequireSetupAccess` strictly control wizard lifecycle.

- **One-Time Token**: 32-character random token required for initial web access, purged upon completion.

- **Self-Destruction**: Total lockdown of setup routes once `app_installed` is true and SuperAdmin exists.

- **Recovery Command**: `app:setup-reset` CLI command to bypass lockdown in case of catastrophic installation failure.



**Settings**:



- Integration with the `Setting` module to persist `brand_name`, `brand_logo`, and SMTP details.



---



## 4. UX/UI Design Specifications (UI/UX Strategy)



**Design Philosophy**: Minimalist, guided, and high-trust onboarding.



**Finalized User Flow**:



1. **Welcome & Language**: Introduction to Internara and locale selection (ID/EN).

2. **Environment**: Automated system check (Permissions, Extensions, DB).

3. **School**: Institutional identity configuration (Name, Logo).

4. **Account**: Initial Super-Administrator registration (Associated with School).

5. **Department**: Defining academic pathways.

6. **Internship**: Setting up management periods.

7. **System**: SMTP configuration with "Test Connection" and "Skip for Now" options.

8. **Complete**: Finalization and redirection to login.



**Mobile-First**:



- Setup Wizard is fully responsive, tested on multiple viewport sizes.



**Multi-Language**:

- Full support for **Indonesian** and **English** across all steps.

---

## 5. Success Metrics (KPIs)

- **Installation Velocity**: Reduced setup time from ~15 minutes to < 2 minutes.
- **Reliability**: Zero manual `.env` configuration errors required for standard installation.
- **Authorization**: 100% of web setup requests are authorized via CLI-generated tokens.

---

## 6. Quality Assurance (QA) Criteria (Exit Criteria)

**Acceptance Criteria**:

- [x] **Zero-Config Install**: `app:install` successfully bootstraps environment.
- [x] **Wizard Completion**: Institutional branding correctly persists to `setting()`.
- [x] **One-Time Use**: Setup module is inaccessible after completion.

**Testing Protocols**:

- [x] 100% Test Pass Rate for the `Setup` module (23 Tests).
- [x] **Feature Coverage**: End-to-end flow validated in `SetupFlowTest`.
- [x] **Unit Coverage**: Service logic validated in `SetupServiceTest` and `InstallerServiceTest`.

**Quality Gates**:

- [x] **Spec Verification**: Complies with SIM-PKL initialization requirements.
- [x] **Static Analysis**: Clean (`pint`, `lint`).
- [x] **Architecture**: Strict Modular Isolation maintained.

---

## 7. Implementation Summary

The system initialization flow has been successfully implemented with a focus on security, 
resilience, and architectural integrity.

- **Deep System Audit**: Implemented a dedicated `SystemAuditor` service that performs 
  comprehensive pre-flight checks, including PHP extensions, folder permissions, and database 
  connectivity.
- **8-Step Guided Wizard**: Refactored the onboarding flow to include a reactive Environment 
  Check and optimized the sequence to **School -> Account** for better data context.
- **State Persistence**: Migrated setup progress storage from volatile Sessions to the 
  permanent `Setting` module, ensuring users can resume the wizard after disconnects.
- **Hardened Security**: Implemented a one-time `setup_token` verified against the database 
  and enforced a total 404 lockdown of setup routes once installation is detected.
- **CLI Utilities**: Enhanced `app:install` with detailed audit feedback and added 
  `app:setup-reset` for emergency recovery.

---

## 8. vNext Roadmap (v0.10.0: Integrative Excellence & Competency Mastery)

- **Competency Mapping**: Linking journals to curriculum rubrics.
- **Mentoring Dialogue**: Formal feedback logs for Instructors/Mentors.
