# Module Catalog: Internara Ecosystem

This document provides a comprehensive and authoritative list of the modules that constitute the
Internara system. Each module is designed to adhere to the principles of domain isolation and
cross-module zero-coupling.

> **Governance Mandate:** All listed modules must comply with the
> **[Coding Conventions](../developers/conventions.md)**. Any violation of domain isolation
> (such as cross-domain Model calls) is considered an architectural defect.

---

## 1. Public Modules (Infrastructure & Foundational)

These modules provide the technical backbone and shared services used by the entire ecosystem.

| Name | Scopes | Purpose |
| :--- | :--- | :--- |
| **Shared** | Infrastructure | Provides business-agnostic technical infrastructure (UUIDs, `EloquentQuery`, universal concerns). |
| **Core** | Technical Foundation | Acts as the system "glue," handling localization middleware, custom Artisan generators, and cross-domain analytics. |
| **Support** | Business Utility | Provides business-aware administrative utilities, such as high-scale CSV onboarding and mass operations. |
| **Exception** | Error Handling | Manages standardized, localized, and secure fault management across all layers. |
| **Status** | State Management | Provides the foundational infrastructure for tracking and auditing entity lifecycle transitions. |
| **UI** | Design System | Implements the headless Design System, standardized components, and the *Slot Injection* mechanism. |
| **Auth** | Authentication | Handles secure entry points, session management, and role-based environment redirection. |
| **Permission** | RBAC | Manages the Role-Based Access Control (RBAC) engine and granular capability mapping. |
| **Setting** | Configuration | Centralizes database-backed, cached dynamic application configurations modified at runtime. |
| **Notification** | Messaging | Provides a unified infrastructure for real-time UI alerts and multi-channel system notifications. |
| **Media** | File Management | Manages secure file storage, image processing, and media attachments for domain entities. |
| **Log** | Audit Trail | Records user activity audit trails and forensic system logs with automated PII masking. |
| **Setup** | Installation | Orchestrates automated environment initialization, system auditing, and the Installation Wizard. |

## 2. Domain Modules (Business Logic)

These modules execute the specific business rules and workflows of the internship management process.

| Name | Scopes | Purpose |
| :--- | :--- | :--- |
| **User** | Identity | Manages authoritative stakeholder identities and account lifecycles. |
| **Profile** | Personal Data | Handles extended personal data, contact information, and role-based model associations. |
| **School** | Institutional | Manages educational institution identity, institutional data, and visual branding. |
| **Department** | Academic Structure | Manages academic specializations and organizational grouping within the institution. |
| **Student** | Student Workspace | Dedicated environment for students to track their internship progress and self-service tasks. |
| **Teacher** | Supervision | Dedicated workspace for academic supervisors to monitor progress and evaluate assigned students. |
| **Mentor** | Industry Feedback | Streamlined interface for industry mentors to track attendance and submit student assessments. |
| **Internship** | Orchestration | Manages the internship lifecycle, including placement availability and student registration. |
| **Attendance** | Time Tracking | Manages student presence tracking, absence requests, and real-time monitoring. |
| **Journal** | Activity Logging | Documents daily student activities, competency mapping, and dual supervision verification. |
| **Assignment** | Task Management | Manages the dynamic assignment engine for mandatory tasks and institutional policy submissions. |
| **Schedule** | Timeline | Manages institutional milestones, events, and vertical student journey visualization. |
| **Guidance** | Onboarding | Manages official handbooks and digital readiness verification through acknowledgement loops. |
| **Assessment** | Evaluation | Central engine for performance evaluation, automated compliance scoring, and credentialing. |
| **Report** | Data Export | Reporting engine for generating authoritative institutional records in PDF and Excel formats. |
| **Admin** | System Monitoring | Central command center for administrators to orchestrate settings and monitor system-wide activity. |

---

_Internara documentation follows **Doc-as-Code** principles. Every module's role is traceable to its internal **README.md**._