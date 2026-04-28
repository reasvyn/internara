<?php

declare(strict_types=1);

return [
    'steps' => 'Step :current of :total',
    'step_labels' => [
        'welcome' => 'Welcome',
        'school' => 'School',
        'account' => 'Administrator',
        'department' => 'Department',
        'internship' => 'Internship',
        'complete' => 'Complete',
    ],
    'status' => [
        'passed' => 'Passed',
        'failed' => 'Failed',
        'writable' => 'Writable',
        'not_writable' => 'Not Writable',
        'connected' => 'Connected',
        'disconnected' => 'Disconnected',
    ],
    'buttons' => [
        'back' => 'Back',
        'next' => 'Next',
        'continue' => 'Continue',
        'save_continue' => 'Save & Continue',
        'finish' => 'Finish',
    ],
    'common' => [
        'back' => 'Back',
        'save' => 'Save',
        'continue' => 'Continue',
        'save_continue' => 'Save & Continue',
        'finish' => 'Finish',
        'later_at_settings' => 'You can change these settings later in the settings page.',
        'step_success' => '[:step] completed successfully.',
        'admin_account' => 'Admin',
    ],
    'welcome' => [
        'title' => 'System Initialization',
        'headline' => 'Empowering Institutions, Transforming Internship Experiences.',
        'problem' => [
            'title' => 'Overcoming Complexity',
            'description' =>
                'Managing industrial internships shouldn\'t feel like solving an impossible puzzle of logistics and compliance.',
        ],
        'solution' => [
            'title' => 'Integrated Ecosystem',
            'description' =>
                ':app serves as your strategic partner, orchestrating every workflow so you can focus on student growth.',
        ],
        'journey' => [
            'title' => 'Seamless Journey',
            'description' =>
                'This initialization process is your first step towards a highly organized and data-driven internship program.',
            'description_short' =>
                'Experience a streamlined deployment designed for modern academic and corporate standards.',
        ],
        'cta' => 'Begin Initialization',
    ],
    'environment' => [
        'title' => 'Infrastructure Audit',
        'description' =>
            'Verifying system environment and directory integrity for enterprise-grade stability.',
        'audit_refreshed' => 'Environment audit re-evaluated successfully.',
        'requirements' => 'Core Dependencies',
        'requirements_desc' =>
            'Mandatory PHP extensions and versioning required for Internara to operate.',
        'permissions' => 'Storage Integrity',
        'permissions_desc' =>
            'Ensuring the system has appropriate write access to critical storage layers.',
        'database' => 'Data Persistence',
        'database_desc' => 'Validating connectivity to the configured database engine.',
        'functions' => 'System Capabilities',
        'functions_desc' =>
            'Checking for critical PHP functions required for system orchestration.',
        'db_connection' => 'Handshake Status',
        'refresh' => 'Re-run Audit',
        'audit' => [
            'php_version' => 'PHP Version (>= :version)',
            'php_extension' => 'PHP Extension: :extension',
            'storage_root' => 'Root Storage Directory',
            'storage_logs' => 'Storage Logs Directory',
            'storage_framework' => 'Storage Framework Directory',
            'bootstrap_cache' => 'Bootstrap Cache Directory',
            'env_file' => 'Environment File (.env)',
            'db_connected' => 'Database connection established.',
        ],
    ],
    'account' => [
        'title' => 'Create Administrator Account',
        'headline' => 'Every Great Journey Needs a Leader.',
        'description' =>
            'This account will be your command center. With this account, you will direct the internship program flow within :app, manage users, and ensure everything runs smoothly. Let\'s set up your main administrator account.',
    ],
    'school' => [
        'title' => 'Set Up School Data',
        'headline' => 'Building Your School\'s Identity.',
        'description' =>
            'This information will be the foundation of the entire system, ensuring every document, report, and communication carries your school\'s unique identity. Let\'s introduce your institution to :app.',
    ],
    'department' => [
        'title' => 'Set Up Department Data',
        'headline' => 'Preparing Skill Pathways.',
        'description' =>
            'Each department is a unique pathway that students will take. By defining these departments, we facilitate internship placements that match their skills. Enter the departments that exist in your school.',
    ],
    'internship' => [
        'title' => 'Set Up Internship Data',
        'headline' => 'Defining the Internship Period.',
        'description' =>
            'Now, let\'s define the internship period or academic year to be managed. This will be the main \'container\' for all future internship activities.',
    ],
    'complete' => [
        'title' => 'Setup Complete',
        'badge' => '🎉 One Last Touch! 🎉',
        'headline' => 'Finalization and Synchronization: :app Ready for Action! ✨',
        'description' =>
            'This is the final touch—like an artist signing their work. This step will bring together everything we have prepared, activate all modules, and ensure :app is ready to serve you fully.',
        'description_extra' =>
            'With one final click, you will open the door to a new internship management experience. Ready to start this new chapter?',
        'cta' => 'Complete Installation',
        'checkup_title' => 'System Readiness Check-up',
        'checkup_desc' =>
            'Please review and confirm your compliance with system governance standards.',
        'checkup' => [
            'data_verified_label' => 'Data Integrity Confirmation',
            'data_verified_desc' =>
                'I have reviewed the school, department, and program data. I confirm that all entered information is accurate and reflects the official status of the institution.',
            'security_aware_label' => 'Security Sovereignty Acknowledgment',
            'security_aware_desc' =>
                'I understand that my SuperAdmin account holds absolute authority. I commit to maintaining credential secrecy and following enterprise security protocols to protect institutional data.',
            'legal_agreed_label' => 'Legal & Regulatory Compliance',
            'legal_agreed_desc' =>
                'I agree to the :privacy and :terms. I commit to operating Internara in compliance with applicable data protection laws.',
        ],
    ],
    'audit_logs' => [
        'step_completed' => 'Setup step [:step] completed successfully.',
        'finalized' => 'Application setup finalized and system locked down.',
    ],
];
