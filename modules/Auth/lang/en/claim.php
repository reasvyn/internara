<?php

declare(strict_types=1);

return [
    'page_title' => 'Claim Account',
    'title' => 'Activate Your Account',
    'subtitle_step1' => 'Enter your username and the activation code you received.',
    'subtitle_step2' => 'Choose a personal password to secure your account.',
    'step_verify' => 'Verify Code',
    'step_password' => 'Set Password',

    'info_step1' =>
        'Your activation code was given to you by your institution administrator. It looks like: XXXX-XXXX-XXXX.',
    'info_step2' => 'Choose a strong password. You will use this to log in from now on.',
    'code_verified' => 'Code verified! Now set your personal password.',
    'back_to_login' => 'Back to login',

    'form' => [
        'username' => 'Username',
        'username_placeholder' => 'Enter your username',
        'activation_code' => 'Activation Code',
        'code_placeholder' => 'e.g. XXXX-XXXX-XXXX',
        'code_hint' => 'Case insensitive — dashes are optional.',
        'verify' => 'Verify Code',
        'password' => 'New Password',
        'password_placeholder' => 'Choose a strong password',
        'password_confirmation' => 'Confirm Password',
        'password_confirmation_placeholder' => 'Repeat your new password',
        'activate' => 'Activate Account',
    ],

    'invalid_code' => 'The username or activation code is incorrect, or the code has expired.',
    'token_expired' =>
        'Your activation code expired before you completed the process. Please contact your administrator to issue a new one.',
    'throttled' => 'Too many attempts. Please wait a few minutes before trying again.',
    'success' => 'Your account has been activated! You can now log in with your new password.',
];
