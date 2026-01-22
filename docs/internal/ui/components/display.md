# UI Components: Data Display

Display components present complex data in a readable manner, following the **Minimalist** 
aesthetic defined in the **[Internara Specs](../../internara-specs.md)**.

---

## 1. `x-ui::card` (Information Grouping)

Primary container for UI sections.

- **Design (Specs 5.1):** High-contrast layering with standardized rounded corners.
- **Mobile-First:** Padding automatically scales down on small screens to maximize space.

---

## 2. `x-ui::table` (Data Grids)

Standardized data tables for list views.

- **Mobile-First Strategy:** 
    - Tables must support horizontal scrolling on mobile.
    - **Card View:** Use conditional rendering to switch from a table to a list of cards on 
      small screens (if the dataset is complex).
- **i11n:** All column headers must be localized.

---

## 3. `x-ui::badge` & `x-ui::avatar` (Indicators)

- **Badge:** Status indicators (e.g., `Approved`, `Pending`). Colors follow the theme variables.
- **Avatar:** User identity. Default placeholders must be visually consistent with the theme.

---

## 4. `x-ui::icon` (Tabler Set)

- **Library:** **Tabler Icons**.
- **Usage:** Standardized stroke width and size.
- **Color:** Icons should use the `primary` (Emerald Green) or `base-content` colors.

---

## 5. `x-ui::alert` (Feedback)

Used for instructions or critical warnings.

- **i11n:** All alert messages must be localized via `__('module::ui.key')` or `__('exception::messages.key')`.

---

_Display components minimize cognitive load, helping Instructors and Staff quickly identify 
student progress and competency achievements._