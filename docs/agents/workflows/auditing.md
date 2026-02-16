# Comprehensive Engineering Protocol: Security & Privacy Auditing

This document establishes the authoritative **Security & Privacy Auditing Protocol** for the
Internara project, adhering to **ISO/IEC 27001** (Information Security Management), **ISO/IEC
29100** (Privacy Framework), and **ISO/IEC 27034** (Application Security).

---

## ⚖️ Core Mandates & Prohibitions (The Auditing Laws)

The Agent must adhere to these invariants to ensure the system remains resilient and compliant.

### 1. Security Invariants

- **Assume Malicious Input**: Treat all external data (User input, API responses, Files) as
  untrusted until validated at the PEP (Policy Enforcement Point).
- **Zero Hardcoded Secrets**: Identification of API keys, passwords, or tokens in source code is a
  **Critical Audit Failure**.
- **Principle of Least Privilege**: Verify that modules and users have only the minimum necessary
  permissions to perform their functions.
- **Direct Evidence Mandate**: Audit findings MUST be based on observable code evidence, not
  hypothetical library vulnerabilities.

### 2. Privacy Invariants

- **PII Taint Analysis**: Trace "Privacy Sources" (Email, SSN, Address) to "Privacy Sinks" (Logs,
  Third-party APIs). Any unmasked or unencrypted flow is a violation.
- **Encryption at Rest**: Verify that sensitive fields utilize the `encrypted` cast in Eloquent
  models.
- **Redaction Mandate**: Ensure that PII is masked or redacted in all application logs (via the
  `Log` module).

---

## 🎯 Scope & Authorized Actions

### 1. Protocol Scope

- **SAST (Static Application Security Testing)**: Scanning source code for common vulnerabilities
  (SQLi, XSS, IDOR).
- **Privacy Compliance Audit**: Verifying adherence to PII protection and data minimization
  mandates.
- **Authorization Audit**: Verifying RBAC (Role-Based Access Control) integrity and Policy
  enforcement.

### 2. Authorized Actions

- **Security Scanning**: Utilizing read-only tools to identify vulnerabilities.
- **Taint Analysis**: Tracing data flow across modules to identify leaks.
- **Vulnerability Reporting**: Categorizing findings using the project's severity rubric (Critical,
  High, Medium, Low).
- **Remediation Suggestion**: Proposing code changes to fix identified flaws.

---

## Phase 0: Discovery & Reconnaissance (Immersion)

- **Baseline Review**: Re-read `docs/developers/security.md` and the security mandates in
  `specs.md`.
- **Architecture Mapping**: Identify critical domain boundaries and data entry points (Livewire
  components, API routes).

## Phase 1: SAST & Secret Scanning

- **Credential Check**: Scan for hardcoded secrets, keys, or passwords.
- **Injection Audit**: Identify unparameterized queries or raw HTML rendering
  (`dangerouslySetInnerHTML` equivalent in Blade/Livewire).
- **Path Traversal Audit**: Check for unsanitized file path constructions.

## Phase 2: Privacy Taint Analysis (PII Flow)

- **Source Identification**: Locate variables/columns containing PII (Email, Phone, Identity
  Numbers).
- **Sink Audit**: Verify that these sources do not flow into loggers or external APIs without
  masking/encryption.
- **Encryption Verification**: Ensure the `encrypted` cast is active on all identified PII fields in
  Models.

## Phase 3: Access Control & Authorization Audit

- **RBAC Integrity**: Verify that every domain action is protected by a **Policy** or **Gate**.
- **IDOR Check**: Ensure that object access (`/api/resource/{id}`) verifies ownership/authorization,
  not just existence.
- **Session Audit**: Check for secure session handling and predictable token generation.

## Phase 4: Vulnerability Reporting & Categorization

- **Severity Rubric**:
    - **Critical**: RCE, Full System Compromise, Auth Bypass.
    - **High**: Stored XSS, IDOR on critical data, SSRF.
    - **Medium**: Reflected XSS, PII in logs, Weak Crypto.
    - **Low**: Verbose error messages, Path traversal with limited scope.
- **Report Construction**: Document findings with Source Location, Sink Location, and Data Type.

## Phase 5: Remediation & SSoT Synchronization

- **Fix Proposal**: Provide code-level suggestions to resolve findings.
- **GitHub Triage**: Convert critical vulnerabilities into **GitHub Issues** (using private channels
  if possible) or link them to the **Security Milestone**.
- **Security Record Sync**: Update `SECURITY.md` or `docs/developers/debts/` if a vulnerability
  cannot be resolved immediately.
- **Audit Logging**: Record the completion of the audit cycle.

## Phase 6: Final Verification & Keypoints

- **Regression Check**: Ensure that remediation doesn't break existing functionality.
- **Keypoints Summary**: Final report of Findings, Severities, and Remediation status.

---

_Security is not a feature; it is the fundamental state of engineering integrity._
