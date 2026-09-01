# Project Identity & Definition

> **Curated mandatory known context** — tech stack, project definition, target users, design principles, and lifecycle scope. Read at start of every session and when releasing new version.

## Project Identity — Scale & Stack (v0.15.8)

| Fact | Value |
|------|-------|
| **Scope** | 19 modules = 18 business + UI + Core (693 PHP files in `app/` — 691 in `app/Modules/` — 45 migrations, 61 spec files incl. 3 meta / 58 feature specs, 17 web route files incl. `web.php`) |
| **Single-tenant** | No `tenant_id` overhead — one instance per school |
| **DB** | SQLite default (zero-config) / MySQL 8 / MariaDB 10.6 / PG 15 |
| **Deploy** | Shared hosting ($5/mo, SQLite+file+sync), **VPS/VM recommended** (Nginx+SQLite/MySQL+optional Redis), Docker Compose (app+queue+scheduler+Redis) |
| **Status** | **v0.15.8** (`composer.json`) / **v0.15.3** (`package.json`) — Stabilization; suite ~98% pass, uneven coverage (core solid, domain needs work); P0 in `Assessment/Certification/Document` |
| **License** | MIT — schools may fork/customize (Dapodik/regional certificate templates) |

### Technology Stack

| Technology | Layer | Version |
|------------|-------|---------|
| PHP | Language | v8.4 |
| Laravel | Framework | v13.24 |
| Livewire | Frontend | v4.3 |
| Alpine.js | Frontend JS | — |
| Tailwind CSS | CSS | v4.3 |
| TallstackUI | UI Component | v4.1.0 |
| Flatpickr | Date Picker | v4.6 |
| Marked | Markdown Parser | v18.0 |
| Vite | Build Tool | v8.1 |
| laravel-vite-plugin | Build Plugin | v3.1 |
| SQLite | Database | — |
| MySQL | Database | v8.0 |
| MariaDB | Database | v10.6 |
| PostgreSQL | Database | v15.0 |
| barryvdh/laravel-dompdf | PDF Generation | v3.1 |
| laravel-lang/lang | Localization | v15.34 |
| Laravel Pulse | Monitoring | v1.8 |
| spatie/laravel-activitylog | Audit Log | v5.0 |
| spatie/laravel-medialibrary | Media Upload | v11.23 |
| spatie/laravel-model-status | Model Status | v1.20.0 — **deprecated, removal planned (#419); do not use in new code** |
| spatie/laravel-permission | RBAC | v8.3.0 |
| Pest | Testing | v4.2 |
| Laravel Pint | Code Style | v1.24 |
| Mockery | Mocking | v1.6 |
| Faker | Test Data | v1.23 |
| Collision | Error Handler | v8.6 |
| Laravel Tinker | REPL | v3.0 |
| Laravel Pail | Log Viewer | v1.2 |
| Laravel Sail | Docker Dev | v1.65 |
| Prettier | Formatter (non-PHP only) | v3.9 |
| prettier-plugin-blade | Blade Formatter (via Pint) | v3.2 |
| prettier-plugin-tailwindcss | Tailwind Class Sorter (via Pint) | v0.8 |
| concurrently | Task Runner | v10.0 |

---

## Project Definition

**Internara** is a self-hosted, single-tenant web application for managing compulsory industrial fieldwork programs (PKL — _Praktik Kerja Lapangan_) at Indonesian vocational schools (SMA/SMK).

### Target Users

| Persona | Role |
|---------|------|
| **Students (Interns)** | Register, daily logbook, attendance, assignments, certificates |
| **Schools (Admin/Teacher)** | System config, enrollment, grading, supervision, reporting |
| **Companies (Supervisors)** | Attendance verification, logbook review, competency evaluation |

### Design Principles (3S Doctrine)

| Principle | Definition |
|-----------|------------|
| **S1 — Secure** | Enforce authorization at every layer, protect data integrity and PII |
| **S2 — Sustain** | Module colocation, Action single-responsibility, clear boundaries |
| **S3 — Scalable** | Single-tenant (no tenant-ID overhead), CQRS-inspired Action triad |

### Lifecycle Scope

Foundation → Configuration → Identity & Auth → Institutional → Partnerships → Programs → Enrollment → Daily Ops → Assessment → Certification → Reporting → Maintenance

### Out-of-Scope

Multi-tenant SaaS, HR/payroll, real-time chat, government DB sync (CSV import/export only).

---

*Full definition: `docs/project-vision.md` (personas, system boundary, horizon) and `docs/philosophy.md` (3S Doctrine). Condensed overview in `README.md`.*

*This file is **mandatory known context** in `.agents/context/`. Update when tech stack or project definition changes.*