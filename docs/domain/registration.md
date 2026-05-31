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

## Domain Boundary

The Registration domain owns the enrollment gateway — the process by which students enter work placement programs. It handles guest applications where prospective students can submit their personal data, school information, and program preferences without having an account in the system. It provides a registration center where existing users can browse programs currently accepting registrations, and a multi-step registration wizard that guides authenticated students through program selection, placement choice, document upload, and final review before submission. Document requirements are tied to specific internship programs, and each required document must be uploaded before registration can be verified.

Registration does not own user identity or profile data (User), program definitions or requirements (Internship), placement slot allocation (Placement), or authentication (Auth). It orchestrates the enrollment flow — collecting data, verifying documents, and triggering downstream actions — but delegates user creation, mentee record provisioning, and role assignment to their respective domains.

The domain depends on Internship for program and requirement definitions, on Placement for slot availability, on User for identity creation during guest-to-student conversion, and on Auth for role assignment. Registration is the gateway that connects an external person to the internal program structure, but it does not own the data it connects.

---

## Key Features

- Allow guest users to submit an enrollment application with personal data, school information, and program preferences without logging in.
- Browse a listing of programs that are currently accepting student registrations.
- Walk authenticated students through a multi-step wizard to select a program, choose a placement slot, review details, and submit.
- Upload required documents per program-specified document requirements as part of the registration.
- Verify pending registrations and assign placement slots and mentors before activating the enrollment.
- Approve or reject guest applications, auto-creating user, mentee, and registration records on approval.
- Progress through a visual multi-step registration wizard with a progress bar and step validation at each stage.
- Upload required documents via drag and drop on each document requirement with file type and size validation.
- Preview uploaded documents before final submission with the ability to replace them.
- Review all entered data on a summary step before confirming the registration submission.
- View the current registration status with a clear color-coded indicator for pending, approved, or rejected.
