# Chapter 2: Setup Wizard

> **Last updated:** 2026-06-14 **Changes:** sync — initial metadata sync with new format

## Description

The setup wizard is a 6-step form in your browser. It creates your super admin account, enters your
school information, and prepares Internara for first use.

**Before you begin:** Make sure you have the signed URL from the [Installation](01-installation.md)
chapter. If you don't have it, run `php artisan setup:install` on your server to generate one.

---

## Step 1: Welcome & Health Check

The wizard automatically checks your server environment:

- PHP version and required extensions
- File and directory permissions
- Database connection
- Available command-line tools

Items show as **green** (passed), **yellow** (warning), or **red** (failed).

> The wizard cannot proceed until all critical checks pass. If anything is red, fix the issue and
> refresh the page to re-run the audit.

---

## Step 2: Super Admin Account

Create the master administrator account. This account has full access to everything.

| Field            | Required | Notes                                                                 |
| ---------------- | -------- | --------------------------------------------------------------------- |
| Name             | No       | Automatically set to **"Administrator"** — cannot be changed          |
| Username         | No       | Automatically set to **"superadmin"** — cannot be changed             |
| Email            | **Yes**  | Use a real email address you can access                               |
| Password         | **Yes**  | Minimum 8 characters, must include uppercase, lowercase, and a number |
| Confirm Password | **Yes**  | Must match the password above                                         |

> **Why can't I change the name and username?** The super admin identity is locked for security
> reasons. This ensures there is always one known, non-renameable administrative account.

---

## Step 3: School Information

Enter your institution's details:

| Field              | Required | Notes                              |
| ------------------ | -------- | ---------------------------------- |
| School Name        | **Yes**  | Full official name of your school  |
| Institutional Code | **Yes**  | Your school's NPSN or similar code |
| Email              | **Yes**  | Official school email address      |
| Address            | No       | School street address              |
| Phone              | No       | School contact number              |
| Website            | No       | School website URL                 |
| Principal Name     | No       | Name of the head of school         |

---

## Step 4: Department

Create your first department (program keahlian / jurusan).

| Field           | Required | Notes                                                  |
| --------------- | -------- | ------------------------------------------------------ |
| Department Name | **Yes**  | E.g., "Software Engineering", "Network Administration" |
| Description     | No       | Optional details about the department                  |

You can add more departments later from **School → Departments** in the admin panel.

---

## Step 5: Finalize & Confirm

Review everything you entered. Before finishing:

- [ ] **Confirm all information is correct** — check the checkbox
- [ ] **Acknowledge super admin responsibility** — this account controls everything

Click **Finish** to complete the setup. The system will:

1. Save your school information
2. Create the department
3. Create the super admin account
4. Generate a recovery key
5. Lock the setup wizard (it cannot be run again)

---

## Step 6: Recovery Key — Save This!

This is the most important step. You will see a screen with a **64-character recovery key**:

```
╔══════════════════════════════════════════════════════╗
║                   RECOVERY KEY                       ║
║                                                      ║
║   a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3   ║
║                                                      ║
║   ⚠  Save this key somewhere safe and offline.      ║
║   It will NOT be shown again after this screen.      ║
╚══════════════════════════════════════════════════════╝
```

### What is this key for?

If you ever lose the super admin password, this key is the **only way** to regain access. Without
it, you cannot recover the account.

### How to save it

- **Write it down** on paper and store it in a safe place
- **Save it in a password manager**
- **Store it offline** — not on the same server as Internara

The key is also saved automatically to a file on your server at `storage/app/private/.recovery-key`
with restricted permissions.

### What happens next

After 60 seconds, you'll be automatically redirected to the login page. You can click **"Go to
Login"** to skip the wait.

---

## Done! What's Next?

The wizard is complete. Now you need to configure Internara for daily use:

**→ Continue to [Chapter 3: Post-Setup](03-post-setup.md)** — add users, create programs, go live.

---

## Troubleshooting

| Problem                   | Solution                                                                                  |
| ------------------------- | ----------------------------------------------------------------------------------------- |
| Setup URL expired         | Run `php artisan setup:reset-token` on the server                                         |
| Setup page shows 403      | The token is invalid or was already used. Get a fresh signed URL                          |
| Setup page shows 404      | The wizard has already been completed. Use `--force` to reset                             |
| Can't proceed past Step 1 | Check the red items and fix them. Common causes: missing PHP extension, wrong permissions |

---

---

**← Previous:** [Chapter 1: Installation](01-installation.md) **Next →**
[Chapter 3: Post-Setup](03-post-setup.md)
