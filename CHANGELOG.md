# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [v0.9.0] - 2026-01-26 (ARC01-BOOT-01)

### 🚀 Overview

Focuses on **System Initialization**, delivering a secure, automated, and professional onboarding
experience for institutional administrators.

### ✨ Added

- **Secure Automated Installer:**
    - Implemented `app:install` Artisan command for single-command technical bootstrapping.
    - **Setup Token Security:** Introduced a CLI-generated authorization token system to prevent
      unauthorized web-based configuration.
- **Hardened Setup Wizard:**
    - Developed a comprehensive 7-step wizard: Welcome, Account, School, Department, Internship,
      System, and Complete.
    - **Self-Destruction Mechanism:** Implemented automated route lockdown and token purging once
      initialization is finalized.

### 🛠 Changed

- **Wizard Refinement:**
    - Reordered the setup sequence to ensure technical configuration (SMTP) occurs before
      finalization.
    - Standardized all `Setup` module services and commands to use **Constructor-based Dependency
      Injection**.
- **Test Infrastructure:**
    - Consolidated feature tests into a robust `SetupFlowTest.php`.
    - Enforced **Cross-Module Isolation** rules, forbidding direct model imports across domain
      boundaries.

---

## [v0.8.0] - 2026-01-25 (ARC01-INTEL-01)

### 🚀 Overview

Focuses on **Data Intelligence**, building tools to synthesize raw internship data into actionable
insights for institutions and industry partners.

### ✨ Added

- **Reporting Engine:** High-performance asynchronous PDF generation for certificates, transcripts,
  and engagement analytics.
- **Placement Lifecycle Tracking:** Automated history logging and audit trails for student
  placements.
- **Analytical Dashboard:** Cross-module data synthesis for "At-Risk" student identification and
  institutional metrics.

### 🛠 Changed

- **Core Engine Refinement:**
    - Standardized `EloquentQuery` usage across all service layers.
    - Unified multi-year data integrity via the `HasAcademicYear` shared concern.

### 🐛 Fixed

- **UUID Consistency:** Resolved inconsistent primary key handling by migrating all modules to the
  `HasUuid` concern.

---

## [v0.7.0] - 2026-01-21 (ARC01-ORCH-01)

### 🚀 Overview

Focuses on **Administrative Orchestration**, expanding beyond simple automation to include activity
monitoring and high-volume data management.

### ✨ Added

- **Requirement Engine:** Full lifecycle management for internship prerequisites and verification.
- **Participation-Driven Assessment:** Automated scoring based on real-time attendance and journal
  evidence.
- **Bulk Placement Engine:** Multi-select student-to-partner batch assignment capabilities.

---

## [v0.6.0] - 2026-01-19 (ARC01-FEAT-01)

### 🚀 Overview

Completes the foundational internship cycle by implementing role-based workspaces and professional
reporting.

### ✨ Added

- **RBAC Workspaces:** Specialized modules and dedicated dashboards for Admins, Students, Teachers,
  and Mentors.
- **Assessment Module:** Unified scoring system for academic and industry supervisors.
- **Artifact Verification:** Integrated QR-Code and Signed URL verification for institutional
  documents.

---

[v0.9.0]: https://github.com/reasnov/internara/compare/v0.8.0-alpha...v0.9.0-alpha
[v0.8.0]: https://github.com/reasnov/internara/compare/v0.7.0-alpha...v0.8.0-alpha
[v0.7.0]: https://github.com/reasnov/internara/compare/v0.6.0-alpha...v0.7.0-alpha
[v0.6.0]: https://github.com/reasnov/internara/compare/v0.5.0-alpha...v0.6.0-alpha
