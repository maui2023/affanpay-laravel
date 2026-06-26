<?php

return [
    'environment' => env('AFFANPAY_ENVIRONMENT', 'sandbox'),
    'webhook_secret' => env('AFFANPAY_WEBHOOK_SECRET'),
    'sandbox' => [
        'url' => env('AFFANPAY_SANDBOX_URL', 'https://sandbox.affanpay.my'),
    ],
    'live' => [
        'url' => env('AFFANPAY_LIVE_URL', 'https://app.affanpay.my'),
    ],
];
