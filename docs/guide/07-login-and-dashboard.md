# Chapter 7: Login & Dashboard

> **Last updated:** 2026-06-15

This chapter covers how to log into Internara, what you'll see on your dashboard, and how to
navigate the application based on your role.

---

## 7.1 Logging In

### The Login Page

Navigate to `/login` on your Internara installation. You'll see a login form with three fields:

- **Email or Username** — Enter the email address or username your administrator provided
- **Password** — Your account password
- **Remember me** — Optional, keeps you logged in longer

### Login Rules

| Rule | Limit |
|------|-------|
| Failed attempts before lockout | 5 attempts per 60 seconds |
| Auto-lock threshold | 10 failed attempts triggers escalating lockout |
| Lockout duration | Doubles with each additional attempt: 20s, 40s, 80s... |

### What Can Prevent Login?

Your account may be blocked from logging in if:

- **Account locked** — An administrator has locked your account
- **Account inactive** — The account has a status that does not allow login
- **Setup required** — The account is marked as requiring initial setup (contact your administrator)

If you cannot log in, contact your system administrator or use the **Forgot Password** or
**Recover Account** options on the login page.

---

## 7.2 Forgot Password

If you've forgotten your password:

1. Click **Forgot Password** on the login page
2. Enter your email address
3. Check your email for a password reset link (may take a few minutes)
4. Click the link and follow the instructions to set a new password

> **Rate limit:** 3 attempts per email per 60 minutes. If exceeded, wait before trying again.

---

## 7.3 Account Recovery

If you have lost access to your email and cannot reset your password, account recovery uses
pre-generated recovery codes.

### If You Have Recovery Codes

1. Click **Recover Account** on the login page
2. Enter your username and one of your recovery codes
3. Set a new password

### If You Don't Have Recovery Codes

Only an administrator (super admin or admin) can generate a recovery slip for you:

1. Ask your administrator to visit **Admin → Recovery Slips**
2. They search for your account and generate a recovery slip
3. The administrator gives you the recovery codes
4. Use the recovery codes at `/recover-account`

### Managing Your Recovery Codes

Once logged in, you can manage your recovery codes:

1. Go to your **Profile → Recovery Codes** (`/profile/recovery`)
2. View, download as PDF, or regenerate your codes
3. Store the codes in a safe place

> **Rate limit:** 3 recovery attempts per 300 seconds.

---

## 7.4 Account Activation (First-Time Users)

If your account was created by an administrator with a **PROVISIONED** status, you need to activate
it before your first login:

1. Check your email for an activation link, or visit `/activate`
2. Enter your email address and the activation code provided by your administrator
3. Create your password (minimum 8 characters)
4. You'll be automatically logged in and redirected to your dashboard

> **Note:** Activation codes are 16–19 characters long. The code input field accepts codes of this
> length. If your code appears shorter or longer, contact your administrator for a new one.

---

## 7.5 After Login — The Dashboard

When you log in, you're redirected to your role-specific dashboard. Internara has four dashboards:

| Dashboard | For Roles |
|-----------|-----------|
| Admin Dashboard | Super Admin, Admin |
| Teacher Dashboard | Teacher |
| Supervisor Dashboard | Supervisor |
| Student Dashboard | Student |

### 7.5.1 Admin Dashboard

The admin dashboard is the most data-rich view. It shows:

**People Overview** — 6 stat cards at the top:
- Total Students, Instructors, Supervisors, Departments, Companies, Active Internships

**PKL Funnel** — A visual pipeline showing student progression:
```
Students → Registered → Placed → Active → Completed
```
Each stage shows the count and drop-off percentage. Below the funnel:
- **Absorption rate** — percentage of students placed
- **Completion rate** — percentage of placed students who completed
- **Bottleneck** — where students are getting stuck (high = problem area)

**3-Column Metrics:**
- **Registration funnel** — total, active, completed registrations
- **Activity funnel** — attendance verification %, logbook verification %, pending logbooks
- **Completion funnel** — placement fill %, certificates issued %, active companies, partnerships

**System Readiness** — Health check indicators for:
- Database, Mail, Cache, Queue, Storage

**Recent Activity** — Latest system activity log entries

**Quick Links:**
- Edit Profile, Recovery Codes, Notifications
- System Settings (Super Admin only)

### 7.5.2 Student Dashboard

If you are registered for an internship, you'll see your company name, position, and batch at the
top. If you haven't registered yet, you'll see a message guiding you to register.

**Action Buttons:**
- Write Journal — record daily activities
- Clock In / Out — mark attendance
- My Assignments — view and submit tasks
- Request Absence — submit absence requests
- My Documents — upload required documents
- Handbooks — read and acknowledge handbooks

**Progress Widgets:**
- **Journal Verification** — how many journals have been verified (with progress bar)
- **Attendance Percentage** — your attendance rate
- **Assignments Completed** — submitted vs total assignments
- **Handbook Acknowledgements** — handbooks read vs total

**Quick Links:** Edit Profile, Recovery Codes, Notifications, View Evaluations

### 7.5.3 Teacher Dashboard

**Stat Cards:**
- Supervised Students, Pending Journals, Active Companies
- Ungraded Submissions, Supervision Logs, Unresolved Incidents

**Action Buttons:**
- Verify Logbooks — review and verify student journal entries
- Grade Assignments — score student submissions
- Supervision Logs — document mentoring sessions

**Quick Links:** Edit Profile, Recovery Codes, Notifications, Supervision Logs

### 7.5.4 Supervisor Dashboard

**Stat Cards:**
- Active Interns, Pending Evaluations, Verified Journals
- Pending Journals, Pending Attendance

**Verification Queue** — List of journal entries awaiting your review

**Action Buttons:**
- Verify Logbooks — review and verify student journal entries
- Submit Evaluation — complete student evaluations

**Quick Links:** Edit Profile, Recovery Codes, Notifications

---

## 7.6 Navigating Internara

Once logged in, you'll see the main application layout:

### Sidebar (Left)

The sidebar shows navigation menu items based on your role. Each menu group is visible only to
roles that need it:

| Menu Group | Visible To |
|------------|------------|
| Dashboard | All roles |
| Foundation (School, Academic Years, Departments) | Super Admin, Admin |
| Internship (Programs, Groups, Companies, Placements) | Super Admin, Admin |
| Registration (Applications, Direct Placement) | Super Admin, Admin |
| People (Users, Students, Teachers, etc.) | Super Admin, Admin |
| Assessment (Rubrics, Evaluations) | Super Admin, Admin |
| Operations (Notifications, Attendance, Logbook, etc.) | Super Admin, Admin, Teacher, Supervisor |
| Student Portal | Student |
| Teacher Portal | Teacher |
| Supervisor Portal | Supervisor |
| Reports (Certificates, Lifecycle, GDPR) | Super Admin, Admin |
| System (Settings, Handbooks, Recovery Slips) | Super Admin, Admin |

### Top Navigation Bar

The header contains:
- **Mobile menu toggle** — shows/hides sidebar on small screens
- **Page title** — current page name
- **Theme switcher** — toggle light/dark mode
- **Language switcher** — switch between English and Bahasa Indonesia
- **Notification bell** — view your in-app notifications
- **User menu** — profile link, logout

### Dashboard Guide

A floating help button (question mark icon) appears in the bottom-right corner of every dashboard.
Click it for a quick orientation guide covering navigation, notifications, and profile management.

---

## 7.7 Profile & Settings

To access your profile:

1. Click your name or avatar in the top-right corner
2. Select **Profile**

From your profile you can:
- Update your name, bio, and contact information
- Upload or change your profile photo
- Change your password
- View and manage your recovery codes
- Review your notification settings

---

## 7.8 Logging Out

Click your name in the top-right navigation bar and select **Logout**. You'll be returned to the
login page.

---

## Next Steps

Your dashboard is your home base in Internara. From here:

- **Students:** Start by writing your first journal entry or clocking in
- **Teachers:** Verify pending logbooks and grade submissions
- **Supervisors:** Review the verification queue
- **Admins:** Check system readiness and monitor the PKL funnel

---

**← Previous:** [Post-Setup](03-post-setup.md)
**Next:** [System Health & Troubleshooting](04-system-health-and-troubleshooting.md)
