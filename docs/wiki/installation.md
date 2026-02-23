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

---

## 4. Launching the Development Environment

To start the server and background processes concurrently:

```bash
composer dev
```

The system will be accessible at `http://localhost:8000`.

---

## 5. Post-Installation Wizard

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
