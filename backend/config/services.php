<?php

return [
    // Keep all provider and payment credentials in the hosting provider's environment settings.
    'paystack' => [
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        'minimum_deposit_kobo' => (int) env('PAYSTACK_MINIMUM_DEPOSIT_KOBO', 10000),
        'maximum_deposit_kobo' => (int) env('PAYSTACK_MAXIMUM_DEPOSIT_KOBO', 100000000),
    ],

    'smm_center' => [
        'api_key' => env('SMM_CENTER_API_KEY'),
        'base_url' => env('SMM_CENTER_BASE_URL', 'https://smm-center.com'),
    ],
];
