# Program — API Reference

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Reference mapped to the Program domain

This reference details the class structures, models, actions, and Livewire components belonging to the **Program** domain.

---

## Actions

### Program Lifecycle Actions
| File | Class | Extends | Description |
|---|---|---|---|
| `Program/Actions/CreateInternshipAction.php` | `CreateInternshipAction` | `BaseAction` | Provisions a new internship program period |
| `Program/Actions/UpdateInternshipAction.php` | `UpdateInternshipAction` | `BaseAction` | Updates calendar ranges and attributes |
| `Program/Actions/DeleteInternshipAction.php` | `DeleteInternshipAction` | `BaseAction` | Deletes a program (aborts if student registrations exist) |
| `Program/Actions/BatchUpdateInternshipStatusAction.php` | `BatchUpdateInternshipStatusAction` | `BaseAction` | Bulk updates statuses across multiple programs |
| `Program/Actions/CheckCloseReadinessAction.php` | `CheckCloseReadinessAction` | `BaseAction` | Audits documentation and grades to check close-readiness |

### Phase, Group, and Requirement Actions
| File | Class | Extends | Description |
|---|---|---|---|
| `Program/Actions/CreateInternshipPhaseAction.php` | `CreateInternshipPhaseAction` | `BaseAction` | Creates calendar phase timelines within a program |
| `Program/Actions/UpdateInternshipPhaseAction.php` | `UpdateInternshipPhaseAction` | `BaseAction` | Modifies phase descriptions or dates |
| `Program/Actions/DeleteInternshipPhaseAction.php` | `DeleteInternshipPhaseAction` | `BaseAction` | Deletes phase spans |
| `Program/Actions/CreateInternshipGroupAction.php` | `CreateInternshipGroupAction` | `BaseAction` | Creates student-mentor supervision groups |
| `Program/Actions/UpdateInternshipGroupAction.php` | `UpdateInternshipGroupAction` | `BaseAction` | Edits supervision group identifiers |
| `Program/Actions/DeleteInternshipGroupAction.php` | `DeleteInternshipGroupAction` | `BaseAction` | Deletes empty supervision groups |
| `Program/Actions/AddMemberToGroupAction.php` | `AddMemberToGroupAction` | `BaseAction` | Adds student or mentor users to a group |
| `Program/Actions/RemoveMemberFromGroupAction.php` | `RemoveMemberFromGroupAction` | `BaseAction` | Removes users from a group |
| `Program/Actions/CreateRequirementAction.php` | `CreateRequirementAction` | `BaseAction` | Creates required enrollment document placeholders |
| `Program/Actions/UpdateRequirementAction.php` | `UpdateRequirementAction` | `BaseAction` | Modifies document requirement parameters |
| `Program/Actions/DeleteRequirementAction.php` | `DeleteRequirementAction` | `BaseAction` | Removes document requirement parameters |

---

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Program/Livewire/InternshipManager.php` | `InternshipManager` | `BaseRecordManager` | Admin CRUD managing program periods and statuses |
| `Program/Livewire/InternshipPhaseManager.php` | `InternshipPhaseManager` | `BaseRecordManager` | Admin timeline scheduler mapping program phases |
| `Program/Livewire/InternshipGroupManager.php` | `InternshipGroupManager` | `BaseRecordManager` | Admin interface managing group supervision mappings |
| `Program/Livewire/RequirementManager.php` | `RequirementManager` | `Component` | Admin editor tracking required files checksheets |

### Livewire Form Objects
| File | Class | Extends | Used By |
|---|---|---|---|
| `Program/Livewire/Forms/InternshipForm.php` | `InternshipForm` | `Form` | `InternshipManager` |
| `Program/Livewire/Forms/InternshipPhaseForm.php` | `InternshipPhaseForm` | `Form` | `InternshipPhaseManager` |
| `Program/Livewire/Forms/InternshipGroupForm.php` | `InternshipGroupForm` | `Form` | `InternshipGroupManager` |
| `Program/Livewire/Forms/InternshipRequirementForm.php` | `InternshipRequirementForm` | `Form` | `RequirementManager` |

---

## Models

### Internship (`Internship.php`)
- **Extends**: `BaseModel`
- **Fields**: name, start_date, end_date, academic_year_id, status (cast to `InternshipStatus` enum)
- **Relationships**:
  - `phases` → `HasMany` (InternshipPhase)
  - `groups` → `HasMany` (InternshipGroup)
  - `requirements` → `HasMany` (InternshipDocumentRequirement)

### InternshipPhase (`InternshipPhase.php`)
- **Extends**: `BaseModel`
- **Fields**: internship_id, name, start_date, end_date, description

### InternshipGroup (`InternshipGroup.php`)
- **Extends**: `BaseModel`
- **Fields**: internship_id, name, description
- **Relationships**:
  - `members` → `HasMany` (InternshipGroupMember)

### InternshipGroupMember (`InternshipGroupMember.php`)
- **Extends**: `BaseModel`
- **Fields**: internship_group_id, user_id, role (cast to `InternshipGroupRole` enum)

### InternshipDocumentRequirement (`InternshipDocumentRequirement.php`)
- **Extends**: `BaseModel`
- **Fields**: internship_id, name, description, category, type (cast to `RequirementType` enum), is_mandatory

---

## Entities, Enums, and Events

### Entities
- `InternshipState`: DTO evaluating date conditions.
- `InternshipPeriod`: DTO checking program active ranges.
- `InternshipGroupState`: DTO mapping group rosters.

### Enums
- `InternshipStatus` (implements `LabelEnum`, `StatusEnum`): `DRAFT`, `PUBLISHED`, `ACTIVE`, `CLOSING`, `CLOSED`.
- `RequirementType` (implements `LabelEnum`): `PDF_FILE`, `IMAGE_FILE`, `DIGITAL_SIGNATURE`.
- `InternshipGroupRole` (implements `LabelEnum`): `STUDENT`, `ACADEMIC_MENTOR`, `COMPANY_SUPERVISOR`.

### Events
- `InternshipCreated`: Dispatched when an internship is created.
- `NotifyAdminsInternshipCreated`: Listener notifying admins on publication.

---

## Policies

- `InternshipPolicy`: Gated checks for program CRUD operations.
- `InternshipPhasePolicy`: Restricts phase modifications.
- `InternshipGroupPolicy`: Restricts group assignments and memberships.
