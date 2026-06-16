# User Manual — Getting Started with Internara

> **Last updated:** 2026-06-16
> **Changes:** add chapter 12 user management guide

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
