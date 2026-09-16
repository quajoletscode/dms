<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Builds the `meta`/`request` prop pair every List page's DataTable.vue expects
 * (pagination cursor + echoed search/sort state) and validates the `sort`
 * column against a caller-supplied allowlist, so this identical handful of
 * lines isn't repeated across every index() controller method.
 */
final class ListPageProps
{
    /**
     * @param  array<string, mixed>  $extraRequestParams  Extra filter params (e.g. a status
     *                                                    chip) a controller needs echoed back into `request` so
     *                                                    they survive a page-size change or a sort click made via
     *                                                    the shared pagination/sort composables, which rebuild
     *                                                    their next request from this prop rather than the URL.
     * @return array{meta: array<string, mixed>, request: array<string, mixed>}
     */
    public function build(LengthAwarePaginator $paginator, Request $request, array $extraRequestParams = []): array
    {
        return [
            'meta' => [
                'currentPage' => $paginator->currentPage(),
                'nextPageUrl' => $paginator->nextPageUrl(),
                'prevPageUrl' => $paginator->previousPageUrl(),
                'perPage' => $paginator->perPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'request' => [
                'q' => $request->string('q')->toString() ?: null,
                'per_page' => $paginator->perPage(),
                'sort' => $request->string('sort')->toString() ?: null,
                'direction' => $request->string('direction')->toString() ?: null,
                ...$extraRequestParams,
            ],
        ];
    }

    /**
     * @param  array<int, string>  $allowedColumns
     */
    public function resolveSort(Request $request, array $allowedColumns, string $default): string
    {
        $sort = $request->string('sort')->toString();

        return in_array($sort, $allowedColumns, true) ? $sort : $default;
    }

    /**
     * $default only applies when the request has no `direction` at all, so a
     * controller whose un-sorted list is naturally newest-first (`latest()`)
     * can keep that as its default without a user-chosen sort being silently
     * forced back to ascending.
     */
    public function resolveDirection(Request $request, string $default = 'asc'): string
    {
        if (! $request->filled('direction')) {
            return $default;
        }

        return $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';
    }

    public function resolvePerPage(Request $request, int $default = 15, int $max = 100): int
    {
        return min($max, max(1, $request->integer('per_page', $default)));
    }
}
