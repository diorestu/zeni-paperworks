<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Package Catalog Defaults
    |--------------------------------------------------------------------------
    |
    | These values are used as fallbacks when no super-admin overrides exist
    | in the package_configs table.
    |
    */
    'packages' => [
        'Free' => [
            'monthly_price' => 0,
            'yearly_price' => 0,
            'invoice_limit' => 10,
        ],
        'Basic' => [
            'monthly_price' => 49000,
            'yearly_price' => 38000,
            'invoice_limit' => 10,
        ],
        'Pro' => [
            'monthly_price' => 139000,
            'yearly_price' => 106000,
            'invoice_limit' => 500,
        ],
        'Enterprise' => [
            'monthly_price' => 199000,
            'yearly_price' => 151000,
            'invoice_limit' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monthly Invoice Limits Per Plan
    |--------------------------------------------------------------------------
    |
    | Null value means unlimited invoice creation.
    |
    */
    'invoice_limits' => [
        'Free' => 10,
        'Basic' => 10,
        'Pro' => 500,
        'Enterprise' => null,
    ],
];
