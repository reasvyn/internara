# UI Components: Data Display

Display components are designed to present complex data in a readable, professional manner. They
follow the Internara aesthetic of high-contrast layering and semantic grouping.

---

## 1. `x-ui::card` (Information Grouping)

The primary container for UI sections.

- **Example**:

```blade
<x-ui::card title="Student Profile" subtitle="Academic record for 2025" shadow separator>
    <div class="p-4">Content here...</div>
</x-ui::card>
```

- **Feature**: Supports `shadow`, `separator`, and `border` props for visual hierarchy.

---

## 2. `x-ui::table` (Data Grids)

A high-level wrapper for MaryUI data tables.

- **Example**:

```blade
<x-ui::table :headers="$headers" :rows="$students" with-pagination>
    @scope('cell_status', $student)
        <x-ui::badge :label="$student->status" />
    @endscope
</x-ui::table>
```

- **Convention**: Always use `@scope` for custom column rendering.

---

## 3. `x-ui::badge` & `x-ui::avatar` (Indicators)

- **Badge**: Used for statuses (e.g., `<x-ui::badge label="Approved" success />`).
- **Avatar**: Used for user identity (e.g., `<x-ui::avatar :image="$user->avatar_url" />`).

---

## 4. `x-ui::icon` (Tabler Set)

We use the **Tabler Icon** library exclusively.

- **Standard**: `<x-ui::icon name="tabler.settings" class="w-5 h-5 text-primary" />`.
- **Note**: The `tabler.` prefix is required by our SVG loader.

---

## 5. `x-ui::alert` (Feedback)

Used for critical warnings or inline instructions.

- **Usage**: `<x-ui::alert icon="tabler.info-circle" title="Notice" info>Content</x-ui::alert>`.

---

_Display components should be used to minimize cognitive load. Use cards to group related fields and
badges to highlight state changes._
