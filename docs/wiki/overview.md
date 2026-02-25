# Project Overview: Internara Ecosystem

**Internara** is a modern, modular, and human-centered **Practical Work Management Information
System (SIM-PKL)**. It is designed to bridge the gap between educational institutions and industrial
partners through digital orchestration.

---

## 1. Strategic Vision

The core purpose of Internara is to eliminate the fragmentation of manual internship management. By
providing a centralized digital ecosystem, the system ensures:

- **Traceability**: Every student activity and competency achievement is documented and verified.
- **Efficiency**: Automated administrative workflows reduce the burden on instructors and staff.
- **Accountability**: Real-time monitoring allows for immediate intervention and guidance.

---

## 2. The Five Strategic Phases

To achieve this vision, Internara is built upon **Five Strategic Phases** that govern the 
entire operational lifecycle:

1.  **Phase 1: The WHO (Identity)**: Establishing secure accounts and persona profiles.
2.  **Phase 2: The WHERE (Institutional)**: Defining school hierarchy and departmental isolation.
3.  **Phase 3: The WHAT (Operational)**: Orchestrating programs, partners, and placements.
4.  **Phase 4: The HOW (Monitoring)**: Capturing attendance and forensic journal telemetry.
5.  **Phase 5: The RESULTS (Intelligence)**: Synthesizing evaluations into certified outcomes.

---

## 3. Key Stakeholders

Internara provides tailored environments for five primary roles:

1.  **Administrator**: System-wide configuration, user management, and institutional oversight.
2.  **Instructor (Teacher)**: Academic supervision, mentoring sessions, and competency assessment.
3.  **Student**: Daily journaling, attendance check-ins, and task fulfillment.
4.  **Industry Supervisor (Mentor)**: On-site feedback, logbook validation, and performance
    evaluation.
5.  **Practical Work Staff**: Scheduling, placement management, and administrative verification.

---

## 3. Core Capabilities

### ⚡ Operational Orchestration

Manages the entire internship lifecycle—from initial program creation and student registration to
final certification.

### 📊 Intelligence & Monitoring

Provides real-time dashboards and analytical reports to track attendance, journal compliance, and
learning outcomes.

### 📱 Mobile-First Experience

Optimized for smartphones to support students and mentors in the field, featuring quick check-ins
and easy document uploads.

---

## 4. Institutional Readiness (Step Zero)

Before proceeding with the installation, we recommend gathering the following foundational data to
ensure a smooth onboarding process:

- **Institutional Identity**: Official school name, address, logo, and legal identifiers (e.g.,
  NPSN).
- **Academic Structure**: A comprehensive list of active Departments or Study Programs.
- **Stakeholder Directory**: A spreadsheet of Instructors (Teachers) and Students, including emails
  and registration identifiers.
- **Industry Partners**: A list of current companies or agencies authorized for student placement.

### 4.1 Operational Workflow Validation

Internara is designed around a specific, high-integrity internship model. Ensure your institutional
policies align with these core flows:

- **Dual Supervision**: Every student must be assigned both a Monitoring Teacher (Academic) and an
  Industry Mentor (Field).
- **Daily Validation**: Students submit daily logs that require verification from their respective
  supervisors to be counted toward competency achievement.
- **Academic Scoping**: Data is strictly partitioned by Academic Years. Ensure you have defined your
  current active cycle.
- **Evidence-Based Assessment**: Grades are derived from a combination of attendance, journal
  consistency, and final evaluation from industry partners.

### 4.2 Infrastructure & Service Requirements

Beyond server hosting, ensure you have access to the following external services required for full
system functionality:

- **Mail Server (SMTP)**: Necessary for sending automated account credentials, password recovery,
  and system alerts to stakeholders.
- **Bot Protection**: Internara utilizes Cloudflare Turnstile for secure form submissions. You will
  need a Site Key and Secret Key (optional for local dev, mandatory for production).
- **Public URL**: To utilize Mobile-First features effectively in the field, the system should be
  accessible via a public domain or a secured tunnel.

---

**Next Step:** [Phase 0: Technical Setup (Step One)](installation.md)

_For technical specifications, refer to the
**[System Requirements Specification](../developers/specs.md)**._
