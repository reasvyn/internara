# Phase 1: User Onboarding (Step Two)

With your school's foundation set, you must now bring the people into the system. Internara
orchestrates the lifecycle of accounts for Teachers, Students, and Mentors, ensuring everyone starts
with the correct permissions and localized credentials.

---

## 1. Onboarding Instructors (Teachers)

Teachers are the "Supervisors" who will monitor student progress and verify competency.

- **Action**: Navigate to **User Management** -> **Teachers**.
- **Checklist**:
    - [ ] Create accounts for all department supervisors.
    - [ ] **Department Assignment**: Ensure each teacher is assigned to their correct Academic
          Department.
- **Why?**: Only teachers registered in the system can be allocated as supervisors for students
  during the placement phase.

## 2. Onboarding the Student Cohort

This is usually the largest data operation. We recommend using **Batch Onboarding** for entire
classes.

- **Action**: Navigate to **User Management** -> **Students** (or `System Settings` ->
  `Onboarding`).
- **Options**:
    - **Mass Import (Recommended)**: Download the CSV template, populate it with NISN, emails, and
      names, then upload it back.
    - **Single Registration**: For late entries or small groups.
- **Onboarding Checklist**:
    - [ ] Download the authoritative **CSV Template**.
    - [ ] Validate student email addresses (used for credential delivery).
    - [ ] Run the **Import Wizard**.
- **Why?**: Batch onboarding ensures that hundreds of accounts are created with 100% accuracy and
  assigned the correct roles automatically (**S3 - Scalable**).

## 3. Credential Delivery & Security

Once accounts are created, Internara handles the secure distribution of initial access.

- **Automatic Notifications**: Every new user will receive a **Welcome Email** containing their
  temporary credentials and a link to log in.
- **Security Check**:
    - [ ] Advise teachers and students to change their temporary passwords upon first login.
- **Why?**: Centralized delivery prevents insecure password sharing via manual lists (**S1 -
  Secure**).

---

**Next Step:** [Phase 1: Data Management (Step Three)](data-management.md)
