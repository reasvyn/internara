# Guides — Operational & Design Documents

Operational guides and design documents. Product scope and project status live in the root
**[README.md](../../README.md)**.

- **[README — Product & Project Status](../../README.md)** — Product scope, design principles, user personas, system boundary, deployment model, localization, licensing, and project status (merged from `product-definition.md` + `project-overview.md`)
- **[Internara Project Spec](../specs/QLHDO-internara-project.md)** — Functional and non-functional requirements
  for Indonesian SMA/SMK PKL management, UI/UX standards, scalability targets, and compliance
- **[RBAC & Authentication](rbac.md)** — Authentication flow, flat role hierarchy, functional roles,
  permissions model, Gate::before bypass
- **[UI/UX Design](ui-ux/)** — Comprehensive UI system documentation:
  - [Design System & Guidelines](ui-ux/design-system.md) — Design principles, layouts, dark mode, responsive, accessibility, routing, localization, component patterns
  - [Index](ui-ux/index.md) — UI stack overview, quick reference, design system
  - [Livewire](ui-ux/livewire.md) — Complete Livewire 4 component guide
  - [Tailwind CSS](ui-ux/tailwindcss.md) — Complete Tailwind CSS 4 styling guide
  - [TallStackUI](ui-ux/tallstackui.md) — Complete component reference (80+ components)
  - [Integration](ui-ux/integration.md) — How all UI technologies work together
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
