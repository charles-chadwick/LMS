<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Traits\HasSearchFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ListCourses
{
    use HasSearchFilter;

    /**
     * Build the filtered, paginated course listing for the index page.
     */
    public function execute(Request $request): LengthAwarePaginator
    {
        $query = Course::query()
            ->select([
                'id',
                'status',
                'title',
                'code',
            ])
            ->withCount([
                'pages',
                'students',
                'instructors',
            ]);

        $query = $this->applyCommonFilters($query, $request, [
            'title',
            'code',
        ]);

        return $query->paginate($request->input('perPage', 15))
            ->withQueryString();
    }
}
