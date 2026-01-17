# UI Module

The `UI` module is the source of truth for the application's visual language and shared frontend
components. It encapsulates common layouts, interactive elements, and design tokens.

## Purpose

- **Design System:** Enforces a consistent look and feel across all modules using Tailwind CSS,
  DaisyUI, and MaryUI.
- **Component Reusability:** Provides a library of high-quality, pre-built Blade and Livewire
  components.
- **Localization UI:** Manages global interface elements for language switching.

## Key Features

### 1. Global Components

- **Navbar & Sidebar:** Standardized navigation structures.
- **LanguageSwitcher (Livewire):** A persistent UI component allowing users to toggle between
  English and Indonesian in real-time.

### 2. Design System Tokens

- Centralized configuration for DaisyUI themes and Tailwind extensions.

### 3. Slot System

- Implements a cross-module UI injection system (Slot Registry) that allows modules to register
  their own elements (like sidebar links) into global layouts without tight coupling.

---

**Navigation** [← Back to Module TOC](table-of-contents.md)
