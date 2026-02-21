<?php

return [
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
