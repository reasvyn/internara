<?php

declare(strict_types=1);

return [
    'register_super_admin' => [
        'title' => 'Register Administrator Account',
        'subtitle' => 'Create a primary account to manage all system data.',
        'form' => [
            'name' => 'Full Name',
            'name_placeholder' => 'Enter your full name',
            'email' => 'Email Address',
            'email_placeholder' => 'Enter your email address',
            'password' => 'Password',
            'password_placeholder' => 'Create your password',
            'password_hint' => 'Attention! Use a hard-to-guess password combination.',
            'password_confirmation' => 'Confirm Password',
            'password_confirmation_placeholder' => 'Repeat your password',
            'submit' => 'Create Account',
            'footer_warning' => 'Be careful! Do not share account information with anyone.',
        ],
    ],
];
