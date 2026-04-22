# 📖 Internara Documentation Index

Welcome to the Internara documentation! This guide provides comprehensive resources for developers, contributors, and operators.

---

## 🚀 Getting Started

- **[Getting Started Guide](./getting_started.md)** — Quick overview of the project, prerequisites, and first steps
- **[Installation Guide](./installation.md)** — Detailed step-by-step installation, configuration, and setup wizard

---

## 📚 Core Documentation

| Document | Purpose |
| :--- | :--- |
| **[Installation Guide](./installation.md)** | Complete installation from git clone to production-ready setup |
| **[Getting Started Guide](./getting_started.md)** | Quick start for developers, feature flags, and debugging |
| **[Architecture Guide](../README.md#-architecture--implementation)** | Modular monolith design, auto-binding, and patterns |
| **[Code Conventions](../README.md#-conventions--standards)** | PSR-12, strict types, naming, and code patterns |
| **[Testing Guide](../README.md#-testing--quality-assurance)** | Test suites, requirements, and quality gates |
| **[Contributing Guide](../CONTRIBUTING.md)** | Workflow, code patterns, and PR checklist |

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

Learn more: [3S Doctrine](../README.md#-philosophy-the-3s-doctrine)

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

Learn more: [Module Architecture](../README.md#module-directory-29-modules)

---

## 🧪 Quality Assurance

**Before submitting a PR:**
```bash
composer test        # Must pass all suites
composer lint        # Must have no violations
```

**Required in every PR:**
- ✅ 90%+ test coverage
- ✅ `declare(strict_types=1);` on all PHP
- ✅ No hardcoded strings
- ✅ New models use `HasUuid` + `timestamps()`
- ✅ Services implement Contracts
- ✅ Livewire managers extend `RecordManager`
- ✅ Documentation updated

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
