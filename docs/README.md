# 📖 Internara Documentation Index

Welcome to the Internara documentation! This guide provides comprehensive resources for developers, contributors, and operators.

---

## 🚀 Getting Started

- **[Getting Started Guide](./getting_started.md)** — Quick overview of the project, prerequisites, and first steps
- **[Installation Guide](./installation.md)** — Detailed step-by-step installation, configuration, and setup wizard

---

## 📚 Core Documentation

| Document | Purpose | Audience | Read Time |
| :--- | :--- | :--- | :--- |
| **[Getting Started Guide](./getting_started.md)** | Quick 5-minute setup and first steps | Everyone | 8 min |
| **[Installation Guide](./installation.md)** | Complete setup from git clone to production | DevOps, Setup | 20 min |
| **[Philosophy](./philosophy.md)** | 3S Doctrine (Secure, Sustain, Scalable) and why | Contributors, Architects | 18 min |
| **[Architecture Guide](./architecture.md)** | Modular monolith design, auto-binding, data flow | Developers, Architects | 22 min |
| **[Modules Catalog](./modules-catalog.md)** | Directory of all 29+ modules and their purposes | Everyone | 16 min |
| **[Testing Guide](./testing.md)** | TDD practices, Pest framework, test suites | QA, Developers | 20 min |
| **[Standards & Conventions](./standards.md)** | Code quality, naming, patterns, PSR-12 | Contributors | 22 min |
| **[Contributing Guide](../CONTRIBUTING.md)** | Workflow, code patterns, and PR checklist | Contributors | 12 min |

---

## 🔐 Project Governance

- **[Governance](../GOVERNANCE.md)** — Project governance model and decision-making
- **[Maintainers](../MAINTAINERS.md)** — Core maintainers and responsibilities
- **[Security Policy](../SECURITY.md)** — Vulnerability reporting and security protocols
- **[Support & Communication](../SUPPORT.md)** — Getting help and contact channels
- **[Versioning Policy](../versioning-policy.md)** — Release strategy and compatibility

---

## 🎯 Philosophy & Design

The Internara project is built on the **3S Doctrine**:

- **🔐 Secure (S1)** — Field-level encryption, UUIDs, immutable audit logs
- **📖 Sustain (S2)** — PSR-12, TDD, strict types, English documentation
- **⚙️ Scalable (S3)** — Modular architecture, loose coupling, evolutionary design

Learn more in the **[Philosophy Guide](./philosophy.md)**

---

## 🛠️ Development Workflow

### Quick Setup
```bash
composer setup       # Install + configure + migrate
composer dev         # Start all services
```

### Development Commands
```bash
composer test        # Run all test suites
composer lint        # Check code style
composer format      # Auto-format code
npm run dev          # Watch assets
```

### Testing
- **Arch Tests**: Architecture compliance
- **Unit Tests**: Individual component logic
- **Feature Tests**: Business workflows
- **Browser Tests**: Livewire UI interactions (Dusk)

---

## 📊 Module System

Internara contains **29+ independent modules** organized by domain:

- **Identity**: Auth, User, Profile, Permission
- **Lifecycle**: Internship, Setup, Student, Mentor, Teacher
- **Monitoring**: Journal, Attendance, Schedule
- **Academic**: Assessment, Assignment, School, Department, Guidance
- **Operations**: Report, Notification, Log, Setting, Media
- **Infrastructure**: Core, Shared, UI, Status, Exception, Admin, Support

Each module follows a strict structure:
```
modules/{ModuleName}/
├── src/{Models,Services,Services/Contracts,Livewire}
├── tests/{Unit,Feature,Browser,Arch}
├── database/{migrations,seeders,factories}
└── resources/{css,js,lang}
```

Learn more in the **[Modules Catalog](./modules-catalog.md)** and **[Architecture Guide](./architecture.md)**

---

## 🧪 Quality Assurance

**Before submitting a PR:**
```bash
composer test        # Must pass all suites
composer lint        # Must have no violations
```

**Required in every PR:**
- ✅ 90%+ test coverage
- ✅ `declare(strict_types=1);` on all PHP files
- ✅ No hardcoded strings (use `__('key')`)
- ✅ New models use `HasUuid` + `timestamps()`
- ✅ Services implement Contracts
- ✅ Livewire managers extend `RecordManager`
- ✅ Documentation updated

Learn more in the **[Testing Guide](./testing.md)** and **[Standards Guide](./standards.md)**

---

## 🔗 Quick Links

- **GitHub Repository**: [github.com/reasvyn/internara](https://github.com/reasvyn/internara)
- **Issues**: [GitHub Issues](https://github.com/reasvyn/internara/issues)
- **Discussions**: [GitHub Discussions](https://github.com/reasvyn/internara/discussions)
- **Lead Developer**: [reasvyn](https://github.com/reasvyn)

---

## 📄 License

Internara is licensed under the **MIT License**. See [LICENSE](../LICENSE) for details.

---

**Last Updated**: 2026-04-22

*For more information, visit the main [README.md](../README.md)*
