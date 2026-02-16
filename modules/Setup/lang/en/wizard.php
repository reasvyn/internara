<?php

declare(strict_types=1);

return [
    'steps' => 'Step :current of :total',
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
        'continue' => 'Continue',
        'save_continue' => 'Save & Continue',
        'finish' => 'Finish',
        'later_at_settings' => 'You can change these settings later in the settings page.',
    ],
    'welcome' => [
        'title' => 'Start Installation',
        'headline' => 'End the Complexity, Welcome a Meaningful Internship Program.',
        'problem' => [
            'title' => 'Understanding Your Complexity',
            'description' => 'We know managing an internship program feels like putting together thousands of complex puzzle pieces.',
        ],
        'solution' => [
            'title' => 'Our Smart Solution',
            'description' => ':app is here as your partner, organizing every detail so you can focus on guiding students\' futures.',
        ],
        'journey' => [
            'title' => 'The Journey Begins',
            'description' => 'This setup process is your first step towards a more organized and impactful internship program.',
        ],
        'cta' => 'Start Installation',
        'toast_greeting' => 'Welcome to :app! Let\'s start configuring the system for a better internship management experience.',
    ],
    'environment' => [
        'title' => 'Environment Check',
        'description' => 'We need to make sure your server is ready to run :app smoothly.',
        'requirements' => 'System Requirements',
        'permissions' => 'Directory Permissions',
        'database' => 'Database Connectivity',
        'db_connection' => 'Database Connection',
    ],
    'account' => [
        'title' => 'Create Administrator Account',
        'headline' => 'Every Great Journey Needs a Leader.',
        'description' => 'This account will be your command center. With this account, you will direct the internship program flow within :app, manage users, and ensure everything runs smoothly. Let\'s set up your main administrator account.',
    ],
    'school' => [
        'title' => 'Set Up School Data',
        'headline' => 'Building Your School\'s Identity.',
        'description' => 'This information will be the foundation of the entire system, ensuring every document, report, and communication carries your school\'s unique identity. Let\'s introduce your institution to :app.',
    ],
    'department' => [
        'title' => 'Set Up Department Data',
        'headline' => 'Preparing Skill Pathways.',
        'description' => 'Each department is a unique pathway that students will take. By defining these departments, we facilitate internship placements that match their skills. Enter the departments that exist in your school.',
    ],
    'internship' => [
        'title' => 'Set Up Internship Data',
        'headline' => 'Defining the Internship Period.',
        'description' => 'Now, let\'s define the internship period or academic year to be managed. This will be the main \'container\' for all future internship activities.',
    ],
    'system' => [
        'title' => 'System Settings',
        'headline' => 'Ensure Communication Lines Are Open.',
        'description' => ':app needs to send important notifications, reports, and account confirmations via email. Configure your SMTP server to ensure every message reaches its destination.',
        'description_extra' => 'You can use a free SMTP service provider or one provided by your institution.',
        'test_connection' => 'Test Connection',
        'skip' => 'Skip for Now',
        'fields' => [
            'smtp_host' => 'SMTP Host',
            'smtp_port' => 'SMTP Port',
            'encryption' => 'Encryption',
            'username' => 'Username',
            'password' => 'Password',
            'from_email' => 'Sender Email',
            'from_name' => 'Sender Name',
        ],
    ],
    'complete' => [
        'title' => 'Setup Complete',
        'badge' => '🎉 One Last Touch! 🎉',
        'headline' => 'Finalization and Synchronization: :app Ready for Action! ✨',
        'description' => 'This is the final touch—like an artist signing their work. This step will bring together everything we have prepared, activate all modules, and ensure :app is ready to serve you fully.',
        'description_extra' => 'With one final click, you will open the door to a new internship management experience. Ready to start this new chapter?',
        'cta' => 'Finalize & Start Adventure',
    ],
];
