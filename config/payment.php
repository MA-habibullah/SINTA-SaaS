<?php

return [
    'default' => env('PAYMENT_GATEWAY_DRIVER', 'midtrans'),

    'drivers' => [
        'midtrans' => [
            'merchant_id'    => env('MIDTRANS_MERCHANT_ID', ''),
            'server_key'     => env('MIDTRANS_SERVER_KEY', ''),
            'client_key'     => env('MIDTRANS_CLIENT_KEY', ''),
            'is_production'  => env('MIDTRANS_IS_PRODUCTION', false),
            'is_sanitized'   => true,
            'is_3ds'         => true,
            'snap_url'       => env('MIDTRANS_IS_PRODUCTION', false)
                ? 'https://app.midtrans.com/snap/v1/transactions'
                : 'https://app.sandbox.midtrans.com/snap/v1/transactions',
        ],

        'tripay' => [
            'api_key'       => env('TRIPAY_API_KEY', ''),
            'private_key'   => env('TRIPAY_PRIVATE_KEY', ''),
            'merchant_code' => env('TRIPAY_MERCHANT_CODE', ''),
            'is_production' => env('TRIPAY_IS_PRODUCTION', false),
            'api_url'       => env('TRIPAY_IS_PRODUCTION', false)
                ? 'https://tripay.co.id/api/'
                : 'https://tripay.co.id/api-sandbox/',
        ],
    ],
];
