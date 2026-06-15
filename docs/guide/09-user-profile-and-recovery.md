# Chapter 9: User Profile & Recovery

> **Last updated:** 2026-06-15

This chapter covers how to manage your personal profile, change your password, generate recovery
codes, and use the notification center.

---

## 9.1 Accessing Your Profile

Click your name or avatar in the top-right navigation bar and select **Profile**, or navigate
directly to `/profile`.

Your profile page is divided into three sections:

---

### 9.1.1 Personal Information

The main card shows your editable profile fields. Available fields depend on your role:

**All users can edit:**
- **Name** — your full display name (immutable for Super Admin)
- **Username** — your login identifier (immutable for Super Admin)
- **Email** — your email address
- **Phone** — contact number
- **Address** — your residential address
- **Bio** — a short description about yourself

**Staff only** (Super Admin, Admin, Teacher):
- **Employment Status** — e.g., permanent, contract, temporary
- **Job Title** — your position
- **ID Number** — employee or identification number
- **Competence Field** — your area of expertise

Click **Save** to apply changes. A success message will confirm the update.

---

### 9.1.2 Avatar (Profile Photo)

To upload a profile photo:

1. Click the avatar circle on the profile page
2. Select an image file (PNG, JPEG, or WebP, max 2 MB)
3. The photo uploads automatically

To remove your avatar, click the **Remove** button that appears when you have a photo.

If no avatar is uploaded, your initials are shown instead.

---

### 9.1.3 Changing Your Password

To change your password, use the **Password** card on the profile page:

1. Enter your **current password**
2. Enter your **new password** (minimum 8 characters)
3. Confirm your new password
4. Click **Update Password**

> **Rate limit:** 5 attempts per session. If exceeded, wait before trying again.

---

## 9.2 Recovery Codes

Recovery codes are one-time-use codes that let you log in if you forget your password and cannot
access your email.

### 9.2.1 Generating Recovery Codes

1. Go to **Profile → Recovery Codes** (`/profile/recovery`)
2. Click **Generate**
3. You will see **10 codes** displayed on screen

Each code is a 12-character uppercase alphanumeric string (no hyphens). They look like this:

```
A7K2X9M4Q3P8
```

### 9.2.2 Downloading as PDF

After generating codes, click **Download PDF** to save them as a printable PDF document. Store this
document in a safe place — each code can only be used once.

### 9.2.3 Using a Recovery Code

If you are locked out:

1. Go to the login page and click **Recover Account** (`/recover-account`)
2. Enter your **username**
3. Enter one of your **recovery codes**
4. Enter a **new password**
5. Confirm the password

Your password is reset and you can log in with the new password. The recovery code you used is now
spent and cannot be used again.

> **Rate limit:** 3 recovery attempts per 300 seconds.

### 9.2.4 Regenerating Codes

If you run out of codes or suspect they have been compromised, click **Generate New** to create a
fresh set of 10 codes. Old codes are invalidated.

---

## 9.3 Admin: Recovery Slip Manager

If a user loses access to their account and does not have recovery codes, an administrator can
generate a recovery slip on their behalf.

1. Go to **Admin → Recovery Slips** (`/admin/recovery-slips`)
2. Search for the user by name, username, or email
3. Select the user from the search results
4. Click **Generate Recovery Slip**
5. Share the recovery codes with the user through a secure channel

The user can then use the codes at `/recover-account` as described above.

---

## 9.4 Notification Center

### 9.4.1 Accessing Notifications

Click the **bell icon** in the top navigation bar to view your notification count. Click the icon
or navigate to `/notifications` to open the full notification center.

### 9.4.2 Managing Notifications

The notification center shows your notifications in a paginated table with the following features:

- **Search** — filter by notification title or message content
- **Filter** — view All, Unread, or Read notifications
- **Sort** — by date received

### 9.4.3 Reading a Notification

Click **View** on any notification to open the full message in a modal. The notification is
automatically marked as read.

### 9.4.4 Marking Notifications as Read

- **Single:** Click the checkmark icon next to a notification
- **Selected:** Select multiple notifications and click **Mark as Read**
- **All:** Click **Mark All as Read** at the top of the list

### 9.4.5 Deleting Notifications

Select one or more notifications and click **Delete**. Confirm the action in the dialog.

---

## 9.5 Profile & Notification Widgets

Your profile summary card appears on dashboards and throughout the system, showing:

- Your avatar or initials
- Your name and role
- An **Edit Profile** link

---

**← Previous:** [Login & Dashboard](07-login-and-dashboard.md)
**Next →** [System Settings & Backups](10-system-settings-and-backups.md)
