<?php

declare(strict_types=1);

return [
    'title' => 'About',
    'about_app' => 'About :app',
    'subtitle' => 'Vocational Fieldwork Management System',
    'lead' => 'Self-hosted, single-tenant, and privacy-first fieldwork management platform purpose-built for vocational schools and academies.',
    'overview' => [
        'heading' => 'Application Overview',
        'subheading' => 'Designed to elevate vocational internship operations with dignity and precision.',
        'description' => ':app is a specialized management system designed to coordinate the entire lifecycle of student vocational fieldwork (PKL / Praktik Kerja Lapangan). From company partnerships, mentor assignment, student logbooks, attendance monitoring, and rubric grading to official certificate issuance, :app puts educational sovereignty and operational reliability at the forefront.',
    ],
    'author' => [
        'heading' => 'Author & Attribution',
        'subheading' => 'System authorship and architectural custody.',
        'role' => 'Creator & Lead Architect',
        'bio' => 'Original architect and lead developer of :app. Dedicated to providing robust, accessible, and self-hosted digital solutions for vocational institutions without vendor lock-in.',
        'email_label' => 'Developer Contact',
        'github_label' => 'GitHub Profile',
        'attribution_notice' => 'System attribution is an architectural invariant governed by AppIntegrity. Attribution to the original author is required by the project license and core invariants.',
    ],
    'philosophy' => [
        'heading' => 'Guiding Principles',
        'subheading' => 'The foundational philosophy that shapes every workflow in :app.',
        'education_first' => [
            'title' => 'Education First, Technology Second',
            'desc' => 'Technology serves education, not the other way around. Every feature is measured by whether it makes running internship programs simpler for schools.',
        ],
        'great_tools' => [
            'title' => 'Every School Deserves Great Tools',
            'desc' => 'Designed to run efficiently from shared hosting to dedicated cloud clusters, ensuring resource-constrained schools enjoy the same power as flagship institutions.',
        ],
        'simplicity' => [
            'title' => 'Simplicity Over Complexity',
            'desc' => 'Favoring Single Responsibility Actions and transparent modular boundaries over opaque abstractions and unnecessary microservices.',
        ],
        'maintainability' => [
            'title' => 'Maintainability Is a Feature',
            'desc' => 'High coding standards, strict type safety, complete test coverage, and comprehensive living specifications ensure the codebase remains maintainable for years.',
        ],
        'data_sovereignty' => [
            'title' => 'Data Sovereignty & Privacy',
            'desc' => 'Your student records, grades, and company evaluations reside on your infrastructure. Zero external telemetry, zero unauthenticated third-party tracking.',
        ],
    ],
    'tech_stack' => [
        'heading' => 'Technology Architecture',
        'subheading' => 'Built with modern, battle-tested open-source technologies.',
        'php' => 'PHP 8.4 with strict typing and modern attributes',
        'laravel' => 'Laravel 13 framework core',
        'livewire' => 'Livewire 4 reactive UI without heavy SPA baggage',
        'tallstackui' => 'TallstackUI v4 accessible component library',
        'tailwind' => 'Tailwind CSS v4 modern utility engine',
        'alpine' => 'Alpine.js 3 lightweight client reactivity',
        'pest' => 'Pest 4 testing framework',
    ],
    'diagnostics' => [
        'heading' => 'System Diagnostics',
        'subheading' => 'Safe, non-sensitive environment metadata for support and issue tracking.',
        'app_version' => 'Application Version',
        'environment' => 'Runtime Environment',
        'php_version' => 'PHP Runtime',
        'laravel_version' => 'Framework Version',
        'license' => 'Software License',
        'repository' => 'Official Repository',
        'copy_button' => 'Copy Diagnostic Information',
        'copied_toast' => 'Diagnostic information copied to clipboard.',
    ],
    'license' => [
        'heading' => 'License & Open Source',
        'text' => ':app is free and open-source software distributed under the permissive MIT License. You are free to self-host, adapt, and run this system for your educational institution.',
        'view_repo' => 'View on GitHub',
    ],
];
