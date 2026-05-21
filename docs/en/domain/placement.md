# Placement Domain

## Purpose

Placement bridges the gap between supply (slots at partner companies defined by Partnership 
agreements) and demand (students who have registered and need a host organization). It manages 
how many students each company can host, assigns students to available slots, handles change 
requests when circumstances evolve, and maintains a waitlist when demand exceeds supply. This 
domain ensures that every registered student has a confirmed placement and that no company's 
agreed capacity is exceeded. It is the critical handshake between the Partnership domain (which 
defines what slots exist) and the Registration domain (which creates the students who need them).

## Boundary

**In scope:** Placement slot definition (per company per program, with total capacity and filled 
count), slot capacity management (track available vs. assigned, adjust capacity within agreement 
limits), student-to-slot assignment (automatic matching and manual assignment), placement 
confirmation workflow (both student and company confirm the placement), placement status 
lifecycle (pending, confirmed, in_change, changed, cancelled), placement change requests (student 
requests company change, company requests student change), waitlist management (ordered queue 
when slots are full), placement reporting (fill rates, unplaced students, slot utilization per 
program and per company).

**Out of scope:** Company partnership agreement management (Partnership domain owns agreement 
terms and capacity ceilings — Placement reads but does not modify them), internship program 
definition (Internship domain), student registration and application processing (Registration 
domain), mentor assignment after placement (Mentor domain), document generation for placement 
letters or confirmation documents (Document domain), company contact management (Partnership 
domain).

## Key Concepts

**Placement Slots.** A slot represents one available position at a specific company for a 
specific internship program. Each slot record tracks: the company and partnership agreement it 
originates from, the internship program it serves, the total capacity (how many students this 
slot represents — typically 1, but can be larger for group placements), the number of filled 
positions (how many students are currently assigned to this slot), and the current status (ACTIVE 
— available for assignment; FULL — capacity reached; SUSPENDED — temporarily unavailable; 
CLOSED — slot no longer available for this program cycle). Multiple slots can exist for the 
same company-program pair to accommodate larger cohorts. The sum of capacities across all slots 
for a company in a program is constrained by the Partnership agreement's total capacity for that 
program — this ceiling is enforced by the Placement domain at write time.

**Student-to-Slot Assignment.** When a student is registered (Registration domain) and needs a 
placement, the system assigns them to an available slot. Two assignment modes exist. AUTO_MATIC: 
the system matches students to slots based on configurable criteria — student preferences 
(industry, location, company size), slot availability, and optionally a ranking or merit order. 
The matching algorithm produces assignments that maximize preference satisfaction within capacity 
constraints. MANUAL: an admin or mentor directly assigns a student to a specific slot, bypassing 
the automatic matching. This mode is used for exceptional situations, special accommodations, or 
when a company specifically requests a particular student. Both modes record the assignment 
timestamp, the assigner identity, and the assignment reason.

**Placement Status Lifecycle.** Assignments progress through a defined state machine. PENDING: 
the assignment has been made (auto or manual) but not yet confirmed by both parties — this is a 
tentative hold on the slot. CONFIRMED: both the student and the company have confirmed acceptance 
of the placement — this is the stable, operational state. IN_CHANGE: a change request has been 
initiated and is being processed — the student's current placement is temporarily under review. 
CHANGED: the student has been moved to a different slot following a successful change request — 
the previous slot is released and the new slot is assigned. CANCELLED: the placement is no longer 
needed (student withdrew, company backed out, program cancelled). The PENDING → CONFIRMED 
transition requires explicit confirmation from BOTH the student and the company — a single 
confirmation leaves it in PENDING. Pending assignments that are not confirmed within a 
configurable window (default 7 days) auto-release the slot.

**Change Requests.** After placement is confirmed, either party can initiate a change request. 
The student might want a different company (unsatisfactory conditions, commute issues, role 
mismatch). The company might request a student change (student-company fit issues, capacity 
constraints). Each change request captures: the requesting party (student or company), the reason 
category and detailed explanation, the desired new placement (specific company or "any available" 
for students), and supporting details. The request is reviewed by an admin who can approve or 
reject it with a reason. The change execution is ATOMIC: the old slot is released and the new 
slot is assigned in a single database transaction, ensuring the student is never in a gap state 
without a placement. The change history is preserved: the system records the original placement, 
the new placement, the reason for change, and the approving admin.

**Waitlist.** When all available slots for a program are filled, newly registered students are 
placed on a waitlist. The waitlist is strictly ordered by objective criteria: registration 
completion date (first registered, first offered) and optionally additional factors like merit or 
preferences. Students on the waitlist can see their queue position and an estimated wait time. 
When a slot opens up (cancellation, capacity increase, change request freeing a slot), the first 
student on the waitlist is automatically offered the slot. The offer has a time limit 
(configurable, default 48 hours) for the student to accept or decline. If declined, the offer 
moves to the next student on the waitlist. Students can remove themselves from the waitlist at 
any time.

**Placement Reporting.** The domain provides reports for program coordinators and admins: slot 
fill rates per company and per program (how many slots are filled vs. total capacity), unplaced 
student lists (registered but not yet assigned), waitlist size and composition, change request 
frequency and outcomes, and historical placement data (which students were placed where in past 
programs). These reports inform capacity planning, partnership development, and program 
optimization decisions.

## Requirements

### User Stories

| Role | Story |
|------|-------|
| Admin | As an admin, I want to create placement slots per company and program so that available positions are tracked |
| Admin | As an admin, I want to assign students to available slots automatically or manually so that every registered student gets a placement |
| Admin | As an admin, I want to review and approve or reject placement change requests so that transfers are managed fairly |
| Admin | As an admin, I want to view placement reports (fill rates, waitlist, unplaced students) so that I can make capacity decisions |
| Student | As a student, I want to confirm my placement so that I can proceed to active participation |
| Student | As a student, I want to request a placement change if circumstances require it |
| Student | As a student, I want to see my waitlist position so that I know where I stand |
| Company | As a company representative, I want to confirm or decline a placed student so that I have control over who joins |
| System | As the system, I want to enforce slot capacity so that no company exceeds its agreed limit |

### Process Flow

```
Placement Assignment:

PENDING ──→ CONFIRMED ──→ IN_CHANGE ──→ CHANGED (atomic swap to new slot)
   │                          │
   ↓                          ↓
CANCELLED                  CANCELLED

Change Request:

PENDING ──→ APPROVED  (atomic: release old slot, assign new)
   │
   ↓
REJECTED
```

- **PENDING**: Assigned but not yet confirmed by both parties — tentative hold on slot
- **CONFIRMED**: Both student and company confirmed — stable operational state
- **IN_CHANGE**: Change request initiated, current placement under review
- **CHANGED**: Moved to a different slot — previous slot released, new slot assigned
- **CANCELLED**: Placement no longer needed
- Pending → Confirmed requires explicit confirmation from BOTH student AND company
- Pending assignments auto-release after configurable window (default 7 days)
- Change execution is ATOMIC — no gap state between slots

### Key Operations

| Action | Description |
|--------|-------------|
| `CreatePlacementAction` | Creates a new placement slot |
| `UpdatePlacementAction` | Updates an existing placement |
| `DeletePlacementAction` | Deletes a placement |
| `DirectPlacementAction` | Manually assigns a student to a slot |
| `RequestPlacementChangeAction` | Initiates a placement change request |
| `ApprovePlacementChangeAction` | Approves a change request (atomic slot swap) |
| `RejectPlacementChangeAction` | Rejects a change request |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `InternshipPlacement` (`placements`), `PlacementChangeRequest` |
| **Entities** | `PlacementState` (placement lifecycle checks), `PlacementCapacity` (capacity calculation) |
| **Enums** | `PlacementChangeStatus` — `PENDING`, `APPROVED`, `REJECTED` |
| **Livewire** | `PlacementIndex`, `DirectPlacementManager`, `PlacementChangeManager`, `StudentPlacementChangeRequest` |

## Dependencies

| Dependency | Reason |
|---|---|
| Internship | Programs define which placements are needed, their date ranges, and slot 
requirements |
| Partnership | Companies and their active agreements provide the capacity ceiling for slot 
creation |
| Registration | Student registrations are the source of placement demand — without registered 
students, there is nothing to place |
| Core | BaseAction, BaseModel, SmartLogger, BaseRecordManager |

## Important Rules

- The number of students assigned to a slot must never exceed its total capacity — enforced at 
the database constraint level.
- Each student can have at most one active placement (PENDING or CONFIRMED status) at any time 
— no double placements.
- Change requests execute atomically: the old slot is released and the new slot assigned in a 
single transaction, eliminating gap states.
- Slots cannot be overallocated even during PENDING phase — pending assignments count toward 
capacity.
- A slot's capacity cannot be reduced below the current number of confirmed assignments — 
reduce requires freeing slots first.
- Pending placements auto-release if not confirmed within the configurable confirmation window 
(default 7 days).
- The waitlist is strictly ordered by objective criteria — position cannot be manually changed.
- Company slot assignment cannot exceed the total capacity defined in the active partnership 
agreement for that program.
- Change request decisions (approve/reject) are logged with the decision-maker's identity and 
reason.
