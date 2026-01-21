# Internara

![Version](https://img.shields.io/badge/version-v0.7.x--alpha-blue?style=flat-square)
![Lifecycle](https://img.shields.io/badge/lifecycle-active--development-yellow?style=flat-square)
![Tests](https://img.shields.io/badge/tests-passing-brightgreen?style=flat-square)
![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-%3E%3D%208.4-777bb4?style=flat-square&logo=php)
![Laravel](https://img.shields.io/badge/Laravel-v12-ff2d20?style=flat-square&logo=laravel)

**Internara** is an open-source internship management system built with **Laravel 12**, **Livewire 3**,
and a **Modular Monolith** architecture.

The system is designed to manage the **full internship lifecycle**, from registration and daily
journals to structured assessment, certification, and institutional reporting.

---

## Features

- **Modular Monolith Architecture**  
  Built using `nwidart/laravel-modules` to isolate business domains (User, Internship, School, etc.)
  into self-contained modules with explicit boundaries.

- **End-to-End Internship Lifecycle**  
  Covers registration, journals, attendance, assessment, certification, and reporting.

- **Role-Based Workspaces**  
  Dedicated, secure workspaces for **Admins**, **Students**, **Academic Teachers**, and
  **Industry Mentors**.

- **Administrative Automation**  
  Participation-driven scoring, requirement verification, batch operations, and background job
  orchestration.

- **Modern TALL Stack**  
  Laravel 12, Livewire 3, Tailwind CSS 4, and Alpine.js.

- **Security-Oriented Design**  
  RBAC, UUID-based entities, and strict inter-module access rules.

---

## Requirements

- PHP 8.4 or higher  
- Composer  
- Node.js & NPM  
- SQLite (development), MySQL, or PostgreSQL  

---

## Installation

1. **Clone the repository**

   ```bash
   git clone https://github.com/reasnov/internara.git
   cd internara
   ```

2. **Install PHP dependencies**

   ```bash
   composer install
   ```

3. **Install Node.js dependencies**

   ```bash
   npm install
   ```

4. **Configure environment**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Update `.env` with your database configuration.

5. **Run migrations and seeders**

   ```bash
   php artisan migrate --seed
   ```

6. **Build frontend assets**

   ```bash
   npm run build
   ```

7. **Start development server**

   ```bash
   php artisan serve
   ```

---

## Versioning & Lifecycle Policy

Internara adopts **Analytical Versioning**, where each released version is documented with a
post-release **technical narrative** describing architectural decisions and final implementation
state.

### Current Version Series

* **Active Series:** `v0.7.x`
* **Stage:** Alpha
* **Lifecycle Status:** Active Development
* **Support Policy:** Snapshot (no security updates or bug-fix guarantees)

> Alpha releases are exploratory by design. APIs, schemas, and workflows may change between versions.

---

### Version Support Matrix

| Version Series | Stage | Support Policy | Security Updates | Bug Fixes | Lifecycle Status   |
| :------------- | :---- | :------------- | :--------------- | :-------- | :----------------- |
| **v0.7.x**     | Alpha | Snapshot       | ❌                | ❌         | Active Development |
| **v0.6.x**     | Alpha | Snapshot       | ❌                | ❌         | Released           |
| **v0.5.x**     | Alpha | EOL            | ❌                | ❌         | EOL (End of Life)  |
| **v0.4.x**     | Alpha | EOL            | ❌                | ❌         | Archived           |

For a complete history and analytical narratives, see **[Application Versions Overview](docs/versions/versions-overview.md)**.

---

## Documentation

Internara maintains structured, SDLC-aligned documentation under the [`/docs`](/docs) directory.

### Key References

* **[Project Overview](docs/project-overview.md)**
  Vision, principles, and documentation structure.

* **[Architecture Guide](docs/main/architecture-guide.md)**
  Modular Monolith layers, boundaries, and communication rules.

* **[Development Workflow](docs/main/development-workflow.md)**
  Engineering SOP for feature implementation.

* **[Application Versions Overview](docs/versions/versions-overview.md)**
  Version lifecycle, support policy, and release lineage.

* **[Changelog](CHANGELOG.md)**
  Incremental changes per release.

* **[Security Policy](SECURITY.md)**
  Security audit scope and vulnerability reporting.

---

## Contributing

Contributions are welcome.
Please read [CONTRIBUTING.md](CONTRIBUTING.md) before submitting pull requests.

---

## License

This project is licensed under the [MIT License](LICENSE).

---

## Credits

* **Author:** [Reas Vyn](https://github.com/reasnov)
* **Contact:** [reasnov.official@gmail.com](mailto:reasnov.official@gmail.com)