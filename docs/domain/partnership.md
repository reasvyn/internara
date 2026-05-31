# Partnership Domain
> Last updated: 2026-05-31
> **Status:** ✅ **Fully Implemented** — all 21 files in [reference](partnership-reference.md) exist

## Purpose

Partnership manages external relationships — companies and formal agreements that define each
partnership's terms for hosting students.

---

## Design Principles

### 1. Company as Core Entity

Companies are the primary entity. Partnerships (MoU agreements) are secondary — a company
can have multiple partnerships over time.

### 2. Partnership Lifecycle

Partnerships flow through ACTIVE → EXPIRED → TERMINATED. Expiry detection warns at 30 days.

---

## Domain Boundary

The Partnership domain owns all external relationships between the school and organizations that host students. It manages company profiles with name, address, industry classification, website, and contact information. It also manages formal partnership agreements — numbered agreements with titles, date ranges, scope descriptions, designated contact persons, and signing party details. Partnerships follow a lifecycle from active through expired to terminated, with configurable expiry detection that warns administrators when an agreement is approaching its end date.

Partnership does not own placement slots, student assignments, or quota management — those belong to the Placement domain, which uses companies and partnerships as reference data for slot allocation. It does not own program definitions (Internship), student identity data (User), or registration workflows (Registration). Partnership defines who the external partners are and what the agreements say; it does not manage how students are assigned to those partners.

The domain references no other business domains directly — companies and partnerships are self-contained entities. It is consumed by the Placement domain for slot assignment context, by the Internship domain for program-partner linkage, and by the Admin domain for management interfaces. But Partnership itself owns only the relationship records and their lifecycle.

---

## Key Features

- Create, update, and delete company profiles with name, address, industry, website, and contact details.
- Create, update, and delete partnership agreements with number, title, dates, scope, and signing parties.
- Manage partnership status through an active, expired, and terminated lifecycle with transition rules.
- Upload and attach memorandum-of-understanding documents to partnership agreements via the media library.
- Detect and warn when a partnership agreement is approaching its expiration date within a configurable window.
- Browse and search all companies and their associated partnership agreements.
- Filter partnerships by status using a dropdown selector for active, expired, and terminated states.
- Sort the company and partnership lists by clicking on column headers for name, industry, or dates.
- Upload a memorandum-of-understanding document via drag and drop on the partnership form with a preview of the attached file.
- See a warning badge on partnership rows that are approaching their expiration date.
- Receive a flash toast notification when a company or partnership agreement is created, updated, or deleted.
