# Installation Guide: System Bootstrapping

This document provides the formal procedure for the deployment and initialization of the Internara
platform. It is designed for system administrators and IT personnel responsible for environment
provisioning.

---

## 🛠 1. Technical Requirements Baseline

Before initialization, verify that the target environment satisfies the requirements defined in the
**[System Requirements Specification](../developers/specs.md)**:

- **PHP 8.4+**: Mandatory for strict-type compatibility.
- **Composer**: PHP dependency manager.
- **Node.js & NPM**: Required for asset orchestration (Tailwind v4).
- **Database Engine**: Support for SQLite, PostgreSQL, or MySQL.
- **Server**: Linux-based environment (VPS or Bare Metal).

---

## 🚀 2. Automated Installation Procedure

Internara utilizes the **Setup** module to automate environment initialization and reduce deployment
friction.

### Step 1: Clone the Repository

```bash
git clone https://github.com/reasnov/internara.git
cd internara
```

### Step 2: Dependency Orchestration

```bash
composer install
npm install
```

### Step 3: Automated Bootstrapping

Run the unified installation command to handle environment generation, application key assignment,
and database migration.

```bash
php artisan app:install
```

### Step 4: UI Asset Construction

```bash
npm run build
```

---

## ✅ 3. Post-Installation Verification

Once the automated installation is complete, verify the health of the application baseline:

```bash
php artisan app:info
```

- **Objective**: Ensure the version, series code, and environment status match the intended
  deployment target.
- **Access**: Access the web-based **Setup Wizard** via the browser to finalize institutional
  branding and initial administrator registration as guided by the system.

---

_For advanced architectural details or customization, refer to the
**[Architecture Description](../developers/architecture.md)**._
