# UI Components: System Utilities

Utility components handle technical UI concerns like theme persistence, dialog modals, and modular 
content injection.

---

## 1. `x-ui::slot-render` (Modular Injection)

The core of our "Pluggable UI" system.

- **Objective:** Ensures modular isolation mandated by the **Modular Monolith** architecture.
- **Usage:** `<x-ui::slot-render name="navbar.actions" />`.

---

## 2. `x-ui::modal` (Focused Interaction)

Standard wrapper for dialog interactions.

- **Mobile-First:** Modals must scale to full-width or use a "Bottom Sheet" style on mobile devices 
  (Specs 2.2).
- **i11n:** All titles and button labels must be localized.

---

## 3. `x-ui::theme-toggle` (User Preference)

- **Specs Alignment:** Toggles between **Light** and **Dark** themes as defined in Specs 5.1.
- **Aesthetic:** Uses a minimalist icon set to maintain a clean UI.

---

## 4. `x-ui::user-menu` (Account Access)

- **Role Awareness:** Displays the user's role (Instructor, Staff, etc.) as defined in the 
  **[Internara Specs](../../internara-specs.md)**.
- **Localization:** All menu labels must be localized via `__('core::ui.user_menu')`.

---

_Utilities enable complex technical patterns while keeping the template code clean and semantic, 
fully aligned with Internara's architectural and visual standards._