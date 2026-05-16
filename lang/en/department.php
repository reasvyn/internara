<?php

declare(strict_types=1);

return [
    'title' => 'Department Management',
    'subtitle' => 'Manage academic organizational units (Jurusan)',
    'add' => 'Add Department',
    'edit' => 'Edit Department',
    'new' => 'New Department',
    'delete_confirm' => 'Are you sure you want to delete this department? This action cannot be undone.',
    'delete_selected_confirm' => 'Are you sure you want to delete the selected departments? Only departments without students will be deleted.',
    'delete_blocked' => 'Cannot delete: this department has :count student profile(s) associated.',
    'selected_count' => '{0} departments selected|{1} department selected|[2,*] departments selected',
    'stats' => [
        'total' => 'Total Departments',
        'with_internships' => 'With Internships',
    ],
    'search_placeholder' => 'Search department...',
    'name' => 'Department Name',
    'name_placeholder' => 'e.g. Rekayasa Perangkat Lunak',
    'description' => 'Description',
    'created_at' => 'Created',
    'save_success_created' => 'Department created successfully.',
    'save_success_updated' => 'Department updated successfully.',
    'delete_success' => 'Department deleted successfully.',
    'cancel' => 'Cancel',
    'save' => 'Save',
    'import_invalid' => 'Invalid CSV format. The file must have a "name" column.',
    'import_summary' => ':created departments imported, :skipped skipped (duplicates).',
    'template_example_name' => 'e.g. Software Engineering',
    'template_example_description' => 'e.g. Department focused on software development',
];
