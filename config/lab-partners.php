<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lab Partner Webhook Secret
    |--------------------------------------------------------------------------
    |
    | Secret key for verifying webhook signatures from lab partners.
    | This should be a long, random string that is shared with lab partners.
    |
    */

    'webhook_secret' => env('LAB_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Default Lab Partner
    |--------------------------------------------------------------------------
    |
    | The default lab partner to use when none is specified.
    | Must match a 'code' in the lab_partners table.
    |
    */

    'default_partner' => env('DEFAULT_LAB_PARTNER', 'quest'),

    /*
    |--------------------------------------------------------------------------
    | Lab Partner Timeouts
    |--------------------------------------------------------------------------
    |
    | API timeout settings for lab partner integrations (in seconds).
    | Connection timeout is for establishing the connection.
    | API timeout is for the entire request/response cycle.
    |
    */

    'connection_timeout' => env('LAB_CONNECTION_TIMEOUT', 10),
    'api_timeout' => env('LAB_API_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Number of times to retry failed lab submissions and delay between retries.
    | retry_delay is in seconds.
    |
    */

    'max_retries' => env('LAB_MAX_RETRIES', 3),
    'retry_delay' => env('LAB_RETRY_DELAY', 300), // 5 minutes

    /*
    |--------------------------------------------------------------------------
    | HL7 Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for HL7 v2.x message processing.
    |
    */

    'hl7' => [
        'listener_port' => env('HL7_PORT', 2575),
        'listener_host' => env('HL7_HOST', '0.0.0.0'),
        'version' => '2.5.1',
        'mllp_start_byte' => "\x0B", // Vertical Tab
        'mllp_end_bytes' => "\x1C\x0D", // File Separator + Carriage Return
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Ordering Physician
    |--------------------------------------------------------------------------
    |
    | Default physician information for lab orders.
    | Some labs require ordering physician details.
    |
    */

    'default_physician' => [
        'name' => env('LAB_DEFAULT_PHYSICIAN_NAME', 'Dr. Medical Director'),
        'npi' => env('LAB_DEFAULT_PHYSICIAN_NPI'),
        'license' => env('LAB_DEFAULT_PHYSICIAN_LICENSE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | LabCorp Specific Configuration
    |--------------------------------------------------------------------------
    */

    'labcorp' => [
        'account_number' => env('LABCORP_ACCOUNT_NUMBER'),
        'provider_npi' => env('LABCORP_PROVIDER_NPI'),
        'provider_first_name' => env('LABCORP_PROVIDER_FIRST_NAME', 'Medical'),
        'provider_last_name' => env('LABCORP_PROVIDER_LAST_NAME', 'Director'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Quest Diagnostics Specific Configuration
    |--------------------------------------------------------------------------
    */

    'quest' => [
        'account_number' => env('QUEST_ACCOUNT_NUMBER'),
        'client_id' => env('QUEST_CLIENT_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Catalog Caching
    |--------------------------------------------------------------------------
    |
    | Cache duration for lab test catalogs (in seconds).
    | Set to 0 to disable caching.
    |
    */

    'test_catalog_cache_ttl' => env('LAB_TEST_CATALOG_CACHE', 3600), // 1 hour

    /*
    |--------------------------------------------------------------------------
    | Result Processing
    |--------------------------------------------------------------------------
    */

    'results' => [
        // Automatically generate PDF when result received
        'auto_generate_pdf' => env('LAB_AUTO_GENERATE_PDF', true),

        // Delay before sending patient notification (in minutes)
        'notification_delay' => env('LAB_NOTIFICATION_DELAY', 5),

        // Require review for all results (not just critical)
        'require_review_all' => env('LAB_REQUIRE_REVIEW_ALL', false),

        // Critical value flags that require review
        'critical_flags' => ['HH', 'LL', 'CRIT', 'CRITICAL'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Alerts
    |--------------------------------------------------------------------------
    */

    'monitoring' => [
        // Email address for lab integration alerts
        'alert_email' => env('LAB_ALERT_EMAIL', env('MAIL_FROM_ADDRESS')),

        // Alert when submission fails more than X times
        'alert_after_failures' => 3,

        // Alert when result is delayed by X hours
        'alert_delayed_result_hours' => 72,
    ],

];