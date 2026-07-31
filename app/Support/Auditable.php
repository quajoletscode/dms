<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Writes create/update/delete events to audit_logs. Do not combine with
 * AppendOnly — ledger models are audited implicitly by their own creation.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            AuditLogger::record('create', $model, null, $model->getAttributes());
        });

        static::updated(function (Model $model): void {
            $changes = $model->getChanges();
            unset($changes['updated_at']);

            if ($changes === []) {
                return;
            }

            $before = array_intersect_key($model->getOriginal(), $changes);

            AuditLogger::record('update', $model, $before, $changes);
        });

        static::deleted(function (Model $model): void {
            AuditLogger::record('delete', $model, $model->getOriginal(), null);
        });
    }
}
