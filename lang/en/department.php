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
    'confirm_delete_selected' => 'Are you sure you want to delete the selected departments? Only departments without students will be deleted.',
    'delete_blocked' => 'Cannot delete: this department has :count student profile(s) associated.',
    'selected_count' => '{0} departments selected|{1} department selected|[2,*] departments selected',
    'stats' => [
        'total' => 'Total Departments',
        'with_students' => 'Has Students',
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
    'delete_success_bulk' => '{0} No departments deleted|{1} 1 department deleted|[2,*] :count departments deleted.',
    'delete_blocked_bulk' => '{0} No departments skipped|{1} 1 department skipped (has profiles)|[2,*] :count departments skipped (have profiles).',
    'import_invalid' => 'Invalid CSV format. The file must have a "name" column.',
    'import_summary' => ':created departments imported, :skipped skipped (duplicates).',
    'template_example_name' => 'e.g. Software Engineering',
    'template_example_description' => 'e.g. Department focused on software development',

    'guide' => [
        'title' => 'Department Guide',
        'intro' => 'Manage academic organizational units (departments/competency areas):',
        'create_title' => 'Adding a Department',
        'create_desc' => 'Create a new department with a name and description. The department name must be unique in the system.',
        'edit_title' => 'Editing a Department',
        'edit_desc' => 'Update the name or description of an existing department. Changes apply system-wide immediately.',
        'import_title' => 'CSV Import',
        'import_desc' => 'Import multiple departments at once from a CSV file. First column: name, second column: description (optional).',
        'delete_title' => 'Deleting a Department',
        'delete_desc' => 'Departments that still have student profiles cannot be deleted. Move students first if needed.',
    ],
];
