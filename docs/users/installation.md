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

### Step 2: Automated Bootstrapping

Run the unified installation command to handle dependency orchestration, environment generation,
application key assignment, database migration, and UI asset construction.

```bash
composer setup
```

### Step 3: Web-Based Setup Wizard

Access the application via your browser to launch the **Setup Wizard**. The system will
automatically detect the uninstalled state and redirect you to the initialization portal.

- **Setup Token Security**: Initial access is protected by a `setup_token` generated during the
  `app:install` command.
- **Signed URL Security**: High-privilege setup actions are protected via **Expiring Signed URLs**
  to prevent session hijacking during initialization.
- **Institutional Branding**: Define your **Brand Name** (Institution name) vs **App Name**
  (Software identity).
- **Primary Admin**: Complete the creation of the first Super-Admin account.

---

## ✅ 3. Post-Installation Verification

Once the automated installation is complete, verify the health of the application baseline:

```bash
php artisan app:info
```

- **Objective**: Ensure the version, series code, and environment status match the intended
  deployment target.
- **Verification**: Run `composer test` to perform a full technical pre-flight check of the modular
  infrastructure.

---

_For advanced architectural details or customization, refer to the
**[Architecture Description](../developers/architecture.md)**._
