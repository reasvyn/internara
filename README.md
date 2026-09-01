<p align="center">
    <img src="https://github.com/reasvyn/internara/actions/workflows/release.yml/badge.svg" alt="CI">
    <img src="https://img.shields.io/badge/license-MIT-blue?style=flat-square" alt="MIT License">
    <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel" alt="Laravel 13">
    <img src="https://img.shields.io/badge/Livewire-4-fb70a9?style=flat-square&logo=livewire" alt="Livewire 4">
</p>

# Internara — Vocational Fieldwork Management System

Self-hosted, single-tenant platform for managing compulsory industrial fieldwork programs
(PKL — _Praktik Kerja Lapangan_) at Indonesian vocational schools (SMK). MIT-licensed, designed
to run entirely on school-owned infrastructure — from a $5/month shared host with SQLite up to a
Docker Compose stack — with zero recurring costs, full data sovereignty, and offline robustness.

---

## Why

Indonesian SMKs are legally required (Kemendikbud) to run PKL: 3–6 months of supervised fieldwork
at partner companies (DUDI). A medium-to-large school coordinates **500–1,000 students across
150–300 companies per period** — today managed on paper, Excel, WhatsApp, and ad-hoc email.
Grade-card compilation alone takes 2–3 weeks per cohort, and accreditation demands audit-ready
records that paper workflows cannot produce reliably.

Internara replaces those workflows with:

- **Single source of truth** — admin, teachers, supervisors, and students share real-time data
- **Automated workflow enforcement** — slot capacity, attendance windows, and evaluation
  completeness are system rules, not spreadsheet discipline
- **Audit-ready records** — GPS-tagged attendance, immutable submitted logbooks, user/IP activity
  logs, QR-verifiable certificates
- **Instant reporting** — grade cards compiled in seconds, certificate verification via QR scan
- **Data sovereignty** — self-hosted, no cloud dependency, works on a school LAN without internet

Deep-dive: [problem analysis & personas](docs/project-vision.md) · [design philosophy](docs/philosophy.md)

## Features

- **Student lifecycle** — registration wizard, slot-based company placement, change requests
- **Daily operations** — geotagged attendance, reflective dual-mentor logbooks, absence approvals
- **Assessment** — competency rubrics, multi-evaluator grading, weighted score aggregation
- **Partnerships** — company registry, MoU lifecycle, slot quota tracking with expiry alerts
- **Certification** — PDF templates, batch issuance, QR-code verification
- **Reporting** — final grade-card compilation with coordinator sign-off, audit export
- **Account management** — RBAC with 5 flat roles, 8-state account lifecycle, recovery mechanisms
- **Localization** — English codebase, bilingual UI (EN/ID)
- **Observability** — Laravel Pulse, dual-channel SmartLogger (file + DB)

## Requirements

- PHP **8.4+** with `bcmath`, `ctype`, `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`,
  `pdo`, `tokenizer`, `xml`, `zip`
- Composer **2.x**, Node.js **20+**, npm **10+**
- Database: SQLite (zero-config), MySQL 8+, MariaDB 10.6+, or PostgreSQL 15+

## Installation

**One-line (recommended):**

```bash
curl -fsSL https://raw.githubusercontent.com/reasvyn/internara/main/scripts/install.sh | bash
# → git clone → composer install → npm install && npm run build → php artisan setup:install
cd internara
composer run dev
```

Custom dir / branch / pass-through to `setup:install`:

```bash
curl -fsSL https://raw.githubusercontent.com/reasvyn/internara/main/scripts/install.sh | bash -s -- --dir my-pkl --branch main -- --force
# local alternative inside an existing checkout:
bash scripts/install.sh --help
```

**Manual:**

```bash
git clone https://github.com/reasvyn/internara.git
cd internara
composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate

# For SQLite: set DB_CONNECTION=sqlite and create the file
touch database/database.sqlite

# Audits env, runs migrations, seeds defaults — outputs a signed setup-wizard URL
php artisan setup:install

# Serves app + queue worker + logs + Vite concurrently
composer run dev
```

Open the signed URL printed by `setup:install` and complete the 6-step setup wizard. Verify with
`php artisan system:health`. Full walkthrough including MySQL/MariaDB/PostgreSQL configuration:
[`docs/getting-started.md`](docs/getting-started.md).

## Architecture

Action-based MVC with vertical slicing: 18 business modules each own their complete stack
(Models, Actions, Livewire, Events, Policies) colocated under `app/{Module}/`. Every mutation
follows one path:

```
Livewire → Command Action → Entity (business rules) → Model → DB
```

Key patterns: Action Triad (Command/Read/Process), immutable Entities carrying business rules,
DTO boundaries between layers, enum-driven state machines. Full model, layer diagram, and pattern
reference: [`docs/architecture.md`](docs/architecture.md).

## Deployment

| Model | Best for | Stack |
|-------|----------|-------|
| **Shared hosting** | Budget deployments | PHP-FPM + SQLite, sync queue, cron endpoint |
| **VPS / VM** | Most schools (**recommended**) | PHP-FPM + Nginx, SQLite/MySQL, optional Redis |
| **Docker Compose** | Tech-savvy operators | app + queue + scheduler + Redis containers |

SQLite is the zero-config default; switching to MySQL/MariaDB/PostgreSQL is a `.env` change, no
code required. Production checklist and per-path guides:
[`docs/guides/infra/deployment.md`](docs/guides/infra/deployment.md).

## Localization

Bilingual EN/ID: English for code and docs, Indonesian as preferred UI language. Every
user-facing string goes through `__()`; locale is togglable via Settings. Domain terminology
(NISN, NPSN, DUDI, PKL, …) is mapped in the
[umbrella spec glossary](docs/specs/QLHDO-internara-project.md).

## Documentation

All documentation lives in [`docs/`](docs/index.md):

| Topic | Document |
|-------|----------|
| Getting started | [`docs/getting-started.md`](docs/getting-started.md) |
| Architecture | [`docs/architecture.md`](docs/architecture.md) |
| Module overviews | [`docs/refs/modules/index.md`](docs/refs/modules/index.md) |
| Coding conventions | [`docs/conventions.md`](docs/conventions.md) |
| Deployment | [`docs/guides/infra/deployment.md`](docs/guides/infra/deployment.md) |
| Testing guide | [`docs/guides/infra/testing.md`](docs/guides/infra/testing.md) |
| Full doc index | [`docs/index.md`](docs/index.md) |

## Testing & Quality

```bash
composer run test      # Full Pest test suite
composer run analyse   # PHPStan static analysis (level 8)
composer run quality   # Lint + analyse + tests
vendor/bin/pint --dirty --format agent  # Code style fixer
```

Pest 4 with feature + unit coverage per Action, PHPStan level 8, Laravel Pint. Architecture
scanner toolkit (`tools/scan_*.py`): [`docs/guides/infra/tools.md`](docs/guides/infra/tools.md).

## Project Status

**v0.15.8** (`composer.json`) / **v0.15.3** (`package.json`) — all 19 modules (18 business + UI + Core) have a full stack; test suite passes at ~98%, though coverage is
uneven (core modules solid, domain modules need work; known issues tracked in
[GitHub Issues](https://github.com/reasvyn/internara/issues)). Current focus: fixing P0 runtime
errors, improving domain-module coverage, UI polish. Roadmap horizons through 2030:
[`docs/project-vision.md`](docs/project-vision.md).

## Contributing

Contributions welcome — typo fixes, tests, new modules. Read
[CONTRIBUTING.md](CONTRIBUTING.md) for branching, commit format (`type(scope): description`),
and quality gates; skim [`docs/architecture.md`](docs/architecture.md) and
[`docs/conventions.md`](docs/conventions.md) before your first PR. All participants must follow
the [Code of Conduct](CODE_OF_CONDUCT.md). Good first issues are labeled
on [GitHub Issues](https://github.com/reasvyn/internara/issues).

Language: English for code, comments, commits, and docs; Indonesian only in `lang/id/`.

## Security

Report vulnerabilities privately — see [SECURITY.md](SECURITY.md) or email
[reasvyn@gmail.com](mailto:reasvyn@gmail.com). Do not open public issues for security reports.

## License

[MIT](LICENSE) © 2025–2026 Reas Vyn. Schools may fork, customize (e.g. Dapodik integration,
regional certificate templates), and deploy freely; contributions upstream benefit every SMK.
