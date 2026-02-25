# Technical Setup: Cloning and Installation

This guide provides the technical procedures for cloning the Internara repository and initializing
the system environment.

---

## 1. Prerequisites

Before starting the installation, ensure your environment meets the following requirements:

- **PHP**: `^8.4` (with `bcmath`, `curl`, `mbstring`, `openssl`, `pdo_sqlite`, `xml`, `zip`).
- **Composer**: `^2.x`
- **Node.js**: `^20.x` (with `npm`).
- **Database**: SQLite (default), MySQL, or PostgreSQL.

---

## 2. Cloning the Repository

Obtain the system baseline from the official repository:

```bash
# Clone the repository
git clone https://github.com/reasnov/internara.git

# Navigate to the project directory
cd internara
```

---

## 3. Automated Installation (Recommended)

Internara provides a standardized orchestration command to prepare the environment in a single pass:

```bash
composer setup
```

**What this command does:**

1.  Installs PHP dependencies via Composer.
2.  Initializes the `.env` file from the template.
3.  Generates the application encryption key.
4.  Executes database migrations.
5.  Installs Node dependencies and builds frontend assets.

> **Troubleshooting**: If this command fails during step 4, verify your database credentials in the
> `.env` file and ensure the database exists. After fixing, you can run `php artisan migrate` to
> resume the process.

---

## 4. System Integrity & Attribution Verification

Internara includes a low-level security mechanism to protect the system's core metadata and ensure
proper attribution.

- **Authoritative Metadata**: The system relies on `app_info.json` for technical identity and
  versioning.
- **Bootstrapping Guard**: During the application boot process, the system verifies the integrity of
  the metadata and the author's identity. If `app_info.json` is missing or tampered with, the
  application will terminate with a 403 error to prevent unauthorized redistribution or
  architectural drift.

---

## 5. Launching the System

To start the server and all necessary background processes (Queue, Logs, and Vite):

```bash
composer dev
```

The system will be accessible at `http://localhost:8000`.

> **Note**: `composer dev` is intended for evaluation and local development. For high-availability
> production deployment, follow the **[Production Deployment Guide](advanced/deployment.md)**.

## 6. Post-Installation Wizard

After launching the server, visit the application in your browser. You will be automatically
redirected to the **Setup Wizard**, which will guide you through:

1.  **Environment Audit**: Verification of server compatibility.
2.  **Administrator Setup**: Creation of the main system account.
3.  **Institutional Base**: Initial data for School and Departments.
4.  **SMTP Configuration**: Mail server settings for notifications.

---

**Next Step:** [Initial Configuration: Foundation](institutional-foundation.md)

_Once completed, refer to the **[Getting Started](getting-started.md)** guide for your first
administrative steps._
