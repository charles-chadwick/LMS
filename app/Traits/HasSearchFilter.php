<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HasSearchFilter
{
    /**
     * Apply search filter to the query
     *
     * @param  Builder  $query
     * @param  Request  $request
     * @param  array<string>  $searchable_fields
     * @param  string  $search_param
     * @return Builder
     */
    protected function applySearchFilter(
        Builder $query,
        Request $request,
        array $searchable_fields,
        string $search_param = 'search'
    ) : Builder {
        if (!$request->has($search_param) || !$request->get($search_param)) {
            return $query;
        }

        $search_term = $request->get($search_param);

        return $query->where(function ($q) use ($searchable_fields, $search_term) {
            foreach ($searchable_fields as $index => $field) {
                $method = $index === 0 ? 'where' : 'orWhere';

                // Handle relation searches (e.g., 'user.name')
                if (str_contains($field, '.')) {
                    [
                        $relation,
                        $relation_field
                    ] = explode('.', $field, 2);
                    $q->orWhereHas($relation, function ($relation_query) use ($relation_field, $search_term) {
                        $relation_query->where($relation_field, 'like', "%{$search_term}%");
                    });
                } else {
                    $q->$method($field, 'like', "%{$search_term}%");
                }
            }
        });
    }

    /**
     * Apply status filter to the query
     *
     * @param  Builder  $query
     * @param  Request  $request
     * @param  string  $field_name
     * @param  string  $param_name
     * @return Builder
     */
    protected function applyStatusFilter(
        Builder $query,
        Request $request,
        string $field_name = 'status',
        string $param_name = 'status'
    ) : Builder {
        if (!$request->has($param_name) || !$request->get($param_name)) {
            return $query;
        }

        return $query->where($field_name, $request->get($param_name));
    }

    /**
     * Apply sorting to the query
     *
     * @param  Builder  $query
     * @param  Request  $request
     * @param  string  $default_sort_by
     * @param  string  $default_sort_direction
     * @return Builder
     */
    protected function applySorting(
        Builder $query,
        Request $request,
        string $default_sort_by = 'created_at',
        string $default_sort_direction = 'desc'
    ) : Builder {
        $sort_by = $request->get('sortBy', $default_sort_by);
        $sort_direction = $request->get('sortDirection', $default_sort_direction);

        return $query->orderBy($sort_by, $sort_direction);
    }

    /**
     * Apply all common filters (search, status, sorting)
     *
     * @param  Builder  $query
     * @param  Request  $request
     * @param  array<string>  $searchable_fields
     * @param  array<string, mixed>  $options
     * @return Builder
     */
    protected function applyCommonFilters(
        Builder $query,
        Request $request,
        array $searchable_fields,
        array $options = []
    ) : Builder {
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
