# Internara

![Version](https://img.shields.io/badge/version-v0.4.0--alpha-blue?style=flat-square)
![Status](https://img.shields.io/badge/status-active--development-orange?style=flat-square)
![Tests](https://img.shields.io/badge/tests-passing-brightgreen?style=flat-square)
![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-%3E%3D%208.4-777bb4?style=flat-square&logo=php)
![Laravel](https://img.shields.io/badge/Laravel-v12-ff2d20?style=flat-square&logo=laravel)

**Internara** is an open-source internship management system built with **Laravel 12**, **Livewire
3**, and a **Modular Monolith** architecture. It is designed to streamline the entire internship
lifecycle, from registration and journal logging to final reporting and evaluation.

## Features

- **Modular Architecture:** Built using `nwidart/laravel-modules` for a scalable and maintainable
  codebase, isolating business domains into self-contained modules.
- **Modern Tech Stack:** Leveraging the **TALL Stack** (Tailwind CSS v4, Alpine.js, Laravel 12,
  Livewire 3) for a blazing-fast and interactive user experience.
- **Robust Infrastructure:**
    - **Automated DI:** Self-binding service layer using `EloquentQuery` patterns.
    - **Modular Tooling:** Custom Artisan generators for Classes, Interfaces, and Traits that
      respect modular namespace conventions.
    - **Standardized States:** Integrated status management across all domain models.
- **Role-Based Access Control:** Secure access management using `spatie/laravel-permission` with
  full UUID support and modular isolation.
- **Internship Lifecycle Management:**
    - **Registration:** Students can browse and apply for available placements.
    - **Journals:** Daily log entries for students to track their progress.
    - **Assignments:** Teachers can assign tasks and review submissions.
    - **Assessments:** Comprehensive evaluation system with customizable aspects and indicators.
    - **Reporting:** Automated and manual reporting tools for final grades.
- **Minimalist UI:** Designed with **MaryUI** and **DaisyUI** for a clean, professional, and
  accessible interface.

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

## Version History & Support

### Version History

| Version        | Series Code  | Status     | Key Focus                 |
| :------------- | :----------- | :--------- | :------------------------ |
| `v0.4.0-alpha` | `ARC01-INST` | `Released` | Institutional & Academic  |
| `v0.3.0-alpha` | `ARC01-USER` | `Released` | User Management & Profile |
| `v0.2.0-alpha` | `ARC01-CORE` | `Released`      | RBAC & Shared Services    |
| `v0.1.1-alpha` | `ARC01-INIT` | `Released`      | Project Initialization    |

### Version Supports

| Version  | Security | Bug Fixes | Status                 |
| :------- | :------- | :-------- | :--------------------- |
| **v0.x** | ✅       | ✅        | **Active Development** |

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
- **[Modular Monolith Workflow](docs/main/modular-monolith-workflow.md)**: **(Crucial)**
  Step-by-step developer guide for implementing new features (Models, Services, UI).
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
