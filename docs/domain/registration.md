# Registration Domain

## Purpose

Registration is the gateway — student enrollment into work placement programs. Handles
guest applications, student registration wizard, and document verification.

---

## Design Principles

### 1. Guest-to-Student Flow

Guest users can apply without an account. On approval, the system auto-creates User + Mentee +
Registration records. This enables students to apply before having system access.

### 2. Document Verification

Required documents are verified by admin before registration is approved. Each document
requirement is tied to an internship program.

### 3. Registration Lifecycle

Applications flow through: PENDING → APPROVED/REJECTED. Approved registrations activate
the student's mentee status and enable daily operations.

---

## Models

| Model | Key Fields |
|---|---|
| `Registration` | mentee_id, internship_id, placement_id, status |
| `AccountApplication` | personal data, school info, internship preferences, status |
| `RegistrationDocument` | registration_id, document_requirement_id, status |

## Actions

| Action | Type |
|---|---|
| `ApplyAccountAction` | Command |
| `RegisterInternshipAction` | Command |
| `ApproveAccountApplicationAction` | Command |
| `RejectAccountApplicationAction` | Command |
| `VerifyRegistrationAction` | Command |
| `UploadRegistrationDocumentAction` | Command |

## Where to Find It

- `app/Domain/Registration/Models/`
- `app/Domain/Registration/Actions/`
- `app/Domain/Registration/Livewire/`
