<?php

return [

    /*
    |--------------------------------------------------------------------------
    | POS discount override threshold
    |--------------------------------------------------------------------------
    |
    | A POS sale line whose discount rate (discount / line gross, before
    | discount) exceeds this percentage requires the pos.discount_override
    | permission — an elevated, per-user escape hatch, not a role default
    | (same discipline as grn.override/sales.credit_override).
    |
    */

    'pos_discount_override_threshold_percent' => env('SALES_POS_DISCOUNT_OVERRIDE_THRESHOLD_PERCENT', 10),

];
