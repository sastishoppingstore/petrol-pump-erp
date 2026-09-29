<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Login throttling
    |--------------------------------------------------------------------------
    | "N failed attempts -> lock the account for M minutes" (spec section 1).
    | Attempts are recorded in the append-only `login_attempts` table so the
    | history is auditable and survives a cache flush.
    */

    'login' => [
        'max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
        'lock_minutes' => (int) env('LOGIN_LOCK_MINUTES', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Business thresholds
    |--------------------------------------------------------------------------
    | Section 3 of the spec. These are read through SettingService from Phase
    | 12 onwards so they can be edited in the UI; the env values are the
    | bootstrap defaults.
    */

    'shift_variance_threshold' => env('SHIFT_VARIANCE_THRESHOLD', 100),
    'credit_overdue_days' => (int) env('CREDIT_OVERDUE_DAYS', 30),
    'meter_variance_tolerance' => env('METER_VARIANCE_TOLERANCE', 0.5),

    /*
    |--------------------------------------------------------------------------
    | Number sequences
    |--------------------------------------------------------------------------
    | Document number formats (spec section 4, step 7).
    */

    'sequences' => [
        'invoice' => ['prefix' => 'INV', 'digits' => 6],
        'shift' => ['prefix' => 'SHIFT', 'digits' => 6],
        'purchase' => ['prefix' => 'PUR', 'digits' => 6],
        'payment' => ['prefix' => 'PAY', 'digits' => 6],
        'adjustment' => ['prefix' => 'ADJ', 'digits' => 6],
    ],

];
