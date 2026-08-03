<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Finds the previous/next record id relative to $model under the same
 * ordering a list page uses, so a detail page can offer prev/next
 * navigation without the caller re-deriving tie-breaking logic per column.
 */
final class AdjacentRecordResolver
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query  Unordered, unfiltered base query (any global scopes still apply).
     * @param  TModel  $model
     * @return array{prev: int|null, next: int|null}
     */
    public function resolve(Builder $query, Model $model, string $column): array
    {
        $value = $model->getAttribute($column);

        $prev = (clone $query)
            ->where(function (Builder $q) use ($column, $value, $model) {
                $q->where($column, '<', $value)
                    ->orWhere(function (Builder $q2) use ($column, $value, $model) {
                        $q2->where($column, $value)->where('id', '<', $model->getKey());
                    });
            })
            ->orderByDesc($column)
            ->orderByDesc('id')
            ->value('id');

        $next = (clone $query)
            ->where(function (Builder $q) use ($column, $value, $model) {
                $q->where($column, '>', $value)
                    ->orWhere(function (Builder $q2) use ($column, $value, $model) {
                        $q2->where($column, $value)->where('id', '>', $model->getKey());
                    });
            })
            ->orderBy($column)
            ->orderBy('id')
            ->value('id');

        return ['prev' => $prev, 'next' => $next];
    }
}
