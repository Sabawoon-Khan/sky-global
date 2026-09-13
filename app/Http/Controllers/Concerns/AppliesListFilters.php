<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait AppliesListFilters
{
    /**
     * @param  list<string>  $allowedStatuses
     * @param  list<string>  $extraKeys
     * @return array<string, mixed>
     */
    protected function listFilters(Request $request, array $allowedStatuses = [], array $extraKeys = []): array
    {
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));
        $category = trim((string) $request->input('category', ''));

        $filters = [
            'search' => $search !== '' ? $search : null,
            'status' => $status !== '' && ($allowedStatuses === [] || in_array($status, $allowedStatuses, true))
                ? $status
                : null,
            'category' => $category !== '' ? $category : null,
            'project_id' => $request->integer('project_id') ?: null,
            'organization_id' => $request->integer('organization_id') ?: null,
            'date_from' => $request->filled('date_from') ? $request->date('date_from')?->toDateString() : null,
            'date_to' => $request->filled('date_to') ? $request->date('date_to')?->toDateString() : null,
        ];

        foreach ($extraKeys as $key) {
            if (array_key_exists($key, $filters)) {
                continue;
            }

            if (str_ends_with($key, '_id')) {
                $filters[$key] = $request->integer($key) ?: null;

                continue;
            }

            $value = trim((string) $request->input($key, ''));
            $filters[$key] = $value !== '' ? $value : null;
        }

        return $filters;
    }

    /**
     * @param  Builder<*>  $query
     * @param  array<string, mixed>  $filters
     * @param  array{
     *     date_column?: string,
     *     search_columns?: list<string>,
     *     search_relations?: array<string, list<string>>,
     *     category_column?: string,
     *     pending_null?: bool
     * }  $options
     */
    protected function applyListFilters(Builder $query, array $filters, array $options = []): void
    {
        $dateColumn = $options['date_column'] ?? 'transaction_date';
        $searchColumns = $options['search_columns'] ?? ['description'];
        $searchRelations = $options['search_relations'] ?? [];
        $categoryColumn = $options['category_column'] ?? 'category';
        $pendingNull = (bool) ($options['pending_null'] ?? false);

        $query
            ->when($filters['project_id'] ?? null, fn (Builder $q, int $projectId) => $q->where('project_id', $projectId))
            ->when($filters['organization_id'] ?? null, fn (Builder $q, int $organizationId) => $q->where('organization_id', $organizationId))
            ->when($filters['category'] ?? null, fn (Builder $q, string $category) => $q->where($categoryColumn, $category))
            ->when($filters['date_from'] ?? null, fn (Builder $q, string $from) => $q->whereDate($dateColumn, '>=', $from))
            ->when($filters['date_to'] ?? null, fn (Builder $q, string $to) => $q->whereDate($dateColumn, '<=', $to))
            ->when($filters['status'] ?? null, function (Builder $q, string $status) use ($pendingNull) {
                if ($pendingNull && $status === 'pending') {
                    $q->where(function (Builder $inner) {
                        $inner->whereNull('status')->orWhere('status', 'pending');
                    });

                    return;
                }

                $q->where('status', $status);
            })
            ->when($filters['search'] ?? null, function (Builder $q, string $search) use ($searchColumns, $searchRelations) {
                $q->where(function (Builder $inner) use ($search, $searchColumns, $searchRelations) {
                    foreach ($searchColumns as $index => $column) {
                        if ($index === 0) {
                            $inner->where($column, 'like', "%{$search}%");

                            continue;
                        }

                        $inner->orWhere($column, 'like', "%{$search}%");
                    }

                    foreach ($searchRelations as $relation => $columns) {
                        $inner->orWhereHas($relation, function (Builder $related) use ($search, $columns) {
                            $related->where(function (Builder $rel) use ($search, $columns) {
                                foreach ($columns as $index => $column) {
                                    if ($index === 0) {
                                        $rel->where($column, 'like', "%{$search}%");

                                        continue;
                                    }

                                    $rel->orWhere($column, 'like', "%{$search}%");
                                }
                            });
                        });
                    }
                });
            });
    }
}
