# Partners — Companies, Partnerships & MoU

> **Last updated:** 2026-06-10
> **Changes:** sync — initial metadata sync with new format

## Description
External relationship management: company profiles, partnership agreements (MoU), placement slot capacity, and agreement lifecycle.


## Purpose & Boundary

Partners manages the school's external relationships with host companies for internship placements. Companies are organizational profiles with legal details, industry classification, and contact information. Partnerships represent formal agreements (MoU) that document terms, duration, and scope. Each partnership provides placement slot capacity consumed by the Enrollment module. Partnerships have a lifecycle (ACTIVE → EXPIRED/TERMINATED) that controls whether new placements can be created.

Out of scope: student placement assignment (Enrollment), internship program definitions (Program), certificate issuance (Certification).

## Submodules

### Company
Organization profile: legal name, trading name, address, industry classification, website, phone, email, and notes. Soft-deletes preserve historical placements and partnerships. Companies are referenced by partnerships and placement records.

##Partners — Companies, Partnerships & MoUhip
Formal agreement record: agreement number, title, description, start/end dates, scope of cooperation, contact person, signing parties, and MoU document upload (via Spatie Media Library). Status lifecycle: `active` → `expired` (automatic on end date) or `active` → `terminated` (admin action). Both `expired` and `terminated` are terminal states. Only active partnerships allow new placements. Expiry warning notifications fire 30 days before end_date.

## Key Concepts

##Partners — Companies, Partnerships & MoUhip Lifecycle

Partnerships follow a controlled lifecycle:
1. **ACTIVE**: New placements can be created. MoU is current.
2. **EXPIRED**: End date reached. No new placements. Existing placements unaffected.
3. **TERMINATED**: Admin action ends agreement early. No new placements. Existing placements unaffected.

Transitions to EXPIRED are automatic (date-based). Transitions to TERMINATED require admin authorization. Both terminal states preserve the partnership record for historical audit — only the placement selection UI filters them out.

### Company→Partnership→Placement Chain

A company may have multiple partnerships over time (different programs, different terms, different scopes). Each partnership defines placement slots. The Enrollment module creates Placements that consume these slots. Deleting a company is blocked if it has active placements, preventing orphaned enrollment records.

### MoU Document Management

Partnership agreements are uploaded as media files via Spatie Media Library. Each partnership can have one or more attached documents (e.g., signed agreement PDF, amendment letters). Document upload is recommended but not mandatory — the system warns when finalizing a partnership without an attached MoU.

## Dependencies

- Core (base classes)
- User (contact person references)

## Used By

- Program (company slot references for internship groups)
- Enrollment (placement slot capacity)
