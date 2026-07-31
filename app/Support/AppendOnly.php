<?php

namespace App\Support;

use App\Support\Exceptions\ImmutableRecordException;

/**
 * Blocks Eloquent-level updates/deletes on models that must stay append-only
 * (stock_ledger, journal_lines). Mirrored by DB triggers as a second line of
 * defence — see the 2026_07_31_060508 migration.
 */
trait AppendOnly
{
    public static function bootAppendOnly(): void
    {
        static::updating(function (): void {
            throw new ImmutableRecordException(static::class.' records are append-only and cannot be updated.');
        });

        static::deleting(function (): void {
            throw new ImmutableRecordException(static::class.' records are append-only and cannot be deleted.');
        });
    }
}
