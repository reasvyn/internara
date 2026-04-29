# Internara: Modern Internship Management System

[![Laravel Version](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.4.x-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Internara is a cutting-edge Internship Management System built with a focus on developer velocity, architectural integrity, and system observability. It is designed to streamline the relationship between **Schools**, **Students**, **Teachers**, and **Companies (Mentors)**.

## 🚀 The "MARY" Stack
Internara leverages a modern "No-JS" (Livewire-heavy) tech stack:
- **UI Components**: [Mary UI](https://mary-ui.com/)
- **Frontend Engine**: [Laravel Livewire 3](https://livewire.laravel.com/)
- **CSS Framework**: [Tailwind CSS 4](https://tailwindcss.com/)
- **UI Library**: [DaisyUI 5](https://daisyui.com/)

## 🏛️ Architecture: Action-Oriented MVC
The project follows a strict **Action-Oriented Architecture** that separates stateless application logic from stateful business rules:
- **Stateless Actions**: Encapsulate single use-cases (e.g., `ClockInAction`, `LoginAction`).
- **Rich Models**: Centralize business rules and data integrity (e.g., `isWithinRadius()`, `canBeApproved()`).
- **UUID Primary Keys**: Global standardization on UUIDs for all database entities.
- **Audited by Design**: Every critical action is automatically recorded in the system audit trail.

---

## 📚 Documentation Index
Explore the detailed documentation in the `docs/` directory:

| Document | Description |
| :--- | :--- |
| [**Architecture**](docs/architecture.md) | Deep dive into layers, Actions, and Rich Models. |
| [**Infrastructure**](docs/infrastructure.md) | Technical stack, dependencies, and environment requirements. |
| [**Engineering Standards**](docs/standards.md) | The 3S Doctrine (Secure, Sustain, Scalable) and coding conventions. |
| [**Access Control (RBAC)**](docs/rbac.md) | User roles, permissions, and account lifecycle management. |
| [**System Audits**](docs/audits.md) | Forensic logging and the `LogAuditAction` system. |
| [**Logging & Monitoring**](docs/logging.md) | Standard logs and real-time observability with Laravel Pulse. |
| [**Testing Strategy**](docs/testing.md) | Architectural, Feature, and Unit testing guidelines. |

---

## 🛠️ Getting Started

### Prerequisites
- PHP 8.4+
- Node.js & NPM/PNPM
- SQLite (or preferred SQL database)

### Installation
1. Clone the repository.
2. Install PHP dependencies:
   ```bash
   composer install
   ```
3. Install JS dependencies:
   ```bash
   pnpm install
   ```
4. Setup environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
5. Run migrations & seed:
   ```bash
   php artisan migrate:fresh --seed
   ```
6. Start the development server:
   ```bash
   php artisan serve
   pnpm dev
   ```

## 📊 Observability
Internara includes built-in real-time monitoring via **Laravel Pulse**.
Access the dashboard at: `http://localhost:8000/pulse`

---

## 👤 Author Credits
- **Name**: Reas Vyn
- **Email**: reasvyn@gmail.com
- **GitHub**: [github.com/reasvyn](https://github.com/reasvyn)

## 📄 License
This project is open-sourced software licensed under the [MIT license](LICENSE).
