# School Module

The `School` module manages the identity and configuration of the educational institution. It serves
as the primary source for branding and institutional data within the platform.

## Purpose

- **Institutional Identity:** Stores school details like name, address, contact information, and
  branding (logo).
- **Global Branding:** Provides logo and school name for use in system headers, documents, and
  reports.

## Core Components

### 1. School Model

- Represents the physical institution.
- Integrated with **Spatie MediaLibrary** for logo management.
- Uses `HasSchoolRelation` trait to provide relationships to other domain entities (e.g.,
  Departments).

### 2. SchoolService

- Orchestrates the creation and updating of school records.
- Handles logic for logo updates and storage.
- Ensures only one primary school record exists in the system.

### 3. SchoolManager (Livewire)

- A comprehensive administrative interface for managing school details.
- Handles data entry for name, address, email, phone, and logo uploads.
- Authorized via `SchoolPolicy`.

## Technical Implementation

- **Media Collection:** Uses a dedicated media collection `logo` for institutional branding.
- **Authorization:** Standard `school.manage` permission required for institutional updates.

---

**Navigation** [← Back to Module TOC](table-of-contents.md)
