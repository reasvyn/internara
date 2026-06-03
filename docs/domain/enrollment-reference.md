# Enrollment — API Reference

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Aggregate-rooted layout mapping for the Enrollment domain

This reference defines the structured aggregates and code layout within the **Enrollment** domain.

---

## 1. AccountApplication Aggregate
Manages unauthenticated user applications for program admission and user credentials auto-provisioning upon approval.

- **Eloquent Models**:
  - `AccountApplication` (`app/Domain/Enrollment/Models/AccountApplication.php`)
- **Policies**:
  - `AccountApplicationPolicy` (`app/Domain/Enrollment/Policies/AccountApplicationPolicy.php`)
- **Command Actions**:
  - `ApplyAccountAction` (`app/Domain/Enrollment/Actions/ApplyAccountAction.php`)
  - `ApproveAccountApplicationAction` (`app/Domain/Enrollment/Actions/ApproveAccountApplicationAction.php`)
  - `RejectAccountApplicationAction` (`app/Domain/Enrollment/Actions/RejectAccountApplicationAction.php`)
- **Livewire UI Components**:
  - `ApplyPage` (`app/Domain/Enrollment/Livewire/ApplyPage.php`)
- **Form Objects**:
  - `AccountApplicationForm` (`app/Domain/Enrollment/Livewire/Forms/AccountApplicationForm.php`)
- **Enums**:
  - `AccountApplicationStatus` (`app/Domain/Enrollment/Enums/AccountApplicationStatus.php`)

---

## 2. Registration Aggregate
Handles program registrations, required uploads, checksheet validations, and student activation milestones.

- **Eloquent Models**:
  - `Registration` (`app/Domain/Enrollment/Models/Registration.php`)
  - `RegistrationDocument` (`app/Domain/Enrollment/Models/RegistrationDocument.php`)
- **Policies**:
  - `RegistrationPolicy` (`app/Domain/Enrollment/Policies/RegistrationPolicy.php`)
  - `RegistrationDocumentPolicy` (`app/Domain/Enrollment/Policies/RegistrationDocumentPolicy.php`)
- **Command Actions**:
  - `RegisterInternshipAction` (`app/Domain/Enrollment/Actions/RegisterInternshipAction.php`)
  - `UploadRegistrationDocumentAction` (`app/Domain/Enrollment/Actions/UploadRegistrationDocumentAction.php`)
  - `VerifyRegistrationAction` (`app/Domain/Enrollment/Actions/VerifyRegistrationAction.php`)
- **Livewire UI Components**:
  - `RegistrationWizard` (`app/Domain/Enrollment/Livewire/RegistrationWizard.php`)
  - `RegistrationCenter` (`app/Domain/Enrollment/Livewire/RegistrationCenter.php`)
  - `RegistrationDocumentUpload` (`app/Domain/Enrollment/Livewire/RegistrationDocumentUpload.php`)
  - `RegistrationVerification` (`app/Domain/Enrollment/Livewire/RegistrationVerification.php`)
- **Form Objects**:
  - `RegistrationWizardForm` (`app/Domain/Enrollment/Livewire/Forms/RegistrationWizardForm.php`)
- **Entities (Domain Rules)**:
  - `RegistrationState` (`app/Domain/Enrollment/Entities/RegistrationState.php`)
- **Enums**:
  - `RegistrationDocumentStatus` (`app/Domain/Enrollment/Enums/RegistrationDocumentStatus.php`)

---

## 3. Placement Aggregate
Governs internship placements slots at host companies and handles quota limits and assignments.

- **Eloquent Models**:
  - `Placement` (`app/Domain/Enrollment/Models/Placement.php`)
- **Policies**:
  - `PlacementPolicy` (`app/Domain/Enrollment/Policies/PlacementPolicy.php`)
- **Command Actions**:
  - `CreatePlacementAction` (`app/Domain/Enrollment/Actions/CreatePlacementAction.php`)
  - `UpdatePlacementAction` (`app/Domain/Enrollment/Actions/UpdatePlacementAction.php`)
  - `DeletePlacementAction` (`app/Domain/Enrollment/Actions/DeletePlacementAction.php`)
  - `DirectPlacementAction` (`app/Domain/Enrollment/Actions/DirectPlacementAction.php`)
- **Livewire UI Components**:
  - `PlacementIndex` (`app/Domain/Enrollment/Livewire/PlacementIndex.php`)
  - `DirectPlacementManager` (`app/Domain/Enrollment/Livewire/DirectPlacementManager.php`)
- **Form Objects**:
  - `PlacementForm` (`app/Domain/Enrollment/Livewire/Forms/PlacementForm.php`)
  - `DirectPlacementForm` (`app/Domain/Enrollment/Livewire/Forms/DirectPlacementForm.php`)
- **Entities (Domain Rules)**:
  - `PlacementState` (`app/Domain/Enrollment/Entities/PlacementState.php`)
  - `PlacementCapacity` (`app/Domain/Enrollment/Entities/PlacementCapacity.php`)

---

## 4. PlacementChange Aggregate
Orchestrates placement swaps, student requests, and quota releases.

- **Eloquent Models**:
  - `PlacementChangeRequest` (`app/Domain/Enrollment/Models/PlacementChangeRequest.php`)
- **Policies**:
  - `PlacementChangeRequestPolicy` (`app/Domain/Enrollment/Policies/PlacementChangeRequestPolicy.php`)
- **Command Actions**:
  - `RequestPlacementChangeAction` (`app/Domain/Enrollment/Actions/RequestPlacementChangeAction.php`)
  - `ApprovePlacementChangeAction` (`app/Domain/Enrollment/Actions/ApprovePlacementChangeAction.php`)
  - `RejectPlacementChangeAction` (`app/Domain/Enrollment/Actions/RejectPlacementChangeAction.php`)
- **Livewire UI Components**:
  - `StudentPlacementChangeRequest` (`app/Domain/Enrollment/Livewire/StudentPlacementChangeRequest.php`)
  - `PlacementChangeManager` (`app/Domain/Enrollment/Livewire/PlacementChangeManager.php`)
- **Form Objects**:
  - `PlacementChangeForm` (`app/Domain/Enrollment/Livewire/Forms/PlacementChangeForm.php`)
- **Enums**:
  - `PlacementChangeStatus` (`app/Domain/Enrollment/Enums/PlacementChangeStatus.php`)
