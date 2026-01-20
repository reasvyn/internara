# Internara

![Version](https://img.shields.io/badge/version-v0.7.0--alpha-blue?style=flat-square)
![Status](https://img.shields.io/badge/status-in--progress-yellow?style=flat-square)
![Tests](https://img.shields.io/badge/tests-passing-brightgreen?style=flat-square)
![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-%3E%3D%208.4-777bb4?style=flat-square&logo=php)
![Laravel](https://img.shields.io/badge/Laravel-v12-ff2d20?style=flat-square&logo=laravel)

**Internara** is an open-source internship management system built with **Laravel 12**, **Livewire
3**, and a **Modular Monolith** architecture. It is designed to streamline the entire internship
lifecycle, from registration and journal logging to final reporting and evaluation.

## Features

- **Modular Monolith Architecture:** Built using `nwidart/laravel-modules` to isolate business domains (User, Internship, School, etc.) into self-contained, scalable modules.
- **Comprehensive Lifecycle Management:** End-to-end handling of the internship process—from student registration and daily logbooks (Journals) to final assessments and certification.
- **Role-Based Workspaces:** Dedicated, secure dashboards for **Admins**, **Students**, **Academic Teachers**, and **Industry Mentors**.
- **Administrative Automation:** Intelligent features like **Participation-Driven Scoring**, **Requirement Verification**, and background job monitoring to streamline operations.
- **Modern TALL Stack:** Blazing-fast reactive UI built with **Laravel 12**, **Livewire 3**, **Tailwind CSS 4**, and **Alpine.js**.
- **Robust Security:** Integrated Role-Based Access Control (RBAC), UUIDs for all models, and strict privacy boundaries.

## Requirements

- PHP 8.4 or higher
- Composer
- Node.js & NPM
- SQLite (default for development), MySQL, or PostgreSQL

### Key Third-Party Packages

This project is built upon the Laravel ecosystem and several key packages:

- [nwidart/laravel-modules](https://nwidart.com/laravel-modules/v11/introduction)
- [spatie/laravel-permission](https://spatie.be/docs/laravel-permission/v6/introduction)
- [spatie/laravel-model-status](https://github.com/spatie/laravel-model-status)
- [robsontenorio/mary](https://mary-ui.com/)
- [secondnetwork/blade-tabler-icons](https://github.com/secondnetwork/blade-tabler-icons)

## Installation

1.  **Clone the repository:**

    ```bash
    git clone https://github.com/reasnov/internara.git
    cd internara
    ```

2.  **Install PHP dependencies:**

    ```bash
    composer install
    ```

3.  **Install Node.js dependencies:**

    ```bash
    npm install
    ```

4.  **Configure environment:**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

    Update your `.env` file with your database credentials.

5.  **Run migrations and seeders:**

    ```bash
    php artisan migrate --seed
    ```

6.  **Build assets:**

    ```bash
    npm run build
    ```

7.  **Start the development server:**

    ```bash
    php artisan serve
    ```

## Version Support & History

### Version Supports

| Version  | Security | Bug Fixes | Status            |
| :------- | :------- | :-------- | :---------------- |
| **v0.x** | ❌       | ❌        | **Alpha**         |

### Version History

| Version        | Series Code     | Status           | Key Focus                    |
| :------------- | :-------------- | :--------------- | :--------------------------- |
| `v0.7.0-alpha` | `ARC01-ORCH-01` | `In-progress`    | Administrative Orchestration |
| `v0.6.0-alpha` | `ARC01-FEAT-01` | `Released`       | Assessment & Finalization    |
| `v0.5.0-alpha` | `ARC01-OPER-01` | `Released`       | Operational & Activity Track |
| `v0.4.0-alpha` | `ARC01-INST-01` | `Released`       | Institutional & Academic     |
| `v0.3.0-alpha` | `ARC01-USER-01` | `Released`       | User Management & Profile    |
| `v0.2.0-alpha` | `ARC01-CORE-01` | `Released`       | RBAC & Shared Services       |
| `v0.1.1-alpha` | `ARC01-INIT-01` | `Released`       | Project Initialization       |

For more details on our security audit protocols and reporting, please see
[SECURITY.md](SECURITY.md).

## Documentation

This project maintains comprehensive developer documentation to ensure consistency, clarity, and
efficient onboarding. All guides are located within the [`/docs`](/docs) directory.

### Key Documents

- **[Project Overview](docs/project-overview.md)**: The best starting point for a high-level
  understanding of the project's vision, core principles, technology stack, and documentation
  structure.
- **[Architecture Guide](docs/main/architecture-guide.md)**: Detailed technical overview of the
  Modular Monolith structure, layers, and communication rules.
- **[Development Workflow](docs/main/development-workflow.md)**: **(Crucial)** Step-by-step
  developer guide for implementing new features (Models, Services, UI).
- **[Main Documentation Overview](docs/main/main-documentation-overview.md)**: A deeper dive into
  core architectural principles, development workflows, coding conventions, and available tools.
- **[Version History](docs/versions/versions-overview.md)**: Details on specific application
  releases and their scope.
- **[Changelog](CHANGELOG.md)**: A comprehensive record of all notable changes made to the project.
- **[Security Policy](SECURITY.md)**: Protocols for security audits and instructions for reporting
  vulnerabilities.

For more detailed guides on architecture, conventions, testing, and more, please explore the
`docs/main` directory.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to
contribute to this project.

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

- **Author:** [Reas Vyn](https://github.com/reasnov)
- **Email:** reasnov.official@gmail.com
