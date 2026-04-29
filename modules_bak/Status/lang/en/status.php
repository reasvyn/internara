<?php

declare(strict_types=1);

return [
    // Lifecycle states
    'pending' => 'Pending Activation',
    'activated' => 'Activated',
    'verified' => 'Verified',
    'protected' => 'Protected (Super Admin)',
    'restricted' => 'Restricted',
    'suspended' => 'Suspended',
    'inactive' => 'Inactive',
    'archived' => 'Archived',

    // Generic
    'unknown' => 'Unknown',

    // Descriptions (for UI tooltips)
    'descriptions' => [
        'pending' => 'New account, awaiting activation',
        'activated' => 'Activated by user, awaiting verification',
        'verified' => 'Account active and verified',
        'protected' => 'Super Admin account - immutable',
        'restricted' => 'Temporary restriction - limited functionality',
        'suspended' => 'Account suspended - no access',
        'inactive' => 'No login for 180+ days',
        'archived' => 'Permanent archive - awaiting deletion',
    ],

    // Quick action reasons
    'quick_actions' => [
        'verify_reason' => 'Quick verified by :name',
        'suspend_reason' => 'Quick suspended by :name',
        'unlock_reason' => 'Quick unlocked',
    ],

    // Role labels
    'roles' => [
        'student' => 'Siswa',
        'teacher' => 'Guru Pembimbing',
        'mentor' => 'Pembimbing Industri',
        'admin' => 'Admin',
    ],
];
