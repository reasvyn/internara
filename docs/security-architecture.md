# Security Architecture (SA): Defensive Design & Protocols

This document defines the **Security Architecture (SA)** of the Internara system, standardized according to **ISO/IEC 27034** (Application security) and **ISO/IEC 29146** (Access control framework).

---

## 1. Architectural Defense: The 3S Protocol

Internara is built on the **3S Doctrine** (Secure, Sustainable, Scalable). Security is not a feature but a systemic invariant.

### 1.1 Defense in Depth
Security is enforced at multiple layers:
1.  **Presentation Layer (PEP)**: Livewire validation and authentication checks.
2.  **Service Layer (PDP)**: Mandatory `Gate::authorize()` calls and ownership verification.
3.  **Persistence Layer (PIP)**: UUID-only identifiers and encrypted PII.

---

## 2. Identity & Access Management (IAM)

Internara utilizes a strict **Role-Based Access Control (RBAC)** model.

### 2.1 Role Taxonomy
Roles are defined in the `Core` module and enforced via the `Permission` module.
- **Super-Admin**: Full system orchestration.
- **Instructor/Teacher**: Academic supervision and assessment.
- **Mentor**: Industrial feedback and mentoring.
- **Student**: Activity logging and progress tracking.

### 2.2 Authorization Policies
Every domain model is governed by a **Policy Class**.
- **Least Privilege**: Users are granted only the minimum permissions required.
- **Ownership Verification**: Policies must check if the subject owns the resource being accessed.

---

## 3. Data Protection & Privacy

### 3.1 PII Protection (ISO/IEC 29100)
Personally Identifiable Information (PII) is protected through:
- **Encryption at Rest**: Sensitive fields (e.g., NIK, email, phone) are encrypted using Eloquent's `encrypted` cast.
- **Logging Redaction**: Logging pipelines automatically redact PII to prevent exposure in logs.

### 3.2 Identity Obfuscation
The system uses **UUID v4** exclusively for all primary and foreign keys to prevent ID enumeration and scraping attacks.

---

## 4. Operational Security

- **Safe Defaults**: All routes and actions are "Deny by Default."
- **Audit Trails**: Every state-changing action is captured in the activity log with a full audit trail (subject, action, before/after).
- **Session Security**: Enforced CSRF protection, secure cookie flags, and session expiration.
