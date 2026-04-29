# Infrastructure and Dependencies: Internara

This document provides a comprehensive overview of the technical stack, infrastructure requirements, and dependencies for the `internara` project.

## 1. Core Runtime
- **PHP**: `^8.4`
- **Framework**: Laravel `^12.0`
- **Frontend Engine**: Livewire `^3.7` & Livewire Volt `^1.10`
- **Node.js**: Required for frontend asset bundling (Vite).
- **Package Managers**: 
  - `composer` for PHP dependencies.
  - `npm` or `pnpm` for JavaScript dependencies.

## 2. Technology Stack (The "MARY" Stack)
The project utilizes a modern Laravel stack focused on developer velocity and a "No-JS" (Livewire-heavy) approach:
- **UI Components**: [Mary UI](https://mary-ui.com/) `^2.4`
- **CSS Framework**: Tailwind CSS `^4.2` (via `@tailwindcss/vite`)
- **UI Library**: DaisyUI `^5.5`
- **Icons**: 
  - Blade Tabler Icons `^3.36`
  - Blade MDI Icons `^1.1`

## 3. Primary Backend Dependencies (Spatie Ecosystem & Others)
The project leverages high-quality industry-standard packages:
- **Access Control**: `spatie/laravel-permission` (Roles and Permissions)
- **Media Management**: `spatie/laravel-medialibrary`
- **Audit Trails**: `spatie/laravel-activitylog`
- **State Management**: `spatie/laravel-model-status` (Tracking model transitions)
- **Modular Structure**: `nwidart/laravel-modules` & `mhmiton/laravel-modules-livewire` (Note: Currently being phased out in favor of standardized MVC).
- **Notifications**: `php-flasher/flasher-laravel`
- **Security**: `spatie/laravel-honeypot` (Spam protection)

## 4. Utilities and Tools
- **PDF Generation**: `barryvdh/laravel-dompdf`
- **Localization**: `laravel-lang/lang` (Multi-language support)
- **QR Codes**: `simplesoftwareio/simple-qrcode`
- **Console Utilities**: `laravel/tinker`

## 5. Development and Testing
- **Testing Framework**: [Pest PHP](https://pestphp.com/) `^4.2`
- **Browser Testing**: Laravel Dusk `^8.3`
- **Code Style**: Laravel Pint (PHP) & Prettier (JS/Blade/PHP)
- **Static Analysis**: PHPStan `^2.1`
- **Debugging**: Laravel Pail, Laravel Boost, Mockery, Faker.
- **Environment**: Laravel Sail (Docker-based development environment).

## 6. Frontend Infrastructure
- **Bundler**: Vite `^7.3`
- **HTTP Client**: Axios
- **Image Processing**: Cropperjs
- **Plugins**: `@tailwindcss/vite`, `prettier-plugin-blade`, `@prettier/plugin-php`.

## 7. Configuration Summary (config/)
- **Activity Log**: Configured in `config/activitylog.php`.
- **Permissions**: Configured in `config/permission.php`.
- **Media Library**: Configured in `config/media-library.php`.
- **Modules**: Configuration for modular structure in `config/modules.php`.
- **Livewire**: Custom settings in `config/livewire.php` and `config/modules-livewire.php`.
- **Flasher**: Integration for flash messages in `config/flasher.php`.

## 8. Requirements for Deployment
- **Web Server**: Nginx or Apache (Laravel compatible).
- **Database**: MySQL, PostgreSQL, or SQLite (SQLite is mentioned in `post-create-project-cmd`).
- **Cache/Queue**: Redis or Database driver (Standard Laravel drivers).
- **File Storage**: Local or S3-compatible (Spatie Media Library requirement).
