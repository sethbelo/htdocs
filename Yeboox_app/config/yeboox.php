<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    */
    'name' => 'Yeboox POS',

    /*
    |--------------------------------------------------------------------------
    | Version
    |--------------------------------------------------------------------------
    */
    'version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | Default Country & Currency
    |--------------------------------------------------------------------------
    */
    'default_country' => 'CD',
    'default_currency' => 'CDF',
    'default_timezone' => 'Africa/Kinshasa',
    'default_locale' => 'fr',

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    */
    'currencies' => [
        'CDF' => ['name' => 'Franc Congolais', 'symbol' => 'FC', 'decimals' => 2, 'position' => 'after'],
        'USD' => ['name' => 'Dollar Américain', 'symbol' => '$', 'decimals' => 2, 'position' => 'before'],
        'EUR' => ['name' => 'Euro', 'symbol' => '€', 'decimals' => 2, 'position' => 'after'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Number Formatting (RDC Style)
    |--------------------------------------------------------------------------
    */
    'number_format' => [
        'decimal_separator' => ',',
        'thousands_separator' => '.',
    ],

    /*
    |--------------------------------------------------------------------------
    | POS Configuration
    |--------------------------------------------------------------------------
    */
    'pos' => [
        'allow_negative_stock' => false,
        'require_cash_session' => true,
        'require_customer' => false,
        'auto_print_receipt' => true,
        'receipt_printer' => 'thermal', // thermal, a4
        'default_order_type' => 'dine_in',
        'max_discount_percentage' => 100,
        'seller_max_discount' => 10, // Max discount % for sellers
        'manager_required_for_refund' => true,
        'manager_required_for_void' => true,
        'lock_timeout_minutes' => 5,
    ],

    'kiosk' => [
        'establishment_id' => env('YEBOOX_KIOSK_ESTABLISHMENT_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Kitchen Display System
    |--------------------------------------------------------------------------
    */
    'kitchen' => [
        'alert_after_minutes' => 15,
        'critical_after_minutes' => 30,
        'auto_accept' => false,
        'sound_enabled' => true,
        'auto_print' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Loyalty Program
    |--------------------------------------------------------------------------
    */
    'loyalty' => [
        'enabled' => true,
        'points_per_currency_unit' => 1, // 1 point per 1000 CDF
        'currency_unit_amount' => 1000,
        'point_value' => 10, // 1 point = 10 CDF
        'points_expiry_months' => 12,
        'levels' => [
            'standard' => ['min_points' => 0, 'label' => 'Standard', 'discount' => 0],
            'bronze' => ['min_points' => 500, 'label' => 'Bronze', 'discount' => 2],
            'silver' => ['min_points' => 2000, 'label' => 'Argent', 'discount' => 5],
            'gold' => ['min_points' => 5000, 'label' => 'Or', 'discount' => 8],
            'platinum' => ['min_points' => 10000, 'label' => 'Platine', 'discount' => 12],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice Configuration
    |--------------------------------------------------------------------------
    */
    'invoice' => [
        'prefix' => 'YBX',
        'numbering' => 'sequential', // sequential, yearly_reset
        'default_payment_terms' => 0, // Days
        'footer_text' => 'Merci pour votre visite !',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Configuration (Desktop <-> Cloud)
    |--------------------------------------------------------------------------
    */
    'sync' => [
        'enabled' => true,
        'interval_seconds' => 30,
        'batch_size' => 100,
        'retry_attempts' => 3,
        'conflict_resolution' => 'server_wins', // server_wins, client_wins, manual
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'max_login_attempts' => 5,
        'lockout_minutes' => 15,
        'pin_length' => 4,
        'require_strong_password' => true,
        'session_lifetime_minutes' => 480, // 8 hours for POS
    ],

    /*
    |--------------------------------------------------------------------------
    | OHADA Accounting
    |--------------------------------------------------------------------------
    */
    'accounting' => [
        'chart_of_accounts' => 'ohada',
        'fiscal_year_start_month' => 1,
        'auto_generate_entries' => true,
        'require_period_closing' => true,
    ],

];
