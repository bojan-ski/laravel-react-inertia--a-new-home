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

    'listings' => [
        'title' => 'Listings',
        'count_prefix' => 'Showing',
        'count_suffix' => 'properties',

        'nav' => [
            'back' => 'Back',
            'brand' => 'New Beginning',
            'listings' => 'Listings',
            'agencies' => 'Agencies',
            'favorites' => 'Favorites',
            'login' => 'Log in',
            'sign_up' => 'Sign Up',
            'dashboard' => 'Dashboard',
            'sign_out' => 'Sign Out',
            'open_menu' => 'Open menu',
            'close_menu' => 'Close menu',
        ],

        'filters' => [
            'search_placeholder' => 'Search by location, title…',
            'all' => 'All',
            'buy' => 'Buy',
            'rent' => 'Rent',
            'all_types' => 'All Types',
            'search' => 'Search',
            'reset' => 'Reset',
            'more_filters' => 'More filters',
            'city_placeholder' => 'City',
            'min_price' => 'Min price',
            'max_price' => 'Max price',
            'property_types' => [
                'house' => 'House',
                'flat' => 'Flat',
                'apartment' => 'Apartment',
                'cottage' => 'Cottage',
                'villa' => 'Villa',
                'condo' => 'Condo',
                'land' => 'Land',
                'commercial' => 'Commercial',
                'other' => 'Other',
            ],
            'sorts' => [
                'newest' => 'Newest First',
                'oldest' => 'Oldest First',
                'price_low_high' => 'Price: Low to High',
                'price_high_low' => 'Price: High to Low',
            ],
        ],

        'card' => [
            'for_sale' => 'For Sale',
            'for_rent' => 'For Rent',
            'per_month' => '/mo',
            'beds' => 'beds',
            'baths' => 'baths',
            'save' => 'Save listing',
            'saved' => 'Saved',
        ],

        'empty' => [
            'headline' => 'No listings found.',
            'hint' => 'Try adjusting your search or filters.',
            'reset' => 'Reset filters',
        ],

        'pagination' => [
            'page_of' => 'Page :current of :last',
            'previous' => 'Previous',
            'next' => 'Next',
        ],

        'show' => [
            'back' => 'Back to Listings',
            'for_sale' => 'For Sale',
            'for_rent' => 'For Rent',
            'bed' => 'Bed',
            'beds' => 'Beds',
            'bath' => 'Bath',
            'baths' => 'Baths',
            'built' => 'Built :year',
            'description_heading' => 'Description',
            'details_heading' => 'Property Details',
            'detail_address' => 'Address',
            'detail_property_type' => 'Property Type',
            'detail_listing_type' => 'Listing Type',
            'detail_year_built' => 'Year Built',
            'detail_listed' => 'Listed',
            'per_month' => '/mo',
            'save_listing' => 'Save Listing',
            'saved' => 'Saved',
            'start_chat' => 'Start Chat',
            'contact_heading' => 'Contact',
        ],
    ],

    'profile' => [
        'title' => 'My Listings',
        'subtitle' => 'Manage your personal property listings.',
        'account_settings' => 'Account Settings',
        'new_listing' => 'New Listing',
        'created' => 'Created :date',
        'rejection_reason_label' => 'Rejection reason',
        'per_month' => '/mo',
        'delete' => 'Delete listing',
        'flash_deleted' => 'Listing deleted.',

        'listing_types' => [
            'sale' => 'Sale',
            'rent' => 'Rent',
        ],

        'status' => [
            'pending_review' => 'Pending Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'hidden' => 'Hidden',
        ],

        'empty' => [
            'headline' => "You haven't created any listings yet.",
            'subline' => 'Create your first property listing to start sharing it on the marketplace.',
            'cta' => 'Create Listing',
        ],

        'confirm' => [
            'title' => 'Delete listing?',
            'description' => "Are you sure you want to delete ':title'? This cannot be undone.",
            'cancel' => 'Cancel',
            'confirm' => 'Delete',
        ],
    ],

    'profile_settings' => [
        'title' => 'Account Settings',
        'subtitle' => 'Update your personal account details and password.',
        'back' => 'Back to My Listings',

        'account' => [
            'heading' => 'Account Details',
            'note' => 'Update your name and email address.',
            'name' => 'Name',
            'email' => 'Email Address',
            'submit' => 'Save Changes',
            'flash' => 'Account details updated.',
        ],

        'password' => [
            'heading' => 'Password',
            'note' => 'Change your password by confirming your current one.',
            'current' => 'Current Password',
            'new' => 'New Password',
            'confirm' => 'Confirm New Password',
            'submit' => 'Update Password',
            'flash' => 'Password updated.',
        ],
    ],

    'bookmarks' => [
        'title' => 'My Bookmarks',
        'subtitle' => 'Saved listings you want to revisit.',
        'count_prefix' => 'Showing',
        'count_one' => 'saved listing',
        'count_other' => 'saved listings',
        'flash_added' => 'Listing saved to bookmarks.',
        'flash_removed' => 'Listing removed from bookmarks.',
        'empty' => [
            'headline' => "You haven't bookmarked any listings yet.",
            'cta' => 'Browse Listings',
        ],
    ],

    'create_listing' => [
        'title' => 'Create Listing',
        'subtitle' => 'Add your property details for admin review.',
        'back' => 'Back to My Listings',
        'submit' => 'Submit for Review',
        'cancel' => 'Cancel',
        'submit_note' => 'Your listing will be reviewed before becoming publicly visible.',
        'flash' => 'Listing submitted for review.',
        'contact_note' => 'Optional. If left blank, your account contact details will be used.',
        'sections' => [
            'basic' => 'Basic Listing Details',
            'location' => 'Location',
            'specs' => 'Property Specs',
            'contact' => 'Contact Info',
        ],
        'listing_types' => [
            'sale' => 'For Sale',
            'rent' => 'For Rent',
        ],
        'fields' => [
            'title' => 'Title',
            'listing_type' => 'Listing Type',
            'property_type' => 'Property Type',
            'price' => 'Price',
            'currency' => 'Currency',
            'description' => 'Description',
            'country' => 'Country',
            'city' => 'City',
            'address_line' => 'Address Line',
            'postal_code' => 'Postal Code',
            'bedrooms' => 'Bedrooms',
            'bathrooms' => 'Bathrooms',
            'area' => 'Area',
            'year_built' => 'Year Built',
            'contact_name' => 'Contact Name',
            'contact_email' => 'Contact Email',
            'contact_phone' => 'Contact Phone',
        ],
    ],

    'edit_listing' => [
        'title' => 'Edit Listing',
        'subtitle' => 'Update your property details.',
        'back' => 'Back to My Listings',
        'submit' => 'Save Changes',
        'cancel' => 'Cancel',
        'submit_note' => 'Your listing will be reviewed before becoming publicly visible.',
        'flash_updated' => 'Listing updated.',
        'flash_resubmitted' => 'Listing updated and resubmitted for review.',
        'blocked' => 'This listing can no longer be edited.',
        'approved_notice' => 'This listing is currently approved. Saving changes will send it back for admin review before it becomes publicly visible again.',
        'contact_note' => 'Optional. If left blank, your account contact details will be used.',
        'sections' => [
            'basic' => 'Basic Listing Details',
            'location' => 'Location',
            'specs' => 'Property Specs',
            'contact' => 'Contact Info',
        ],
        'listing_types' => [
            'sale' => 'For Sale',
            'rent' => 'For Rent',
        ],
        'fields' => [
            'title' => 'Title',
            'listing_type' => 'Listing Type',
            'property_type' => 'Property Type',
            'price' => 'Price',
            'currency' => 'Currency',
            'description' => 'Description',
            'country' => 'Country',
            'city' => 'City',
            'address_line' => 'Address Line',
            'postal_code' => 'Postal Code',
            'bedrooms' => 'Bedrooms',
            'bathrooms' => 'Bathrooms',
            'area' => 'Area',
            'year_built' => 'Year Built',
            'contact_name' => 'Contact Name',
            'contact_email' => 'Contact Email',
            'contact_phone' => 'Contact Phone',
        ],
    ],

    'admin' => [
        'nav' => [
            'eyebrow' => 'Admin',
            'dashboard' => 'Dashboard',
            'listings' => 'Listings',
            'agencies' => 'Agencies',
            'users' => 'Users',
            'reported_messages' => 'Reported Messages',
        ],
        'dashboard' => [
            'title' => 'Admin Dashboard',
            'subtitle' => 'Monitor listings, agencies, users, and moderation activity.',
            'sections' => [
                'listings' => 'Listings',
                'users' => 'Users',
                'moderation' => 'Moderation',
            ],
            'stats' => [
                'total_listings' => [
                    'label' => 'Total Listings',
                ],
                'pending_listings' => [
                    'label' => 'Pending Review',
                    'description' => 'Awaiting moderation',
                ],
                'approved_listings' => [
                    'label' => 'Approved',
                ],
                'rejected_listings' => [
                    'label' => 'Rejected',
                ],
                'regular_users' => [
                    'label' => 'Regular Users',
                ],
                'agency_users' => [
                    'label' => 'Agencies',
                ],
                'admins' => [
                    'label' => 'Admins',
                ],
                'pending_agencies' => [
                    'label' => 'Pending Agencies',
                    'description' => 'Awaiting verification',
                ],
                'reported_messages' => [
                    'label' => 'Reported Messages',
                    'description' => 'Unresolved reports',
                ],
            ],
        ],
    ],
];
