# Partner — Companies, Partnerships & MoU

## Description

External relationship management: company profiles, partnership agreements (MoU), placement slot
capacity, and agreement lifecycle.

## Purpose & Boundary

Partner manages the school's external relationships with host companies for internship placements.
Companies are organizational profiles with legal details, industry classification, and contact
information. Partnerships represent formal agreements (MoU) that document terms, duration, and
scope. Each partnership provides placement slot capacity consumed by the Enrollment module.
Partnerships have a lifecycle (ACTIVE → EXPIRED/TERMINATED) that controls whether new placements can
be created.

Out of scope: student placement assignment (Enrollment), internship program definitions (Program),
certificate issuance (Certification).

## Submodules

### Company

Organization profile: legal name, trading name, address, industry classification, website, phone,
email, and notes. Soft-deletes preserve historical placements and partnerships. Companies are
referenced by partnerships and placement records.

### Partnership

Formal agreement record: agreement number, title, description, start/end dates, scope of
cooperation, contact person, signing parties, and MoU document upload (via Spatie Media Library).
Status lifecycle: `active` → `expired` (automatic on end date) or `active` → `terminated` (admin
action). Both `expired` and `terminated` are terminal states. Only active partnerships allow new
placements. Expiry warning notify fire 30 days before end_date.

## Key Concepts

### Partnership Lifecycle

Partnerships follow a controlled lifecycle:

1. **ACTIVE**: New placements can be created. MoU is current.
2. **EXPIRED**: End date reached. No new placements. Existing placements unaffected.
3. **TERMINATED**: Admin action ends agreement early. No new placements. Existing placements
   unaffected.

Transitions to EXPIRED are automatic (date-based). Transitions to TERMINATED require admin
authorization. Both terminal states preserve the partnership record for historical audit — only the
placement selection UI filters them out.

### Company→Partnership→Placement Chain

A company may have multiple partnerships over time (different programs, different terms, different
scopes). Each partnership defines placement slots. The Enrollment module creates Placements that
consume these slots. Deleting a company is blocked if it has active placements, preventing orphaned
enrollment records.

### MoU Document Management

Partnership agreements are uploaded as media files via Spatie Media Library. Each partnership can
have one or more attached documents (e.g., signed agreement PDF, amendment letters). Document upload
is recommended but not mandatory — the system warns when finalizing a partnership without an
attached MoU.

## Dependencies

- Core (base classes)
- User (contact person references)

## Used By

- Program (company slot references for internship groups)
- Enrollment (placement slot capacity)

## Design Principles

- **Partnership lifecycle is terminal on exit** — both `expired` (automatic on end date) and `terminated` (admin action) are final states. They preserve the partnership record for audit but filter out of the placement UI. Existing placements are unaffected by lifecycle transitions — the rule only gates new placements.
- **Company deletion is blocked by active placements** — a company with active placements cannot be soft-deleted. This prevents orphaned enrollment records and ensures the company record remains queryable for historical placement reporting.
- **Partnership capacity is the placement budget** — each partnership defines slot count consumed by the Enrollment module. Capacity is enforced atomically at placement creation time. One partnership may cover multiple programs with independent slot pools; capacity is per-partnership, not per-company.
- **MoU upload is recommended but not mandatory** — the system warns when finalizing a partnership without an attached MoU document but does not block finalization. The warning is the signal; enforcement of document completeness is a policy decision outside the module.

## How It Works

Partner is the supply side of the internship: without a company willing to host students and a
signed agreement behind it, there is no placement to make. A company profile is the organization's
identity — legal and trading name, address, industry classification, contacts — and it is kept
separate from the agreements so that one company can hold several partnerships over the years
without duplicating its details.

The partnership is the agreement itself, and it has a life. It is active from a signed start date,
expires on its own once the end date passes, or is terminated early by an administrator; both
expiry and termination are final, because a lapsed agreement must not be reactivated as though it
had never lapsed. Only an active partnership can receive new placements, which is the mechanism by
which a company that stops hosting simply stops appearing in placement options — without anyone
having to remember to hide it.

Expiry is anticipated rather than discovered. A warning is raised ahead of the end date so a
coordinator can renew in time, and renewing is an explicit act with its own record. Because both
companies and partnerships are soft-deleted, a placement made three years ago still resolves to
the company it referred to, even after that company has been archived.
