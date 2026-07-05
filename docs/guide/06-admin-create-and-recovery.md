# Chapter 6: Admin Accounts & Recovery

> **Last updated:** 2026-06-14 **Changes:** sync — initial metadata sync with new format

## Description

This chapter covers how to create super admin accounts and regain access if you lose the password.

---

## 6.1 The Super Admin Account

The super admin is the master account with full access to every part of Internara. There is only one
super admin, and it has special protections:

| Property     | Value                                                 |
| ------------ | ----------------------------------------------------- |
| **Name**     | `Administrator` — cannot be changed                   |
| **Username** | `superadmin` — cannot be changed                      |
| **Status**   | `PROTECTED` — cannot be deleted, locked, or suspended |
| **Access**   | Bypasses all permission checks                        |

### Where Is It Created?

During the [Setup Wizard](02-setup-wizard.md) (Step 2), you provide an email and password. The super
admin is created automatically with the fixed name and username above.

You can also create it after installation using the command line (see below).

---

## 6.2 Creating an Admin from the Command Line

If you need to create the super admin outside the setup wizard (e.g., after a fresh deployment
without the wizard), use the `admin:create` command:

```bash
php artisan admin:create
```

The command will prompt you for:

1. **Email address** — the login email for the super admin
2. **Password** — minimum 8 characters
3. **Confirm password** — must match

You can also provide them directly:

```bash
php artisan admin:create admin@sekolah.sch.id
```

> **Note:** `admin:create` only works if no super admin exists yet. If one already exists, the
> command will abort.

### What Happens After Creation

After the admin is created, the command displays a **recovery key** — a 64-character random string:

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

**Save this key.** You will need it to recover access if the password is lost.

---

## 6.3 Understanding the Recovery Key

The recovery key is the **backup plan** for your super admin account.

### How It Works

1. When the super admin is created, a random 64-character key is generated
2. A **bcrypt hash** of this key is stored in the database (never the plaintext)
3. The plaintext key is saved to a file on your server at: `storage/app/private/.recovery-key`
4. This file is readable only by the server owner (`chmod 0600`)

### Where to Find It

```bash
# Show the recovery key file path
php artisan admin:recovery-path

# Display the stored recovery key (after confirmation)
php artisan admin:recovery-show
```

### When You Need It

You'll need the recovery key if:

- You forget the super admin password
- The super admin account becomes locked
- Another administrator leaves and you need to rotate access

---

## 6.4 Recovering Admin Access

If you lose the super admin password, use the `admin:recover` command:

```bash
php artisan admin:recover
```

### Step-by-Step Recovery

**Step 1 — Verify the Recovery Key**

The command looks for the recovery key in two places:

1. The file `storage/app/private/.recovery-key` (auto-detected)
2. Or provide it manually: `php artisan admin:recover --key=your-64-char-key`

The key is verified against the stored hash in the database.

**Step 2 — OTP Verification (Production Only)**

On production servers, a 6-digit OTP is sent to the admin's email address. Enter the OTP to proceed.
This prevents unauthorized recovery even if someone has the recovery key file.

**Step 3 — Enter New Password**

You'll be prompted for:

- **Email** — the super admin's email address
- **New password** — minimum 8 characters
- **Confirm password** — must match

**Step 4 — Confirm Recovery**

Type the admin's email address to confirm you want to proceed. All other super admins will be
notified about the recovery.

**Step 5 — Save the New Recovery Key**

A new recovery key is generated after the password is reset. **Save this key** — the previous key is
no longer valid.

---

## 6.5 Recovery Key File Management

### View the File Path

```bash
php artisan admin:recovery-path
```

Shows the expected path and whether the file exists.

### Display the Key

```bash
php artisan admin:recovery-show
```

Shows the stored recovery key. You'll need to confirm before it's displayed. This action is logged
as a security event.

### Regenerate the File

If the recovery key file is missing but you still have the key, rewrite it:

```bash
php artisan admin:recover --key=your-64-char-key --regenerate-file
```

This writes the key back to `storage/app/private/.recovery-key`.

---

## 6.6 Quick Reference

### Commands

| Command                                       | Purpose                                |
| --------------------------------------------- | -------------------------------------- |
| `php artisan admin:create`                    | Create super admin (no existing admin) |
| `php artisan admin:recover`                   | Reset super admin password             |
| `php artisan admin:recover --key=<key>`       | Recover with explicit key              |
| `php artisan admin:recover --regenerate-file` | Rewrite recovery key file from `--key` |
| `php artisan admin:recovery-path`             | Show recovery key file location        |
| `php artisan admin:recovery-show`             | Display stored recovery key            |

### Security Notes

- The recovery key is **never stored in plaintext** in the database — only a bcrypt hash
- Recovery attempts are rate-limited: **3 attempts per 15 minutes**
- In production, an OTP is emailed as a second factor
- All recovery attempts are logged with PII masking
- Other super admins are notified when a recovery happens
- The recovery key is **rotated** on every successful recovery

---

---

**← Previous:** [Chapter 5: Upgrading Internara](05-upgrading-from-previous.md) **Next →**
[Back to Manual Index](index.md)
