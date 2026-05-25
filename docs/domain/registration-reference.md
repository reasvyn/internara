# Registration — API Reference

Total: 18 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Registration/Actions/ApplyAccountAction.php` | `ApplyAccountAction` | `BaseAction` | Submits a new internship account application |
| `Registration/Actions/ApproveAccountApplicationAction.php` | `ApproveAccountApplicationAction` | `BaseAction` | Approves a pending account application |
| `Registration/Actions/RejectAccountApplicationAction.php` | `RejectAccountApplicationAction` | `BaseAction` | Rejects an account application with reason |
| `Registration/Actions/RegisterInternshipAction.php` | `RegisterInternshipAction` | `BaseAction` | Registers a student for an internship program |
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
| `Registration/Livewire/AccountApplicationForm.php` | `AccountApplicationForm` | `Component` | Student account application form |
| `Registration/Livewire/RegistrationCenter.php` | `RegistrationCenter` | `Component` | Central registration management hub |
| `Registration/Livewire/RegistrationDocumentUpload.php` | `RegistrationDocumentUpload` | `Component` | Document upload for registration |
| `Registration/Livewire/RegistrationVerification.php` | `RegistrationVerification` | `Component` | Admin verification of registrations |
| `Registration/Livewire/RegistrationWizard.php` | `RegistrationWizard` | `Component` | Multi-step registration flow |

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
