<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Manual journal approval threshold
    |--------------------------------------------------------------------------
    |
    | A manual journal whose total debit exceeds this amount (major units,
    | GHS) is held as pending_approval instead of posting immediately —
    | requiring a second user (ApproveManualJournal) before it reaches
    | journal_entries. Same escalation discipline as the other
    | override/threshold permissions in this app.
    |
    */

    'manual_journal_approval_threshold' => env('FINANCE_MANUAL_JOURNAL_APPROVAL_THRESHOLD', '5000.00'),

];
