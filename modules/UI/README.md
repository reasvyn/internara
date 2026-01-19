# UI Module

The `UI` module is the **"Headless"** source of truth for the application's visual language and
shared frontend components. It encapsulates common layouts, interactive elements, and design tokens,
but **does not** contain business logic, routes, or database tables.

## Purpose

- **Design System:** Enforces a consistent look and feel across all modules using Tailwind CSS,
  DaisyUI, and MaryUI.
- **Component Library:** Provides a comprehensive library of pre-built Blade and Livewire components
  (e.g., `<x-ui::button>`, `<x-ui::card>`).
- **No Side Effects:** This module is strictly for presentation. It does not handle HTTP requests or
  store data.

## Key Features

### 1. Global Components

- **Core Elements:** Buttons, Inputs, Cards, Modals, Badges.
- **Layouts:** Navbar, Sidebar, App Shell.
- **LanguageSwitcher:** A persistent UI component allowing users to toggle between English and
  Indonesian.

### 2. Design System Tokens

- Centralized configuration for DaisyUI themes and Tailwind extensions.
- Light/Dark mode compatibility built-in.

### 3. Slot System

- Implements a cross-module UI injection system (Slot Registry) that allows modules to register
  their own elements (like sidebar links) into global layouts without tight coupling.
