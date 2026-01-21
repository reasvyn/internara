# Internara

![Version](https://img.shields.io/badge/version-v0.7.0--alpha-blue?style=flat-square)
![Lifecycle](https://img.shields.io/badge/lifecycle-released-green?style=flat-square)
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

Refer to the **[Installation Guide](docs/main/installation-guide.md)** for detailed setup instructions.

---

## Versioning & Support Policy

Internara adopts **Analytical Versioning**, where each released version is documented with a 
post-release narrative describing delivered value and system state.

### Current Version Series

* **Active Series:** `v0.7.x`
* **Current Release:** `v0.7.0-alpha`
* **Lifecycle Status:** Released
* **Support Policy:** Snapshot (no ongoing maintenance)

---

### Version Support Matrix

| Version Series | Stage | Support Policy | Security Updates | Bug Fixes | Lifecycle Status   |
| :------------- | :---- | :------------- | :--------------- | :-------- | :----------------- |
| **v0.7.x**     | Alpha | Snapshot       | ❌                | ❌         | Released           |
| **v0.6.x**     | Alpha | Snapshot       | ❌                | ❌         | Released           |
| **v0.5.x**     | Alpha | EOL            | ❌                | ❌         | EOL (End of Life)  |
| **v0.4.x**     | Alpha | EOL            | ❌                | ❌         | Archived           |

For a complete history and analytical narratives, see **[Application Versions Overview](docs/versions/versions-overview.md)**.

---

## Documentation

Internara maintains structured documentation categorized by target audience.

### 👥 Public Guides (Users & Admins)

* **[Project Overview](docs/main/project-overview.md)**: Vision, goals, and key features.
* **[Installation Guide](docs/main/installation-guide.md)**: Setup instructions for servers.
* **[Latest Updates](docs/versions/versions-overview.md)**: Release history and status.

### 🛠 Internal Manuals (Developers)

* **[Architecture Guide](docs/internal/architecture-guide.md)**: Modular Monolith layers and rules.
* **[Development Workflow](docs/internal/development-workflow.md)**: Feature engineering lifecycle.
* **[Engineering Index](docs/internal/table-of-contents.md)**: Complete list of technical standards.

---

## License

This project is licensed under the [MIT License](LICENSE).

---

## Credits

* **Author:** [Reas Vyn](https://github.com/reasnov)
* **Contact:** [reasnov.official@gmail.com](mailto:reasnov.official@gmail.com)