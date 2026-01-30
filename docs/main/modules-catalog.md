# Module Catalog: Internara Ecosystem

This document provides a comprehensive list of the modules that constitute the Internara system.
Each module is designed to adhere to the principles of domain isolation and cross-module
zero-coupling.

## 1. Public Modules (Infrastructure & Foundational)

| Name             | Scopes                        | Purposes                                                                                                            |
| :--------------- | :---------------------------- | :------------------------------------------------------------------------------------------------------------------ |
| **Shared**       | Infrastructure                | Provides basic technical infrastructure such as UUIDs, base service `EloquentQuery`, and universal model concerns.  |
| **Core**         | Technical Foundation          | Acts as the system "glue," handling localization middleware, custom Artisan generators, and cross-domain analytics. |
| **Support**      | Business Utility              | Provides business-aware utilities for mass operations, such as CSV onboarding.                                      |
| **Exception**    | Error Handling                | Manages standardized error handling (Fault Tolerance) and localized error message mapping.                          |
| **Status**       | State Management              | Provides the foundational infrastructure for tracking and managing entity state transitions.                        |
| **UI**           | Design System                 | Implements the Design System, standard Blade/Livewire components, and the _Slot Injection_ mechanism.               |
| **Auth**         | Authentication, Authorization | Handles authentication protocols, user session management, and primary account registration.                        |
| **Permission**   | RBAC, Access Control          | Manages Role-Based Access Control (RBAC) and permission mapping for system actions.                                 |
| **Setting**      | Dynamic Configuration         | Centralizes dynamic application configuration management that can be modified at runtime.                           |
| **Notification** | Messaging                     | Provides message delivery infrastructure via database (web-notify) and external channels.                           |
| **Media**        | File Management               | Manages file storage, image processing, and media attachments for model entities.                                   |
| **Log**          | Audit Trail                   | Records user activity audit trails and entity status changes for forensic purposes.                                 |
| **Setup**        | Installation                  | Provides an Installation Wizard for environment initialization and system configuration.                            |

## 2. Domain Modules (Business Logic)

| Name           | Scopes                | Purposes                                                                               |
| :------------- | :-------------------- | :------------------------------------------------------------------------------------- |
| **User**       | User Management       | Manages system user identities and account lifecycles.                                 |
| **Profile**    | Personal Data         | Handles deep personal data management, contact information, and role synchronization.  |
| **School**     | Institutional Data    | Manages educational institution identity information and visual branding.              |
| **Department** | Academic Structure    | Manages department or major data within the institution.                               |
| **Student**    | Student Workflow      | Specific business logic for students, including individual progress tracking.          |
| **Teacher**    | Supervision Workflow  | Specific dashboards and functionality for instructors and academic supervisors.        |
| **Mentor**     | Industry Feedback     | Assessment interface and logic for supervisors from industry partners.                 |
| **Internship** | Program Orchestration | Core domain; manages internship programs, placement quotas, and student registration.  |
| **Attendance** | Time Tracking         | Records daily student attendance at internship locations (Check-in/Check-out).         |
| **Journal**    | Activity Logging      | Documents daily student activities, learning reflections, and supervisor verification. |
| **Assignment** | Task Management       | Manages mandatory tasks and institutional policy document submissions.                 |
| **Assessment** | Competency Evaluation | Calculates final grades, manages competency rubrics, and issues digital certificates.  |
| **Report**     | Data Export           | Reporting engine for exporting administrative data to document formats (PDF/Excel).    |
| **Admin**      | System Monitoring     | Central orchestration dashboard for administrative staff and system oversight.         |

---

**Architectural Note:** All listed modules must comply with the
**[Conventions and Rules](../internal/conventions-and-rules.md)**. Any violation of domain isolation
(such as cross-domain Model calls) is considered an architectural defect.
