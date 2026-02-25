# Module Catalog: Internara Ecosystem

This document provides a comprehensive and authoritative list of the modules that constitute the
Internara system. Each module is designed to adhere to the principles of domain isolation and
cross-module zero-coupling.

> **Governance Mandate:** All listed modules must comply with the
> **[Coding Conventions](../developers/conventions.md)**. Any violation of domain isolation (such as
> cross-domain Model calls) is considered an architectural defect.

---

## 1. Public Modules (Infrastructure & Foundational)

These modules provide the technical backbone and shared services used by the entire ecosystem.

| Name             | Scopes               | Purpose                                                                                                                     |
| :--------------- | :------------------- | :-------------------------------------------------------------------------------------------------------------------------- |
| **Shared**       | Infrastructure       | Provides business-agnostic technical infrastructure (UUIDs, `EloquentQuery`) and technical utilities (Formatting, Masking). |
| **Core**         | Technical Foundation | Acts as the system "glue," handling metadata SSoT (`MetadataService`), Academic Year scoping (`AcademicYearManager`), and global middleware.        |
| **Support**      | Business Utility     | Provides automated scaffolding (Artisan generators), testing orchestration, and modular development tools.                 |
| **Exception**    | Error Handling       | Manages standardized, localized, and secure fault management with automated abstraction for production safety.              |
| **Status**       | State Management     | Provides the foundational infrastructure and Enums for tracking auditable entity lifecycle transitions.                     |
| **UI**           | Design System        | Implements the headless Design System, standardized components, and the cross-module _Slot Injection_ mechanism.            |
| **Auth**         | Authentication       | Handles secure multi-identifier entry points, session management, and role-based environment redirection.                   |
| **Permission**   | RBAC                 | Manages the Role-Based Access Control (RBAC) engine and granular capability mapping via indexed UUIDs.                      |
| **Setting**      | Configuration        | Centralizes database-backed application configurations with SSoT metadata resolution.                                       |
| **Notification** | Messaging            | Provides a unified infrastructure for real-time UI alerts (Native Toasts) and multi-channel system notifications.           |
| **Media**        | File Management      | Manages secure file storage, image processing, and media collections via standardized model concerns.                       |
| **Log**          | Audit Trail          | Records activity audit trails and forensic system logs with recursive automated PII masking.                                |
| **Setup**        | Installation         | Orchestrates environment initialization, system auditing, Onboarding orchestration, and the Installation Wizard.           |

## 2. Domain Modules (Business Logic)

These modules execute the specific business rules and workflows of the internship management
process.

| Name           | Scopes             | Purpose                                                                                             |
| :------------- | :----------------- | :-------------------------------------------------------------------------------------------------- |
| **User**       | Identity           | Manages authoritative stakeholder identities and account lifecycles.                                |
| **Profile**    | Personal Data      | Handles extended personal data, contact information, and role-based model associations.             |
| **School**     | Institutional      | Manages educational institution identity, institutional data, and visual branding.                  |
| **Department** | Academic Structure | Manages academic specializations and organizational grouping within the institution.                |
| **Student**    | Student Workspace  | Dedicated environment for students to track their internship progress and self-service tasks.       |
| **Teacher**    | Supervision        | Dedicated workspace for academic supervisors to monitor progress and evaluate assigned students.    |
| **Mentor**     | Industry Feedback  | Streamlined interface for industry mentors to track attendance and submit student assessments.      |
| **Internship** | Orchestration      | Manages the internship lifecycle, including placement availability and student registration.        |
| **Attendance** | Time Tracking      | Manages student presence tracking, absence requests, and real-time monitoring.                      |
| **Journal**    | Activity Logging   | Documents daily student activities, competency mapping, and dual supervision verification.          |
| **Assignment** | Task Management    | Manages the dynamic assignment engine for mandatory tasks and institutional policy submissions.     |
| **Schedule**   | Timeline           | Manages institutional milestones, events, and vertical student journey visualization.               |
| **Guidance**   | Onboarding         | Manages official handbooks and digital readiness verification through acknowledgement loops.        |
| **Assessment** | Evaluation         | Central engine for performance evaluation, automated compliance scoring, and credentialing.         |
| **Report**     | Data Export        | Reporting engine for generating authoritative institutional records in PDF and Excel formats.       |
| **Admin**      | System Monitoring  | Central command center for administrators to orchestrate settings and monitor system-wide activity. |

---

_Internara documentation follows **Doc-as-Code** principles. Every module's role is traceable to its
internal **README.md**._
