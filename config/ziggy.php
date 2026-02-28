<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Route Groups
    |--------------------------------------------------------------------------
    |
    | Limit Ziggy route exposure per page context. This only reduces metadata
    | visible in page source; authorization must still be enforced in middleware.
    |
    */
    'groups' => [
        'shared' => [
            'landing',
            'privacy-policy',
            'terms-service',
        ],

        'guest' => [
            'login',
            'register',
            'auth.google.*',
        ],

        'auth-common' => [
            'dashboard',
            'logout',
            'verification.*',
            'feedback.store',
            'notifications.*',
            'onboarding.*',
            'profile.*',
            'settings.billing*',
            'settings.reset-password*',
            'settings.index',
        ],

        'auth-documents' => [
            'invoices.*',
            'quotations.*',
        ],

        'auth-user' => [
            // Used in add-client/add-product actions inside invoice/quotation forms.
            'clients.store',
            'products.store',
        ],

        'auth-admin' => [
            'clients.*',
            'products.*',
            'settings.update',
            'settings.sub-users.*',
            'settings.taxes.*',
        ],

        'super-admin' => [
            'super-admin.*',
        ],
    ],
];

