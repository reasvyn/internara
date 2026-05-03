<?php

declare(strict_types=1);

return [
    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'title' => 'Authentication',
    'login' => [
        'title' => 'Sign in',
        'subtitle' => 'Secure Access to the Gateway',
        'identifier' => 'Identity',
        'password' => 'Passkey',
        'remember' => 'Remember',
        'forgot_password' => 'Reset Password',
        'submit' => 'Go to Dashboard',
        'welcome_back' => 'Welcome back, :name!',
        'back_to_login' => 'Back to login',
    ],
    'forgot_password' => [
        'subtitle' => 'Enter your email to receive a reset link',
        'email' => 'Email address',
    ],
    'reset_password' => [
        'subtitle' => 'Create a strong new password for your account',
        'email' => 'Email address',
        'password' => 'New password',
        'password_confirmation' => 'Confirm new password',
    ],
    'logout' => 'Logout',
    'login_success' => 'Login successful!',
    'logout_success' => 'You have been logged out.',
    'invalid_credentials' => 'Invalid email or password.',
    'email_reset_link' => 'We have emailed your password reset link.',
    'password_reset_success' => 'Your password has been reset.',
];
