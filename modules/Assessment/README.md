# Assessment Module

The `Assessment` module is the central engine for evaluating student performance and generating
official credentials. It aggregates data from various sources to produce final grades.

> **Governance Mandate:** This module implements the requirements defined in the authoritative
> **[System Requirements Specification](../../docs/developers/specs.md)**. All implementation must
> adhere to the **[Coding Conventions](../../docs/developers/conventions.md)**.

---

## Purpose

- **Unified Grading:** Consolidates evaluations from **Industry Supervisors** and **Instructors**.
- **Automated Compliance:** Calculates participation scores from `Attendance` and `Journal` data.
- **Credentialing:** Generates verifiable certificates (Specs 1).

## Core Components

### 1. Models

- **Assessment:** Stores manual criteria and feedback (UUID identity).
- **Competency:** Master registry for departmental skills.
- **JournalCompetency:** Pivot mapping student journals to claimed skills.
- **ComplianceScore:** Automated score derived from student engagement.

### 2. Services

- **AssessmentService:** Manages manual evaluations from Instructors and Supervisors.
- **CompetencyService:** Orchestrates the registry of skills and their mapping to student
  activities.
- **ComplianceService:** Orchestrates the calculation of participation-driven scores via
  **Contracts**.
- **CertificateService:** Generates PDF documents with QR-code verification.

### 3. Key Features

- **Participation-Driven Assessment:** Automated logic mapping to student competency goals.
- **i18n Support:** All grading feedback and certificates must be fully localized (ID/EN).
- **Mobile-First:** Evaluation forms are optimized for on-site use by Industry Supervisors.

---

_The Assessment module transforms raw internship data into formal academic outcomes._
