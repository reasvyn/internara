# Security Architecture (SA): Defensive Design & Protocols

This document defines the **Security Architecture (SA)** of the Internara system, standardized 
according to **ISO/IEC 27034** (Application security) and **ISO/IEC 29146** (Access control 
framework). It incorporates the authoritative governance standards for identity, authorization, and 
defensive logic.

---

## 1. Architectural Defense: The 3S Protocol

Internara is built on the **3S Doctrine** (Secure, Sustainable, Scalable). Security is not a feature 
but a systemic invariant.

### 1.1 Defense in Depth
Security is enforced at multiple layers:
1.  **Presentation Layer (PEP)**: Livewire validation and authentication checks.
2.  **Service Layer (PDP)**: Mandatory `Gate::authorize()` calls and ownership verification.
3.  **Persistence Layer (PIP)**: UUID-only identifiers and encrypted PII.

### 1.2 Authorization Philosophy: Least Privilege
Internara operates on the principle of **Least Privilege** and **Explicit Deny by Default**.
- **Least Privilege**: Users are granted only the minimum permissions necessary to execute their 
  assigned domain functions.
- **Explicit Deny**: Access is denied unless a specific permission or role assignment is verified.
- **Modular Sovereignty**: Authorization logic is encapsulated within the module that owns the 
  domain resource, utilizing framework-standard Policies.

---

## 2. Identity & Access Management (IAM)

Internara utilizes a strict **Role-Based Access Control (RBAC)** model adhering to **ISO/IEC 27001**.

### 2.1 Role Taxonomy & Traceability
Roles are defined in the `Core` module and mapped directly to the stakeholders identified in the 
System Requirements Specification (SyRS).

| Role | SSoT Stakeholder | Primary Domain Function |
| :--- | :--- | :--- |
| **Administrator** | System Admin | Full System Orchestration |
| **Instructor** | Instructor (Teacher) | Supervision, Mentoring, Assessment |
| **Staff** | Practical Work Staff | Administration, Scheduling, V&V |
| **Industry Supervisor** | Industry Supervisor | On-site Feedback, Mentoring |
| **Student** | Student | Journals, Attendance, Reporting |

### 2.2 Permission Specification (ISO/IEC 29146)
Permissions follow a strict **Module-Action** naming convention: `{module}.{action}` (e.g., 
`attendance.report`). Every permission must fulfill a specific functional requirement defined in the 
blueprints.

### 2.3 Implementation Layers
- **UI Layer**: Use `@can` for Blade and `$this->authorize()` for Livewire. Elements for 
  unauthorized actions must be suppressed.
- **Service Layer**: Services MUST verify authorization before performing state-altering operations 
  using `Gate::authorize()`.
- **Policy Pattern**: Every domain model must have a **Policy Class** for context-aware validation.

---

## 3. Policy Patterns: Authorization Governance Standards

This section formalizes standardized logic for **Policy Enforcement Points (PEP)** to ensure 
consistent security across all domain modules.

### 3.1 Standard Authorization Patterns
- **Permission-Based Ownership**: Verifies functional permission AND resource ownership (e.g., Student Journals).
- **Hierarchical Supervision**: Allows supervisors (Instructors/Mentors) to access subordinate 
  resources (e.g., student logs).
- **Administrative Override**: Super-Admins maintain universal bypass via `Gate::before()`.

### 3.2 Engineering Standards for Policies
- **Strict Typing**: All policy methods must declare strict types for the `User` subject and the 
  domain `Model`.
- **Semantic CRUD Mapping**: Methods must correspond to standard actions (`viewAny`, `view`, 
  `create`, `update`, `delete`, etc.).
- **Explicit Deny by Default**: Ambiguity is prohibited; unsatisfied conditions must return `false`.

---

## 4. Data Protection & Privacy

### 4.1 PII Protection (ISO/IEC 29100)
Personally Identifiable Information (PII) is protected through:
- **Encryption at Rest**: Sensitive fields utilize Eloquent's `encrypted` cast.
- **Logging Redaction**: PII is automatically redacted in all logging sinks.

### 4.2 Identity Obfuscation
The system uses **UUID v4** exclusively for all primary and foreign keys to prevent ID enumeration 
attacks.

---

## 5. Operational Security

- **Safe Defaults**: All routes and actions are "Deny by Default."
- **Audit Trails**: Every state-changing action is captured via `InteractsWithActivityLog`.
- **Baseline Synchronization**: Permissions are introduced through **Modular Seeders** and 
  synchronized via `php artisan permission:sync`.
