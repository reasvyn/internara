# Partners Domain

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Consolidated Company and Partnership agreements

## Purpose

The **Partners** domain manages the institution's external relationships. This includes company profiles (DUDI - *Dunia Usaha Dunia Industri*) and formal partnership agreements (MoU - *Memorandum of Understanding*) signed between the school and partner companies.

It establishes the institutional context for student placements. A company must be registered as a partner and have an active partnership agreement for students to be assigned to it.

---

## Design Principles

### 1. Company and Agreement Separation
- **Companies** exist as independent entities in the system. They hold static organizational information (name, address, industry sector, contact email, phone, and representative name).
- **Partnership Agreements (MoU)** represent the legal, time-bound agreements between the school and a company.
- A company can have multiple partnership agreements over time (e.g., historical renewals or sequential MoUs). Deleting or terminating an agreement does not delete the host company profile.

### 2. Partnership Agreement Lifecycle
Partnerships follow a strict state machine managed by `PartnershipStatus`:
`DRAFT` ➔ `ACTIVE` ➔ `EXPIRED` or `TERMINATED`
- **Active agreements** allow student placement slots to be allocated.
- **Expired agreements** automatically block new placement allocations but preserve active placements until completion.
- **Expiry Warnings**: The system flags agreements expiring within 30 days to alert admins for renewals.
- **Termination**: Active agreements can be terminated manually with a recorded reason, transitioning them to a terminal state that prevents further updates.

### 3. Verification and File Attachments
- Every partnership agreement requires an uploaded PDF document (the signed MoU file) stored via Spatie Media Library under the `mou` collection.
- Deleting a company profile requires checking that no current or historical placement records are linked to it, protecting placement audits.

---

## Domain Boundary

### Technical Ownership
- **Company Profiles**: CRUD operations for companies, industry classification, contact representative linkage.
- **Partnership Agreements**: CRUD operations for MoU tracking, start and end date validation, signing records.
- **Agreement Media**: Storing, retrieving, and replacing signed MoU document PDFs.
- **Lifecycle Transition Actions**: Activating, renewing, and terminating agreements with state machine safety.

### Dependencies
- **Core**: Uses `BaseModel`, `BaseAction`, `BasePolicy`, and `SmartLogger` for mutation tracking.
- **User**: Links company contacts to `User` records.
- **Enrollment (formerly Placement/Registration)**: Placements reference companies for internship slots. Placements block company deletion if active placements exist.

---

## Domain Rules & Invariants

- **R1 — Company Independence**: Companies can exist without active partnerships. However, students can only be placed at companies with an `ACTIVE` partnership status on the start date.
- **R2 — Non-overlapping Agreements**: A company cannot have two `ACTIVE` partnership agreements with overlapping date ranges.
- **R3 — Date Logical Integrity**: The `start_date` of a partnership agreement must be before or equal to the `end_date`.
- **R4 — Expiry Warn Threshold**: Agreements with `ACTIVE` status whose `end_date` is within 30 days from the current date are flagged as expiring.
- **R5 — PDF Attachment Required**: Moving a partnership agreement from `DRAFT` to `ACTIVE` status requires a PDF document to be uploaded in the `mou` collection.
- **R6 — Safeguarded Deletions**: A company profile cannot be deleted if it has associated placements (active or archived) or verified student registrations.

---

## Key Features

- **Company Profile CRUD**: Manage company name, address, sector, website, contact phone, email, and liaison person.
- **MoU Agreement Creator**: Create partnership spans with custom MoU document numbers, start dates, end dates, and signing witnesses.
- **Signed MoU File Manager**: Upload, view, and replace PDF attachments for each agreement.
- **Bulk Delete Companies**: Safe bulk action checking database constraints before purging profiles.
- **Partnership Renewal Flow**: Renew expiring partnerships by creating sequential agreements, maintaining the link to the company profile.
- **Manual Agreement Termination**: Terminate active agreements immediately, requiring a logged reason for the action.
- **Expiry Alerts Dashboard**: Alerts administrators on the dashboard regarding upcoming partnership expirations.
