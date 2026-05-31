# Registration — API Reference
> Last updated: 2026-05-31
> Changes: docs: audit — all items Implemented

> **Legend:** ✅ Implemented = code exists | ⏳ Planned = not yet implemented

Total: 22 files — ✅ 22 Implemented

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Registration/Actions/ApplyAccountAction.php` | `ApplyAccountAction` | `BaseAction` | Submits a new internship account application |
| `Registration/Actions/ApproveAccountApplicationAction.php` | `ApproveAccountApplicationAction` | `BaseAction` | Approves a pending account application |
| `Registration/Actions/RejectAccountApplicationAction.php` | `RejectAccountApplicationAction` | `BaseAction` | Rejects an account application with reason |
| `Registration/Actions/RegisterInternshipAction.php` | `RegisterInternshipAction` | `BaseAction` | Registers a student for an internship program |
| `Registration/Actions/UploadRegistrationDocumentAction.php` | `UploadRegistrationDocumentAction` | `BaseAction` | Uploads a required document for registration |
| `Registration/Actions/VerifyRegistrationAction.php` | `VerifyRegistrationAction` | `BaseAction` | Verifies a student's registration |

## Entities

| File | Class | Extends | Description |
|---|---|---|---|
| `Registration/Entities/RegistrationState.php` | `RegistrationState` | `BaseEntity` | Read-only DTO for registration state |

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `Registration/Enums/AccountApplicationStatus.php` | `AccountApplicationStatus` | `LabelEnum`, `StatusEnum` | Account application lifecycle status |
| `Registration/Enums/RegistrationDocumentStatus.php` | `RegistrationDocumentStatus` | `LabelEnum`, `StatusEnum` | Registration document verification status |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Registration/Livewire/ApplyPage.php` | `ApplyPage` | `Component` | Internship program application page |
| `Registration/Livewire/RegistrationCenter.php` | `RegistrationCenter` | `Component` | Central registration management hub |
| `Registration/Livewire/RegistrationDocumentUpload.php` | `RegistrationDocumentUpload` | `Component` | Document upload for registration |
| `Registration/Livewire/RegistrationVerification.php` | `RegistrationVerification` | `Component` | Admin verification of registrations |
| `Registration/Livewire/RegistrationWizard.php` | `RegistrationWizard` | `Component` | Multi-step registration flow |

### Livewire Form Objects

| File | Class | Extends | Fields | Used By |
|---|---|---|---|---|
| `Registration/Livewire/Forms/AccountApplicationForm.php` | `AccountApplicationForm` | `Form` | name, email, phone, address | `ApplyPage` |
| `Registration/Livewire/Forms/RegistrationWizardForm.php` | `RegistrationWizardForm` | `Form` | internship_id, placement_id, academic_year, proposed_company_name, proposed_company_address | `RegistrationWizard` |

## Models

| File | Class | Extends/Implements | Description |
|---|---|---|---|
| `Registration/Models/AccountApplication.php` | `AccountApplication` | `BaseModel` | Eloquent model for account applications |
| `Registration/Models/Registration.php` | `Registration` | `BaseModel` | Eloquent model for internship registrations |
| `Registration/Models/RegistrationDocument.php` | `RegistrationDocument` | `BaseModel`, `HasMedia` | Eloquent model for registration documents |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Registration/Policies/AccountApplicationPolicy.php` | `AccountApplicationPolicy` | `BasePolicy` | Authorization for account application operations |
| `Registration/Policies/RegistrationPolicy.php` | `RegistrationPolicy` | `BasePolicy` | Authorization for registration operations |
| `Registration/Policies/RegistrationDocumentPolicy.php` | `RegistrationDocumentPolicy` | `BasePolicy` | Authorization for registration document operations |

## Where to Find It

- `app/Domain/Registration/Models/`
- `app/Domain/Registration/Actions/`
- `app/Domain/Registration/Livewire/`

## Dependency Graph

```
Registration Domain
├── Core         → BaseModel, BaseAction, SmartLogger, BasePolicy,
│                   HandlesActionErrors
├── User         → User model (registrant identity)
├── Internship   → Internship records (registration target)
├── Placement    → Placement records (placement context)
├── Mentee       → Mentee records (student registration)
├── Mentor       → Mentor records (mentor registration)
└── School       → School, AcademicYear (institutional context)
```

Consumed by: all operational domains (entry point for internship participation)

