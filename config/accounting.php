<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Canonical Chart of Accounts codes
    |--------------------------------------------------------------------------
    |
    | PostingRules resolve accounts by code through ChartOfAccountResolver
    | rather than hardcoding IDs or scattering magic strings. A fuller
    | standard CoA template lands in Phase 6 (FIN-01); this is the minimal
    | set the posting rules built so far actually need.
    |
    */

    'accounts' => [
        'inventory' => env('ACCOUNTING_INVENTORY_ACCOUNT_CODE', '1000'),
        'cash_on_hand' => env('ACCOUNTING_CASH_ACCOUNT_CODE', '1010'),
        'accounts_receivable' => env('ACCOUNTING_AR_ACCOUNT_CODE', '1100'),
        'accounts_payable' => env('ACCOUNTING_AP_ACCOUNT_CODE', '2000'),
        'sales_revenue' => env('ACCOUNTING_SALES_REVENUE_ACCOUNT_CODE', '4000'),
        'cogs' => env('ACCOUNTING_COGS_ACCOUNT_CODE', '5000'),
        'inventory_loss' => env('ACCOUNTING_INVENTORY_LOSS_ACCOUNT_CODE', '5100'),
        'bank_charges' => env('ACCOUNTING_BANK_CHARGES_ACCOUNT_CODE', '5200'),
        'cash_over_short' => env('ACCOUNTING_CASH_OVER_SHORT_ACCOUNT_CODE', '5900'),
        'cash_in_dsr_hand' => env('ACCOUNTING_CASH_IN_DSR_HAND_ACCOUNT_CODE', '1020'),
    ],

];
