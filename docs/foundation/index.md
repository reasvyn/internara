# Foundation Documents

> **Last updated:** 2026-08-21 **Changes:** merge — `product-definition.md` + `project-overview.md` merged into root `README.md` (SSOT for product scope, personas, boundary, deployment, project status)

Product scope, security model, and design foundations. Product scope and project status now live in
the root **[README.md](../../README.md)** (System Boundary, 3S Doctrine, Personas, Deployment,
Module Landscape, Tech Debt).

- **[README — Product & Project Status](../../README.md)** — Product scope, design principles, user personas, system boundary, deployment model, localization, licensing, and project status (merged from `product-definition.md` + `project-overview.md`)
- **[Internara Project Spec](../specs/QLHDO-internara-project.md)** — Functional and non-functional requirements
  for Indonesian SMA/SMK PKL management, UI/UX standards, scalability targets, and compliance
- **[RBAC & Authentication](rbac.md)** — Authentication flow, flat role hierarchy, functional roles,
  permissions model, Gate::before bypass
- **[UI/UX Design](ui-ux.md)** — Design system (Tailwind CSS v4 + DaisyUI + maryUI), layouts, dark
  mode, responsive
- **[Branding](branding.md)** — Dynamic theming, color system, presets, logo management, font
  strategy
- **[Schema Design Philosophy](../specs/J68GZ-system-requirements.md#73-schema-design-philosophy)** — Domain table design decisions and package/framework tables
- **[Account Recovery](account-recovery.md)** — Recovery slip flow, recovery codes,
  administrative-mediated recovery, CLI super admin recovery

### Operational Guides

- **[Installation](installation.md)** — Server prep, dependencies, environment config, first run
- **[Setup Wizard](setup-wizard.md)** — Browser-based 6-step initial configuration
- **[Post-Setup](post-setup.md)** — First actions after installation: settings, users, programs
- **[System Health & Troubleshooting](system-health.md)** — Health checks, common problems, maintenance commands
- **[Upgrading](upgrading.md)** — Upgrade procedure, rollback, version numbering
- **[Backup & Recovery](backup-recovery.md)** — Account recovery, system backup, restoration procedures
- **[System Observability](system-observability.md)** — Pulse dashboard, audit logs, cleanup, backups
