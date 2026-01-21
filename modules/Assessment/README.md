# Assessment Module

The `Assessment` module is the central engine for evaluating student performance and generating
official credentials. It aggregates data from various sources (manual evaluations, automated
compliance checks) to produce a final grade and official certificates.

## Purpose

- **Unified Grading:** Consolidates evaluations from Industry Mentors and Academic Teachers.
- **Automated Compliance:** Calculates participation scores based on operational data (Attendance &
  Journal).
- **Credentialing:** Generates verifiable PDF Certificates and Transcripts.

## Core Components

### 1. Models

- **Assessment:** Stores manual evaluation criteria and qualitative feedback from supervisors.
- **ComplianceScore:** (Computed) Represents the automated score derived from attendance and journal
  completion.

### 2. Services

- **AssessmentService:** Manages the submission and retrieval of manual evaluations (`teacher` or
  `mentor`).
- **ComplianceService:** Orchestrates the calculation of participation-driven scores by querying the
  `Attendance` and `Journal` modules via contracts.
- **CertificateService:** Handles the generation of PDF documents with QR-code verification support.

### 3. Key Features

- **Participation-Driven Assessment:** Automated logic that calculates
  `(Attendance Days / Expected) * Weight + (Approved Journals / Expected) * Weight`.
- **Read-Only Integration:** Automations are injected as read-only metrics into supervisor
  evaluation forms to aid decision-making.
- **Public Verification:** Signed routes allow external parties to verify certificate authenticity
  via QR code.

## Technical Implementation

### Service Isolation

The `ComplianceService` strictly adheres to the **Modular Monolith** architecture by:

- Depending on `Modules\Attendance\Services\Contracts\AttendanceService`
- Depending on `Modules\Journal\Services\Contracts\JournalService`

This ensures that the Assessment module does not know about the internal implementation details of
the operational modules.
