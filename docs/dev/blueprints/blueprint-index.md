# Feature-Based Blueprints: Evolution Records

This directory serves as the authoritative repository for **living Architecture Design Records (ADR)** and
strategic feature blueprints for the Internara project. 

Rather than describing abstract system "phases", these blueprints record the **evolution of specific end-to-end features**. They document how multiple modules interact to fulfill a specific operational workflow, acting as the Single Source of Truth (SSoT) for cross-module contracts, hidden invariants, and gating logic.

---

## 🏗️ 1. System Core & Identity Mechanics

Blueprints defining the technical baseline, identity lifecycle, and security perimeters.

- **[BP-SYS-01: Initialization & Technical Baseline](BP-SYS-01-Initialization.md)**
    - **Focus**: Setup orchestration, dynamic settings, module discovery, and fallback resilience.
    - **Modules**: `Setup`, `Setting`, `Core`, `Support`.
- **[BP-ID-01: Dual-Entity Identity & RBAC](BP-ID-01-Dual-Entity-Identity.md)**
    - **Focus**: The `User` vs `Profile` paradigm, UUID identities, session orchestration, and granular permissions.
    - **Modules**: `Auth`, `Permission`, `User`, `Profile`.

---

## 🏫 2. Institutional Architecture

Blueprints defining the structural boundaries and academic routing within the system.

- **[BP-ORG-01: Institutional & Departmental Sovereignty](BP-ORG-01-Institutional-Sovereignty.md)**
    - **Focus**: School branding fallbacks, department-based scoping, and academic year temporal isolation.
    - **Modules**: `School`, `Department`, `Core`, `Profile`.

---

## 💼 3. The Internship Lifecycle (Core Business)

Blueprints defining the complex orchestration of student placements and industry partnerships.

- **[BP-PLC-01: Partner Registry & Quota Management](BP-PLC-01-Partner-Quota.md)**
    - **Focus**: Company master data, batch opening, and slot allocation mechanisms.
    - **Modules**: `Internship`.
- **[BP-REG-01: Registration Gating & Placement Flow](BP-REG-01-Registration-Flow.md)**
    - **Focus**: Multi-step student registration, document prerequisite validation, and final placement allocation.
    - **Modules**: `Internship`, `Media`, `Status`.

---

## 📋 4. Operational Telemetry & Fulfillment

Blueprints detailing the daily execution logic, accountability, and the strict rules governing student activities.

- **[BP-OPR-01: The Guidance Gating Invariant](BP-OPR-01-Guidance-Gating.md)**
    - **Focus**: The mandatory sequence where policy acknowledgment unlocks operational features.
    - **Modules**: `Guidance`, `Journal`, `Attendance`.
- **[BP-OPR-02: Forensic Journals & Geofenced Attendance](BP-OPR-02-Forensic-Telemetry.md)**
    - **Focus**: Temporal/spatial validation, logbook immutability after supervisor verification, and PII masking.
    - **Modules**: `Journal`, `Attendance`, `Log`, `Media`.
- **[BP-OPR-03: Assignment Fulfillment Logic](BP-OPR-03-Assignment-Fulfillment.md)**
    - **Focus**: Dynamic task allocation and the prerequisite logic for program completion.
    - **Modules**: `Assignment`, `Internship`.

---

## 📊 5. Intelligence & Delivery

Blueprints defining how raw telemetry is aggregated into academic value and certifiable artifacts.

- **[BP-EVAL-01: Composite Scoring & Rubric Assessment](BP-EVAL-01-Composite-Scoring.md)**
    - **Focus**: Aggregating participation (Journal/Attendance) with formal rubric evaluations from supervisors.
    - **Modules**: `Assessment`, `Teacher`, `Mentor`.
- **[BP-DOC-01: Certificate Synthesis & Verification](BP-DOC-01-Certificate-Synthesis.md)**
    - **Focus**: PDF generation, QR-code signed URL encoding, and document immutability.
    - **Modules**: `Report`, `Assessment`.

---

## 📏 Blueprint Maintenance Rules

1.  **Living Documents**: Blueprints must be updated concurrently with the code during an *up-down-up* iteration.
2.  **Cross-Module Contracts**: Every blueprint must explicitly define which `Service Contracts` are used to communicate between its relevant modules.
3.  **No Status Labels**: Blueprints do not have "Draft" or "Completed" status. They represent the *current reality* of the implementation.