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
    'login' => [
        'title' => 'Sign In to Your Account',
        'subtitle' => 'Use your email or username to continue.',
        'form' => [
            'identifier' => 'Email or Username',
            'identifier_placeholder' => 'Enter your Email or Username',
            'password' => 'Password',
            'password_placeholder' => 'Enter your password',
            'forgot_password' => 'Forgot your password?',
            'remember_me' => 'Remember me',
            'submit' => 'Sign In Now',
            'no_account' => 'Don\'t have an account?',
            'register_now' => 'Register Now',
        ],
    ],
    'register' => [
        'title' => 'Register Student Account',
        'subtitle' => 'Fill in your details to start your internship journey.',
        'form' => [
            'name' => 'Full Name',
            'name_placeholder' => 'Enter your full name',
            'email' => 'Email Address',
            'email_placeholder' => 'Enter your email address',
            'password' => 'Password',
            'password_placeholder' => 'Create your password',
            'password_confirmation' => 'Confirm Password',
            'password_confirmation_placeholder' => 'Repeat your password',
            'submit' => 'Register Now',
            'policy_agreement' => 'By clicking **Register Now**, you automatically agree to our [Privacy Policy](:url).',
            'has_account' => 'Already have an account?',
            'login_now' => 'Sign In',
        ],
    ],
    'verification' => [
        'title' => 'Verify Your Email',
        'subtitle' => 'Check your inbox for a verification link.',
        'notice' => 'Before proceeding, please check your email for a verification link.',
        'resend_prompt' => 'Didn\'t receive the email?',
        'resend_button' => 'Click here to request another',
        'verify_button' => 'Verify Email',
    ],
];
