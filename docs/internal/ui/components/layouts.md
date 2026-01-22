# UI Components: Structural Layouts

Layout components are the scaffolding of the Internara application. They manage the global HTML
structure, meta-tags, responsive containers, and modular UI injection points.

> **Spec Alignment:** Layouts implement the **Mobile-First** and **Visual Identity** requirements
> of the **[Internara Specs](../../internara-specs.md)**.

---

## 1. `x-ui::layouts.base` (The Root)

The foundational HTML structure for every page.

- **Branding (Specs 5.1):** Uses **Instrument Sans** and enforces the system theme (Light/Dark).
- **Feedback:** Includes the global notification bridge for system-wide alerts.
- **Settings:** The `<title>` and Favicon are dynamic, retrieved via `setting('site_title')`.

---

## 2. `x-ui::main` (The Responsive Workspace)

This component is the engine of our **Mobile-First** strategy.

- **Mobile Behavior:** Sidebar is hidden by default and accessible via a **Drawer** (Burger menu).
- **Desktop Behavior:** Sidebar becomes persistent on screens larger than `lg`.
- **Primary Color:** All layout accents (active links, border highlights) use **Emerald Green**.

### Supported Slots:
- `sidebar`: The navigation area (Drawer-compatible).
- `actions`: Page-level primary actions (Optimized for touch targets on mobile).
- `header`: Page title and breadcrumbs (Responsive sizing).

---

## 3. `x-ui::layouts.guest` (Public Pages)

Used for Auth pages (Login) and Public Verification (Certificates).

- **Goal:** Focused, minimalist container.
- **Mobile-First:** Centered cards that scale to full-width on small screens.

---

## 4. `x-ui::toast` (Feedback Layer)

- **Behavior:** Renders in a fixed position (bottom-right on desktop, top-center on mobile).
- **i11n:** All toast messages must be localized via `__('module::exceptions.key')`.

---

_Layouts ensure that whether a user is an Instructor on a desktop or a Student on a phone, the Internara experience remains cohesive and professional._