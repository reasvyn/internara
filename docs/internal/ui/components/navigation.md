# UI Components: Navigation & Branding

These components handle the user's journey through the Internara platform, ensuring accessibility 
and clear branding as defined in the **[Internara Specs](../../internara-specs.md)**.

---

## 1. `x-ui::navbar` (Global Access)

The top-level navigation bar.

- **Branding:** Displays the logo retrieved via `setting('brand_logo')` and name from `setting('brand_name')`.
- **Mobile-First:** Includes the burger menu button to toggle the sidebar drawer on small screens.
- **Role Awareness:** User profile menu displays the current User Role (Instructor, Student, etc.).

---

## 2. `x-ui::sidebar` (Workspace Menu)

The primary navigation for role-specific workspaces.

- **Responsive Logic:** 
    - **Desktop:** Persistent vertical menu.
    - **Mobile:** Hidden within an Alpine.js-powered Drawer.
- **i11n:** All menu item titles MUST be wrapped in `__('module::ui.menu_key')`.

---

## 3. `x-ui::brand` (Application Identity)

- **Requirement:** Hard-coding the brand name or logo is strictly prohibited.
- **Logic:** Uses `setting('brand_logo')` and `setting('brand_name')`.
- **Primary Color:** The branding text uses **Emerald Green** accent in light mode.

---

## 4. `x-ui::tabs` (In-Page Navigation)

Used for switching views within a domain (e.g., `Internship` details).

- **Mobile-First:** Tabs automatically convert to a horizontal scroll or a dropdown menu on 
  very small screens to prevent layout overflow.
- **State:** Integrated with `wire:model` for reactive state management.

---

_Navigation components ensure that Internara remains intuitive and consistent across all modules,
fully respecting the project's visual and multi-language standards._