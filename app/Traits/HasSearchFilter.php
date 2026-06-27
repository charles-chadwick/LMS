<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HasSearchFilter
{
    /**
     * Apply search filter to the query
     *
     * @param  array<string>  $searchable_fields
     */
    protected function applySearchFilter(
        Builder $query,
        Request $request,
        array $searchable_fields,
        string $search_param = 'search'
    ): Builder {
        if (! $request->filled($search_param)) {
            return $query;
        }

        $search_term = $request->input($search_param);

        return $query->where(function (Builder $search_query) use ($searchable_fields, $search_term) {
            foreach ($searchable_fields as $field) {
                // Handle relation searches (e.g., 'user.name')
                if (str_contains($field, '.')) {
                    [$relation, $relation_field] = explode('.', $field, 2);
                    $search_query->orWhereHas($relation, function (Builder $relation_query) use ($relation_field, $search_term) {
                        $relation_query->where($relation_field, 'like', "%{$search_term}%");
                    });
                } else {
                    $search_query->orWhere($field, 'like', "%{$search_term}%");
                }
            }
        });
    }

    /**
     * Apply status filter to the query
     */
    protected function applyStatusFilter(
        Builder $query,
        Request $request,
        string $field_name = 'status',
        string $param_name = 'status'
    ): Builder {
        if (! $request->filled($param_name)) {
            return $query;
        }

        return $query->where($field_name, $request->input($param_name));
    }

    /**
     * Apply sorting to the query
     */
    protected function applySorting(
        Builder $query,
        Request $request,
        string $default_sort_by = 'created_at',
        string $default_sort_direction = 'desc'
    ): Builder {
        $sort_by = $request->input('sortBy', $default_sort_by);
        $sort_direction = $request->input('sortDirection', $default_sort_direction);

        return $query->orderBy($sort_by, $sort_direction);
    }

    /**
     * Apply all common filters (search, status, sorting)
     *
     * @param  array<string>  $searchable_fields
     * @param  array<string, mixed>  $options
     */
    protected function applyCommonFilters(
        Builder $query,
        Request $request,
        array $searchable_fields,
        array $options = []
    ): Builder {
        $query = $this->applySearchFilter(
            $query,
            $request,
            $searchable_fields,
            $options['search_param'] ?? 'search'
        );

        if ($options['apply_status_filter'] ?? true) {
            $query = $this->applyStatusFilter(
                $query,
                $request,
                $options['status_field'] ?? 'status',
                $options['status_param'] ?? 'status'
            );
        }

        if ($options['apply_sorting'] ?? true) {
            $query = $this->applySorting(
                $query,
                $request,
                $options['default_sort_by'] ?? 'created_at',
                $options['default_sort_direction'] ?? 'desc'
            );
        }

        return $query;
    }
}
