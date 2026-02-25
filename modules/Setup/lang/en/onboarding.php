<?php

declare(strict_types=1);

return [
    'errors' => [
        'file_not_readable' => 'The CSV file could not be found or is not readable.',
        'empty_file' => 'The provided CSV file is empty.',
        'column_mismatch' => 'Column count mismatch on row :row.',
        'required_fields' => 'Name and Email are mandatory fields.',
        'invalid_email' => 'The provided email address is invalid.',
    ],
    'messages' => [
        'import_completed' => 'Import operation completed: :success success, :failure failure.',
    ],
];
