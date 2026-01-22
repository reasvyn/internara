# UI Components: Input & Form Handling

Internara provides a library of standardized form components that ensure user inputs are 
accessible, localized, and consistent across all user roles.

> **Spec Alignment:** Form components implement the **Mobile-First** and **Multi-Language** 
> mandates of the **[Internara Specs](../../internara-specs.md)**.

---

## 1. Standard Input Wrappers

These components are optimized for Livewire's `wire:model` and provide automatic localized error handling.

### 1.1 `x-ui::input`
A standard text input field.
- **i11n:** All labels and hints MUST be localized via `__('module::ui.key')`.
- **Validation:** Displays error messages retrieved from the module's `lang/validation.php`.

### 1.2 `x-ui::select`
A dropdown selector.
- **Mobile-First:** Uses native select styling on mobile devices to leverage the OS's picker.

---

## 2. Interactive Actions

### 2.1 `x-ui::button`
Primary action trigger.
- **Primary Color:** Defaults to **Emerald Green** accent.
- **Touch Friendly:** Sized to meet touch target standards (Specs 2.2).
- **Feedback:** Includes built-in `spinner` for Livewire actions.

### 2.2 `x-ui::file` (File Management)
Robust file upload with built-in preview and cropping.
- **i11n:** Support for localized "Click to upload" messages.
- **Security:** Integrated with `Media` module for secure file handling.

---

## 3. Structural Forms

### 3.1 `x-ui::form`
Wraps the HTML `<form>` tag and handles localized submission feedback.

---

## 4. Best Practices for Forms

1.  **Strict Localization:** Hardcoding labels or placeholders in English or Indonesian is 
    strictly prohibited. Use `__('key')`.
2.  **Explicit Labels:** Mandatory for accessibility.
3.  **No-Surprise Logic:** No business logic in form components. Use the **Service Layer**.
4.  **Dynamic Config:** Use `setting()` or `config()` for dynamic values like max file sizes or 
    accepted formats.

---

_Form components ensure that Instructors, Staff, and Students can provide data reliably and 
efficiently on any device._