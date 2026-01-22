# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]



### 🚀 Overview



Focuses on **System Initialization & Setup**, including automated installation and mobile-optimized

student experiences.



---



## [v0.8.0-alpha] - 2026-01-22 (ARC01-INTEL-01)



### 🚀 Overview



Focuses on **Data Intelligence**, building tools to synthesize raw internship data into actionable

insights for institutions and industry partners.



### ✨ Added



- **Reporting Engine (Keystone 1):**

    - Created the `Report` module to handle asynchronous PDF generation.

    - Defined the `ExportableDataProvider` contract in `Shared` for cross-module data extraction.

    - Implemented `ReportService` with support for both synchronous and queued generation.

    - Integrated `GenerateReportJob` for high-performance background processing.

    - Created `InternshipClassReportProvider` for class-wide status reports.

    - Added **Partner Engagement Analytics** report provider with responsiveness and feedback metrics.

- **Placement Lifecycle Tracking:**

    - Implemented **Placement History** log to track student journeys across sequential or

      rotational programs.

    - Created `PlacementLogger` service for transparent audit trails of placement reassignments.

    - Added `reassignPlacement` capability to `InternshipRegistrationService` with mandatory

      logging.

- **Analytical Dashboard:**

    - Implemented `AnalyticsAggregator` in `Core` module to orchestrate cross-module data synthesis.

    - Built **"At-Risk" identification** system to highlight students with low engagement or

      poor assessment scores.

    - Added institutional summary statistics (Total Interns, Active Partners, Placement Rate) to

      the Admin Dashboard.



### 🛠 Changed



- **Service API Expansion:**

    - Enhanced `JournalService` with `getEngagementStats` for responsiveness tracking.

    - Enhanced `AssessmentService` with `getAverageScore` for partner feedback aggregation.



---



## [v0.7.0-alpha] - 2026-01-21 (ARC01-ORCH-01)



### 🚀 Overview

Focuses on **Administrative Orchestration**, expanding beyond simple automation to include
intelligent student-industry matching, proactive activity monitoring, and high-volume data
management.

### ✨ Added

- **Requirement Engine:**
    - Standardized `RequirementType` and `SubmissionStatus` Enums.
    - Implemented `InternshipRequirementService` with full lifecycle management (Submit, Verify,
      Reject).
    - Created `RequirementSubmissionManager` for student-facing prerequisite fulfillment.
    - Added mandatory requirement validation to the internship registration approval workflow.
- **Participation-Driven Assessment:**
    - Created `ComplianceService` to calculate automated student scores based on `Attendance` and
      `Journal` evidence.
    - Integrated real-time participation metrics into Mentor and Teacher evaluation forms.
- **Admin Console & Notifications:**
    - Implemented `JobMonitor` to manage background queues and failed jobs.
    - **Notifier System:** Standardized `Notifier` contract in `Notification` module for
      system-wide UI feedback.
    - **Exception Integration:** Bridged `AppException` to automatically dispatch toast
      notifications via the Notifier service.
- **Stakeholder Management:**
    - Created centralized `UserManager` for all system roles (Student, Teacher, Mentor, Admin).
    - Implemented "Just-in-Time" mentor creation workflow directly within Internship Placements.
    - Restricted Admin account management exclusively to SuperAdmins.
- **Bulk Placement Engine:**
    - Added multi-select capabilities to the `RegistrationManager` table.
    - Implemented **Bulk Placement Modal** for batch assigning students to industry partners.

### 🛠 Changed

- **Architectural Refinement:**
    - Moved `jobs` and related queue migrations from `Shared` to `Core` module for better
      infrastructure isolation.
    - Standardized requirement submission status checks to use Enums across all modules.
    - Enhanced `SlotRegistry` and `SlotManager` with robust error logging and crash prevention.
- **Schema Update:**
    - Added `mentor_id` to `internship_placements` table to support default mentor assignment.
- **Service API:**
    - Expanded `AttendanceService` and `JournalService` contracts with aggregation methods for
      compliance reporting.
    - Updated `EloquentQuery` to support relationship filtering via dot-notation (SQLite compatible).

---

## [v0.6.0-alpha] - 2026-01-19 (ARC01-FEAT-01)

### 🚀 Overview

Completes the foundational internship cycle by implementing role-based workspaces, a unified
assessment engine, and professional PDF reporting with public verification.

### ✨ Added

- **RBAC Workspaces:**
    - Decomposed the monolithic `Dashboard` into four specialized modules: `Admin`, `Student`,
      `Teacher`, and `Mentor`.
    - Implemented role-based routing (`/admin`, `/student`, `/teacher`, `/mentor`).
    - Established clear workspace boundaries while keeping User identity centralized.
- **Assessment Module:**
    - Unified scoring system for Industry Mentors and Academic Teachers.
    - Automated score averaging and feedback collection.
    - Integrated assessment triggers directly into actor dashboards.
- **Automated PDF Reporting:**
    - Professional **Certificate of Completion** generation with digital signatures.
    - **Internship Transcript** generation with detailed score breakdown.
    - Contextual download triggers for Students (post-assessment) and Admins.
- **Artifact Verification:**
    - Integrated **QR-Code Verification** on all generated certificates.
    - Implemented **Signed URL** verification landing pages for external parties.

### 🛠 Changed

- **Module Deprecation:** Removed the `Dashboard` module entirely.
- **Routing Logic:** Updated `RedirectService` to route users to their new dedicated workspace URLs.
- **Global Documentation:** Synchronized the `Testing Guide` with the comprehensive technical
  library.
- **Arch Definition:** Formally defined the `app/` directory as an Infrastructure Module in the
  `Architecture Guide`.

### 🐛 Fixed

- **Layout References:** Fixed 500 errors in `Attendance` and `Journal` modules by updating
  references from the deleted `Dashboard` layout to `UI` layout.
- **Icon Naming:** Resolved `SvgNotFound` exceptions by enforcing `tabler.name` convention across
  all new modules and components.
- **Model Stability:** Resolved environment-specific Signal 4 (Illegal Instruction) crashes by
  migrating `decimal:2` casts to `float` in the `Assessment` model.
- **Policy Integrity:** Created `InternshipRegistrationPolicy` to allow students to view their own
  academic records.

---

## [v0.5.0-alpha] - 2026-01-18 (ARC01-OPER-01)

### 🚀 Overview

Focuses on the operational and activity tracking phase, introducing Daily Journals (Logbook),
Attendance tracking, and Supervisor matching relationships.

### ✨ Added

- **Supervisor Matching:** Formal relationship between Students, Academic Teachers, and Industry
  Mentors.
- **Journal Module:**
    - Core implementation of daily student logbooks with "Dual-Authority Approval" policy.
    - Added **"Save as Draft"** functionality for students.
    - Implemented **"Week at a Glance"** visual tracking sidebar for student progress.
    - Added **Private Media Attachments** support for journal entries with temporary URL generation.
    - Built comprehensive **Journal Detail Modal** for supervisor review.
    - Implemented **Strict Journal Locking**; prevents updates or deletions once approved or
      verified.
- **Attendance Module:**
    - Daily check-in/out system with automated status determination (Present/Late).
    - Integrated with global application settings for dynamic late thresholds.
    - Enforced **Double Check-in Prevention** via database unique constraints and service logic.
- **Mentor Role:** New system role for industry supervisors with dedicated dashboard.
- **Academic Year Scoping:** Added `academic_year` column to all operational tables
  (`registrations`, `journals`, `attendance`) to ensure multi-year data integrity.

### 🛠 Changed

- **UI Consolidation:**
    - Merged `file-upload` and `file` components into a single interactive `x-ui::file` component.
    - Integrated `x-mary-file` as the internal engine for the UI module to support robust file
      handling and image cropping.
    - Added support for slots in the file component to maintain backward compatibility with custom
      previews (e.g., Avatars).
- **Icon Standardization:**
    - Unified the entire application to use **Tabler Icons** (`tabler.name` format) for better
      consistency and performance.
    - Updated all feature modules (`Attendance`, `Department`, `Internship`, `Journal`, `Dashboard`,
      `User`, `Profile`) to follow the new naming standard.
- **UI Standardization:** Refactored all feature modules to use centralized `UI` components instead
  of direct MaryUI/DaisyUI classes.
- **Card Styling:** Standardized `x-ui::card` with default borders, rounded corners, and shadows.
- **UI Route Integrity:** Added missing route files and service provider fixes for the `UI` module.
- **Unified Dashboards:** All role-specific dashboards now use a consistent local layout.
- **Expanded Registrations:** `InternshipRegistration` now tracks assigned Teacher and Mentor.

### 🐛 Fixed

- **View Compatibility:** Resolved `Undefined variable $logo_url` in `SchoolManager` by
  standardizing form data binding.
- **Icon Rendering:** Fixed `SvgNotFound` exceptions by standardizing on the `tabler.name` format
  compatible with MaryUI's default icon handler.
- **Service Signatures:** Fixed method signature mismatch in `JournalService::delete` to ensure
  inheritance compatibility with `EloquentQuery`.

---

## [v0.4.0-alpha] - 2026-01-17 (ARC01-INST)

### 🚀 Overview

Establishes the institutional and academic core of the application, featuring decoupled
school/department management, industry placement tracking, and localized user interfaces.

### ✨ Added

- **Internship Placements:** Full CRUD for managing industry partner slots and internship programs.
- **Language Switcher:** Global UI component and middleware for real-time locale switching (EN/ID).
- **School & Department Management:** Comprehensive administrative tools for managing institutional
  identity and academic structures.
- **Academic Integration:** Linked user profiles directly to departments for institutional context.
- **Security Policies:** Granular access control for School, Department, and Internship modules.

### 🛠 Changed

- **Database Decoupling:** Removed physical foreign key constraints across module boundaries to
  ensure true modular isolation and portability.
- **UUID Standard:** Migrated all institutional entities (School, Department, Internship) to UUID
  primary keys.
- **Modular Relations:** Standardized inter-module relationships via Service-driven Concerns.
- **Setting Helper:** Refactored `setting()` for reliable multi-module fallback and performance.
- **Terminology:** Reverted "Serial Code" back to "Series Code" to better represent development
  cycles.

### 🐛 Fixed

- **Migration Integrity:** Resolved foreign key mismatch errors in SQLite by fixing primary key
  definitions.
- **Service Consistency:** Unified parameter naming and method visibility across core services.

### ⚠️ Breaking Changes

- **Schema Update:** Tables `schools` and `departments` now use UUIDs.
- **Relational Integrity:** Constraints are now managed at the application layer; direct
  database-level cascading deletes across modules are disabled.

---

## [v0.3.0-alpha] - 2026-01-15 (ARC01-USER)

### Added

- **Administrative User Management:** Full CRUD interface with role-based adaptive forms.
- **Specialized Profiles:** Polymorphic-like relations for **Student** (NISN) and **Teacher** (NIP).
- **Email Verification:** Mandatory verification for Student and Teacher roles to secure the
  platform.
- **Role-Based Redirection:** Intelligent post-login redirection based on user roles.
- **Institution-Centric Notifications:** Customized email verification and welcome templates.

### Fixed

- **Security Hardening:** Remediated Authentication Bypass in email verification.
- **Access Control:** Fixed IDOR vulnerabilities via explicit Policy enforcement.
- **Privacy:** Implemented email masking in application logs to prevent PII leaks.

---

## [v0.2.0-alpha] - 2026-01-10 (ARC01-CORE)

### Added

- **RBAC Infrastructure:** Full integration of `spatie/laravel-permission` within a modular context.
- **Shared Concerns:** Foundational concerns for **UUID** support and **Status** management.
- **EloquentQuery Service:** Base service for standardized CRUD and query orchestration.

---

## [v0.1.1-alpha] - 2026-01-01 (ARC01-INIT)

### Added

- **Project Genesis:** Initial environment setup with Laravel 12 and PHP 8.4.
- **Modular Monolith:** Implementation of `nwidart/laravel-modules`.
- **UI Module:** Centralized design system using **DaisyUI** and **MaryUI**.

[Unreleased]: https://github.com/reasnov/internara/compare/v0.7.0-alpha...main
[v0.7.0-alpha]: https://github.com/reasnov/internara/compare/v0.6.0-alpha...v0.7.0-alpha
[v0.6.0-alpha]: https://github.com/reasnov/internara/compare/v0.5.0-alpha...v0.6.0-alpha
[v0.5.0-alpha]: https://github.com/reasnov/internara/compare/v0.4.0-alpha...v0.5.0-alpha
[v0.4.0-alpha]: https://github.com/reasnov/internara/compare/v0.3.0-alpha...v0.4.0-alpha
[v0.3.0-alpha]: https://github.com/reasnov/internara/compare/v0.2.0-alpha...v0.3.0-alpha
[v0.2.0-alpha]: https://github.com/reasnov/internara/compare/v0.1.1-alpha...v0.2.0-alpha
[v0.1.1-alpha]: https://github.com/reasnov/internara/releases/tag/v0.1.1-alpha
