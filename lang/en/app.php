<?php

return [
    'name' => config('app.name'),

    'nav' => [
        'brand' => 'New Beginning',
        'features' => 'Features',
        'how_it_works' => 'How It Works',
        'listings' => 'Listings',
        'login' => 'Log in',
        'get_started' => 'Get Started',
        'dashboard' => 'Dashboard',
        'sign_out' => 'Sign Out',
        'open_menu' => 'Open menu',
        'close_menu' => 'Close menu',
    ],

    'footer' => [
        'brand' => 'New Beginning',
        'tagline' => 'Your trusted property marketplace for buying, selling, and renting.',
        'company' => 'Company',
        'legal' => 'Legal',
        'connect' => 'Connect',
        'about' => 'About Us',
        'careers' => 'Careers',
        'press' => 'Press',
        'contact' => 'Contact',
        'privacy_policy' => 'Privacy Policy',
        'terms_of_service' => 'Terms of Service',
        'cookie_policy' => 'Cookie Policy',
        'gdpr' => 'GDPR',
        'twitter' => 'Twitter',
        'linkedin' => 'LinkedIn',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'copyright' => ':year New Beginning. All rights reserved.',
        'privacy' => 'Privacy',
        'terms' => 'Terms',
        'cookies' => 'Cookies',
    ],

    'auth' => [
        'login' => [
            'title' => 'Sign In',
            'subtitle' => 'Welcome back.',
            'email' => 'Email Address',
            'password' => 'Password',
            'forgot' => 'Forgot your password?',
            'submit' => 'Sign In',
            'no_account' => "Don't have an account?",
            'create_one' => 'Create one',
        ],

        'register' => [
            'title' => 'Create Account',
            'subtitle' => 'Join the marketplace in a few steps.',
            'account_type' => 'Account Type',
            'regular_label' => 'Regular User',
            'regular_description' => 'Browse, save, and create personal listings.',
            'agency_label' => 'Agency',
            'agency_description' => 'Manage an agency account and agency listings.',
            'name' => 'Name',
            'email' => 'Email Address',
            'password' => 'Password',
            'confirm_password' => 'Confirm Password',
            'terms_prefix' => 'I accept the',
            'terms_link' => 'Terms & Conditions',
            'privacy_prefix' => 'I accept the',
            'privacy_link' => 'Privacy Policy',
            'submit' => 'Create Account',
            'have_account' => 'Already have an account?',
            'sign_in' => 'Sign in',
        ],

        'forgot_password' => [
            'title' => 'Forgot Password',
            'subtitle' => "Enter your email address and we'll send you a link to reset your password.",
            'email' => 'Email Address',
            'submit' => 'Send Reset Link',
            'back_to_login' => '← Back to sign in',
        ],

        'reset_password' => [
            'title' => 'Reset Password',
            'subtitle' => 'Choose a new password for your account.',
            'email' => 'Email Address',
            'password' => 'New Password',
            'confirm_password' => 'Confirm New Password',
            'submit' => 'Reset Password',
        ],
    ],

    'landing' => [
        'title' => 'Home',

        'hero' => [
            'headline_line1' => 'Find Your Perfect',
            'headline_line2' => 'New Beginning',
            'subheading' => 'Discover verified properties for sale and rent. Connect directly with trusted owners on a moderated, premium marketplace.',
            'cta' => 'Browse Properties',
            'stats' => [
                ['value' => '10K+', 'label' => 'Listings'],
                ['value' => '5K+', 'label' => 'Agencies'],
                ['value' => '25K+', 'label' => 'Users'],
            ],
        ],

        'features' => [
            'eyebrow' => 'Features',
            'items' => [
                ['title' => 'Smart Search', 'description' => 'Find exactly what you need with powerful filters.'],
                ['title' => 'Verified Listings', 'description' => 'Every property is reviewed and approved before going live.'],
                ['title' => 'Direct Chat', 'description' => 'Message property owners and agencies in real time.'],
                ['title' => 'Save Favorites', 'description' => 'Bookmark the homes you love and revisit them anytime.'],
            ],
        ],

        'how_it_works' => [
            'eyebrow' => 'How It Works',
            'heading_line1' => 'Four simple steps',
            'heading_line2' => 'to your new home',
            'steps' => [
                ['number' => '01', 'title' => 'Create Account', 'description' => 'Sign up as a buyer, seller, or agency in seconds.'],
                ['number' => '02', 'title' => 'Explore', 'description' => 'Browse verified listings with smart filters.'],
                ['number' => '03', 'title' => 'Connect', 'description' => 'Chat directly with property owners.'],
                ['number' => '04', 'title' => 'Move In', 'description' => 'Find your perfect space and begin.'],
            ],
        ],

        'cta' => [
            'heading' => 'Ready to find your new beginning?',
            'paragraph' => 'Join thousands who found their perfect property through our marketplace.',
            'browse' => 'Browse Listings',
            'list_property' => 'List Property',
        ],
    ],
];
