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
];
