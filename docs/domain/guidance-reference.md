# Guidance — API Reference

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Aggregate-rooted layout mapping for the Guidance domain

This reference defines the structured aggregates and code layout within the **Guidance** domain.

---

## 1. Handbook Aggregate
Manages procedure guides, Markdown rendering, PDF attachments, and immutable reader acknowledgments.

- **Eloquent Models**:
  - `Handbook` (`app/Domain/Guidance/Models/Handbook.php`)
  - `HandbookAcknowledgement` (`app/Domain/Guidance/Models/HandbookAcknowledgement.php`)
- **Policies**:
  - `HandbookPolicy` (`app/Domain/Guidance/Policies/HandbookPolicy.php`)
- **Command Actions**:
  - `CreateHandbookAction` (`app/Domain/Guidance/Actions/CreateHandbookAction.php`)
  - `UpdateHandbookAction` (`app/Domain/Guidance/Actions/UpdateHandbookAction.php`)
  - `DeleteHandbookAction` (`app/Domain/Guidance/Actions/DeleteHandbookAction.php`)
  - `AcknowledgeHandbookAction` (`app/Domain/Guidance/Actions/AcknowledgeHandbookAction.php`)
- **Livewire UI Components**:
  - `HandbookManager` (`app/Domain/Guidance/Livewire/HandbookManager.php`)
  - `HandbookIndex` (`app/Domain/Guidance/Livewire/HandbookIndex.php`)
- **Form Objects**:
  - `HandbookForm` (`app/Domain/Guidance/Livewire/Forms/HandbookForm.php`)
- **Entities (Domain Rules)**:
  - `HandbookPublishState` (`app/Domain/Guidance/Entities/HandbookPublishState.php`)

---

## 2. Mentee Aggregate
Manages student mentee activations, internship remaining days computation, and operational progress rules.

- **Eloquent Models**:
  - `Mentee` (`app/Domain/Guidance/Models/Mentee.php`)
- **Policies**:
  - `MenteePolicy` (`app/Domain/Guidance/Policies/MenteePolicy.php`)
- **Command Actions**:
  - `CreateMenteeAction` (`app/Domain/Guidance/Actions/CreateMenteeAction.php`)
  - `UpdateMenteeAction` (`app/Domain/Guidance/Actions/UpdateMenteeAction.php`)
  - `DeleteMenteeAction` (`app/Domain/Guidance/Actions/DeleteMenteeAction.php`)
- **Livewire UI Components**:
  - `MenteeManager` (`app/Domain/Guidance/Livewire/MenteeManager.php`)
- **Form Objects**:
  - `MenteeForm` (`app/Domain/Guidance/Livewire/Forms/MenteeForm.php`)
- **Entities (Domain Rules)**:
  - `MenteeState` (`app/Domain/Guidance/Entities/MenteeState.php`)

---

## 3. Mentor Aggregate
Manages mentor assignments, industrial or academic classifications, and mentor rating evaluations.

- **Eloquent Models**:
  - `Mentor` (`app/Domain/Guidance/Models/Mentor.php`)
- **Policies**:
  - `MentorPolicy` (`app/Domain/Guidance/Policies/MentorPolicy.php`)
- **Command Actions**:
  - `CreateMentorAction` (`app/Domain/Guidance/Actions/CreateMentorAction.php`)
  - `UpdateMentorAction` (`app/Domain/Guidance/Actions/UpdateMentorAction.php`)
  - `DeleteMentorAction` (`app/Domain/Guidance/Actions/DeleteMentorAction.php`)
  - `ToggleMentorActiveAction` (`app/Domain/Guidance/Actions/ToggleMentorActiveAction.php`)
  - `CreateMentorProfileAction` (`app/Domain/Guidance/Actions/CreateMentorProfileAction.php`)
  - `UpdateMentorProfileAction` (`app/Domain/Guidance/Actions/UpdateMentorProfileAction.php`)
- **Livewire UI Components**:
  - `MentorManager` (`app/Domain/Guidance/Livewire/MentorManager.php`)
  - `MentorProfileManager` (`app/Domain/Guidance/Livewire/MentorProfileManager.php`)
  - `EvaluateMentor` (`app/Domain/Guidance/Livewire/EvaluateMentor.php`)
  - `AssessInternship` (`app/Domain/Guidance/Livewire/AssessInternship.php`)
- **Form Objects**:
  - `MentorForm` (`app/Domain/Guidance/Livewire/Forms/MentorForm.php`)
- **Entities (Domain Rules)**:
  - `MentorRole` (`app/Domain/Guidance/Entities/MentorRole.php`)

---

## 4. SupervisionLog Aggregate
Orchestrates on-site visitations logs, phone logs, and verification checks.

- **Eloquent Models**:
  - `SupervisionLog` (`app/Domain/Guidance/Models/SupervisionLog.php`)
- **Policies**:
  - `SupervisionLogPolicy` (`app/Domain/Guidance/Policies/SupervisionLogPolicy.php`)
- **Command Actions**:
  - `CreateSupervisionLogAction` (`app/Domain/Guidance/Actions/CreateSupervisionLogAction.php`)
  - `VerifySupervisionLogAction` (`app/Domain/Guidance/Actions/VerifySupervisionLogAction.php`)
- **Livewire UI Components**:
  - `SupervisionManager` (`app/Domain/Guidance/Livewire/Supervision/SupervisionManager.php`)
  - `SupervisorLogManager` (`app/Domain/Guidance/Livewire/Supervision/SupervisorLogManager.php`)
- **Entities (Domain Rules)**:
  - `SupervisionStatus` (`app/Domain/Guidance/Entities/SupervisionStatus.php`)
- **Enums**:
  - `SupervisionLogStatus` (`app/Domain/Guidance/Enums/SupervisionLogStatus.php`)
  - `SupervisionType` (`app/Domain/Guidance/Enums/SupervisionType.php`)
