# Chapter 11: Institution & Academics

> **Last updated:** 2026-06-16 **Changes:** sync — initial metadata sync with new format

## Description

This chapter covers how to manage your school's institutional profile, academic departments
(jurusan), and academic years (tahun ajaran). These three elements form the foundation that all
other features — programs, enrollments, assessments — are built on.

---

## 11.1 School Profile

Your school's identity is stored as system settings and managed through the School Editor. Only the
Super Admin can edit the school profile.

Navigate to **School → School Profile** or go directly to `/academics/school`.

### 11.1.1 Profile Fields

| Field              | Key                         | Description                                |
| ------------------ | --------------------------- | ------------------------------------------ |
| **School Name**    | `school.name`               | Full official name (e.g. "SMKN 1 Jakarta") |
| **NPSN**           | `school.institutional_code` | National school identification number      |
| **Email**          | `school.email`              | General school contact email               |
| **Phone**          | `school.phone`              | School phone number                        |
| **Address**        | `school.address`            | Full street address                        |
| **Website**        | `school.website`            | School website URL                         |
| **Principal Name** | `school.principal_name`     | Name of the head of school                 |

These values are referenced throughout the system — on certificates, reports, and official
documents.

### 11.1.2 Editing the Profile

1. Go to **School → School Profile**
2. Update any field
3. Click **Save** — changes apply immediately

---

## 11.2 Departments (Jurusan)

Departments represent your school's study programs or majors (jurusan). Teachers and students are
assigned to departments, and internship programs can be scoped by department.

Navigate to **School → Departments** or go directly to `/academics/departments`.

### 11.2.1 Adding a Department

1. Click **Add Department**
2. Fill in the fields:

| Field           | Description                           | Example                                    |
| --------------- | ------------------------------------- | ------------------------------------------ |
| **Name**        | Full department name                  | Software Engineering                       |
| **Description** | Optional details about the department | Includes mobile and web development tracks |

3. Click **Save**

### 11.2.2 Editing a Department

1. Find the department in the list
2. Click the **Edit** button
3. Update the fields
4. Click **Save**

### 11.2.3 Deleting a Department

Internara protects against accidental data loss. You cannot delete a department if it has:

- Active internship programs assigned to it
- Active users (teachers or students) assigned to it
- Active placement records

If you attempt to delete a protected department, the system shows a detailed error listing exactly
what blocks the deletion. You must resolve these dependencies first.

### 11.2.4 Default Department

During setup, the wizard creates one default department. You can rename or repurpose it later.

---

## 11.3 Academic Years (Tahun Ajaran)

Academic years define the school calendar periods that scope programs, enrollments, and reports.

Navigate to **School → Academic Years** or go directly to `/academics/academic-years`.

### 11.3.1 Adding an Academic Year

1. Click **Add Academic Year**
2. Fill in the fields:

| Field          | Description                         | Example                     |
| -------------- | ----------------------------------- | --------------------------- |
| **Name**       | Display name for the year           | 2025/2026                   |
| **Start Date** | First day of the academic year      | 14 July 2025                |
| **End Date**   | Last day of the academic year       | 26 June 2026                |
| **Active**     | Toggle to make this the active year | Only one year can be active |

3. Click **Save**

### 11.3.2 The Active Year Singleton

Exactly **one** academic year can be active at any time. Activating a new year automatically
deactivates the previously active year. This invariant is enforced at both the application and
database level.

The active year is used as the default for:

- New internship programs
- Enrollment periods
- Report scoping
- Dashboard data filtering

### 11.3.3 Activating a Different Year

1. Find the year you want to activate
2. Click **Activate**
3. The previously active year is deactivated automatically

You can also activate a year from the command line:

```bash
php artisan academics:activate-year "2025/2026"
```

### 11.3.4 Editing an Academic Year

1. Find the year in the list
2. Click the **Edit** button
3. Update the fields
4. Click **Save**

Note: You cannot make a year active and inactive at the same time through the edit form. Use the
**Activate** button to switch the active year.

### 11.3.5 Date Validation Rules

- **End date must be after start date**
- **Years cannot overlap** — the system checks for conflicting date ranges
- Changing an active year's dates may affect existing programs and enrollments

---

## 11.4 Troubleshooting

### Cannot delete a department

If the system refuses to delete a department, check for:

- Active internship programs using this department
- Active users assigned to this department
- Active placement records

The error message lists exactly what needs to be removed first. Reassign or remove the blocking
records, then try again.

### Cannot activate an academic year

Verify that the year's date range does not overlap with another existing year. Each year must have a
unique date range — no two years can share periods.

### School profile changes not visible

The school profile is cached for performance. Changes take effect immediately, but if you are using
a page-level cache (e.g. Varnish, CloudFlare), you may need to purge the cache to see updates.

### Academic year not appearing in dropdowns

The system defaults to the **active** academic year. If a year is not marked active, it will not
appear as the default in new programs or enrollments. Activate the year from the Academic Years
page.

---

**← Previous: [Chapter 10: System Settings & Backups](10-system-settings-and-backups.md)**
