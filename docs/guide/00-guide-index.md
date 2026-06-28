# Chapter 0: User Manual Overview

> **Last updated:** 2026-06-16
> **Changes:** add chapter 12 user management guide, chapter 13 supervisor & partnership, chapters 14-15 internship management & registration

## Description
Welcome to Internara — the vocational fieldwork management system for Indonesian schools.

This manual walks you through everything you need to set up and start using Internara, step by step.
No technical background required beyond following instructions.

---


## How to Use This Manual

Each chapter depends on the one before it. Start at Chapter 1 and follow the `→ Next` links at the
bottom of each page to move forward. Use `← Previous` to go back.

Estimated total time: **1–2 hours** for installation and initial setup.

---

## Table of Contents

| # | Chapter | What You'll Do | Est. Time |
|---|---------|----------------|-----------|
| 01 | [Installation](01-installation.md) | Install dependencies, configure your server, run the installer | 30–45 min |
| 02 | [Setup Wizard](02-setup-wizard.md) | Create admin account, enter school info, set up your first department | 10–15 min |
| 03 | [Post-Setup](03-post-setup.md) | Configure settings, add users, create programs, go live | 30–60 min |
| 04 | [System Health & Troubleshooting](04-system-health-and-troubleshooting.md) | Health checks, common problems, maintenance commands | 10–15 min |
| 05 | [Upgrading Internara](05-upgrading-from-previous.md) | Upgrade procedure, rollback, version numbering | 15–20 min |
| 06 | [Admin & Recovery](06-admin-create-and-recovery.md) | Create admin accounts, recover access, manage recovery keys | 10–15 min |
| 07 | [Login & Dashboard](07-login-and-dashboard.md) | Log in, navigate the dashboard, manage your profile | 10–15 min |
| 09 | [User Profile & Recovery](09-user-profile-and-recovery.md) | Edit profile, manage recovery codes, use notifications | 10–15 min |
| 10 | [System Settings & Backups](10-system-settings-and-backups.md) | Configure branding, mail, locale; manage system backups | 10–15 min |
| 11 | [Institution & Academics](11-institution-and-academics.md) | Manage school profile, departments, academic years | 10–15 min |
| 12 | [User Management](12-user-management.md) | Manage users across all roles (admins, students, teachers, supervisors) | 15–20 min |
| 13 | [Supervisor & Partnership](13-supervisor-and-partnership.md) | Manage supervisors, companies, and partnership agreements | 15–20 min |
| 14 | [Internship Management & Handbook](14-internship-management-and-handbook.md) | Create programs, manage groups, publish handbooks | 15–20 min |
| 15 | [Internship Registration & Placement](15-internship-registration-and-placement.md) | Student applications, direct placement, placement changes | 15–20 min |
| 16 | [Attendance & Logbook](16-attendance-and-logbook.md) | Clock in/out, logbook entries, absence requests, mentor verification | 15–20 min |
| 17 | [Monitoring Visit & Supervision Log](17-monitoring-visit-and-supervision-log.md) | Teacher site visits, student mentoring logs, supervisor review | 10–15 min |
| 18 | [Assignment & Assessment](18-assignment-and-assessment.md) | Task management, submissions, rubric-based competency evaluation | 15–20 min |
| 19 | [Student Report & Certification](19-student-report-and-certification.md) | Final report drafting, grading, certificate issuance | 15–20 min |
| 20 | [Evaluation & Incident](20-evaluation-and-incident.md) | Feedback forms, workplace issue reporting and resolution | 10–15 min |
| 21 | [Announcement & Notifications](21-announcement-and-notifications.md) | Broadcast messages, personal alerts, notification center | 10–15 min |
| 22 | [System Observability](22-system-observability.md) | Health checks, Pulse monitoring, audit logs, backups | 15–20 min |

---

## What You'll Need

Before starting, make sure you have:

- **A server or computer** — running Linux (recommended), macOS, or Windows (WSL)
- **PHP 8.4+** installed with common extensions
- **Composer** (PHP package manager)
- **Node.js 20+** with npm
- **A database** — SQLite works out of the box for testing; MySQL or PostgreSQL for production
- **A domain or IP address** — where Internara will be accessible
- **An email address** — for the super admin account

Don't worry if you're missing something — Chapter 1 covers everything in detail.

---

## Need Help?

If you get stuck:

- Run `php artisan system:health` for a system check
- See the [Troubleshooting](../getting-started.md#troubleshooting) section
- Each chapter has its own troubleshooting notes at the end

---

---

**Ready?** → [Start with Chapter 1: Installation](01-installation.md)
