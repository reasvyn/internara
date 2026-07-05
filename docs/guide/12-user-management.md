# Chapter 12: User Management

> **Last updated:** 2026-06-16 **Changes:** sync — initial metadata sync with new format

## Description

Internara provides five user management interfaces, each tailored to a specific role. Navigate to
**Admin → [Role]** or use the corresponding URL path.

| Manager                | Role                   | Path                    |
| ---------------------- | ---------------------- | ----------------------- |
| **User Manager**       | All roles              | `/sysadmin/users`       |
| **Admin Manager**      | `super_admin`, `admin` | `/sysadmin/admins`      |
| **Student Manager**    | `student`              | `/sysadmin/students`    |
| **Teacher Manager**    | `teacher`              | `/sysadmin/teachers`    |
| **Supervisor Manager** | `supervisor`           | `/sysadmin/supervisors` |

---

## 12.1 Creating a User

The process is similar across all managers. Open the relevant manager, click **New**, and fill in
the form. Required fields vary by role:

| Role           | Additional Fields               |
| -------------- | ------------------------------- |
| **Student**    | NISN, NIS, Department (jurusan) |
| **Teacher**    | ID Number (NIP/NUPTK)           |
| **Supervisor** | Phone, Company assignment       |

After saving, Internara generates an **account slip** with the user's initial credentials. Download
and share it with the user.

> Creating a user through the **User Manager** allows setting a full profile — address, bio,
> emergency contact, and multiple role assignments.

## 12.2 Editing a User

Find the user in the table, click the edit button, update the fields, and save.

Super admin accounts are protected — they cannot be edited or deleted from any manager. Use the
**Admin Manager** to manage non-super admin accounts.

## 12.3 Deleting a User

Each manager supports single deletion and bulk deletion via checkbox selection.

**Protected accounts:**

- Super admin accounts cannot be deleted
- You cannot delete your own account
- Users with active enrollments may be blocked from deletion

## 12.4 Bulk Operations (User Manager)

Select multiple users in the User Manager to access bulk actions:

- **Download Account Slips** — print activation slips for multiple users at once
- **Lock / Unlock Selected** — suspend or reactivate accounts in bulk
- **Export Selected** — download a CSV of selected users

## 12.5 Importing Users

The User Manager supports batch creation via CSV upload. Click the menu next to **New User**, select
**Import**, and upload a file with columns: `name`, `email`, `phone`. Rows with existing email
addresses are skipped. A summary shows the result.

Download the **Template** from the same menu for the correct format.

## 12.6 Archiving Students

The Student Manager includes an **Archive Filtered** action to mass-archive students matching the
current search and filter criteria — useful for graduating cohorts at the end of an academic year.

---

**← Previous: [Chapter 11: Institution & Academics](11-institution-and-academics.md)**
