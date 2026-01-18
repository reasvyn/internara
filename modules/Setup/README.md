# Setup Module

The `Setup` module is designed to automate the initial installation, deployment, and configuration
of the Internara application.

## Purpose

- **Deployment:** Streamlines the transition from a clean install to a working application.
- **Security:** Protects initial configuration routes from unauthorized access.
- **Automation:** Automates database seeding and environment verification.

## Key Features

- **[RequireSetupAccess Middleware](../../docs/versions/v0.1.x-alpha.md#31-environment--framework-setup)**:
  Ensures the setup wizard can only be accessed before the application is marked as "installed".
- **Setup Wizard**: A guided process for configuring basic school info and creating the first
  SuperAdmin.

---

**Navigation** [← Back to Module TOC](../../docs/main/modules/table-of-contents.md)
