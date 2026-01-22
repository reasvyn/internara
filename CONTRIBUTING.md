# Contributing to Internara

Thank you for considering contributing to Internara! We welcome contributions from everyone.

## Getting Started

1.  **Fork the repository** on GitHub.
2.  **Create a new branch** (`git checkout -b feature/amazing-feature`).
3.  **Plan your change**: Create an **Application Blueprint** in `docs/internal/blueprints/`.
4.  **Implement your changes**: Adhere to the **[Development Conventions](docs/internal/development-conventions.md)**.
5.  **Verify**: Run tests (`php artisan test --parallel`) and static analysis (`vendor/bin/pint`).
6.  **Artifact Sync**: Update relevant documentation and the changelog.
7.  **Submit a Pull Request**.

## Coding Standards

- **Strict Typing:** Every PHP file must use `declare(strict_types=1);`.
- **Localization:** No hardcoded strings. Use `__('key')`.
- **Identity:** Use UUIDs for all entities.
- **Testing:** Use **Pest v4**.

## Security Vulnerabilities

If you discover a security vulnerability, please refer to our **[Security Policy](SECURITY.md)**.

## License

By contributing, you agree that your contributions will be licensed under the MIT License.