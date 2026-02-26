# Contributing to Internara

Thank you for your interest in contributing to Internara! We welcome contributions from everyone who
shares our commitment to building high-quality, resilient software.

## 🚀 Getting Started

To ensure a smooth contribution process, please follow these steps:

1.  **Fork the repository** on GitHub.
2.  **Create a new branch** following our naming convention (`feature/module/description`).
3.  **Plan your change**: Create or update an **Architectural Blueprint** in
    `docs/dev/blueprints/` to define your design intent.
4.  **Implement your changes**: Adhere to the authoritative
    **[Coding Conventions](docs/dev/conventions.md)**.
5.  **Verify**: Execute the full verification suite via **`composer test`** and ensure static
    analysis passes via **`composer lint`**.
6.  **Synchronize Artifacts**: Update relevant documentation and analytical release notes.
7.  **Submit a Pull Request**: Ensure your PR satisfies the requirements in the
    **[Git Protocols](docs/dev/git.md)**.

## 🛠 Engineering Standards

- **Strict Typing:** Every PHP file must declare `strict_types=1`.
- **Localization Invariant:** Zero hard-coded strings. All user-facing text must use `__('key')`.
- **Identity:** Mandatory use of **UUID v4** for all entities.
- **TDD-First:** Verification must utilize the **`test(...)`** pattern via **Pest v4**.

## 🔐 Security Vulnerabilities

If you discover a security vulnerability, please refer to our **[Security Policy](SECURITY.md)** for
the responsible disclosure process.

## ⚖️ License

By contributing, you agree that your contributions will be licensed under the **MIT License**.
