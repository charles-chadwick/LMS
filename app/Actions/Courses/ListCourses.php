<?php

namespace App\Actions\Courses;

use App\Enums\UserRole;
use App\Models\Course;
use App\Traits\HasSearchFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $user = $request->user();
        $is_admin = $user->hasRole(UserRole::Admin);
        $taught_course_ids = $is_admin
            ? collect()
            : DB::table('courses_users')
                ->where('user_id', $user->id)
                ->where('is_instructor', true)
                ->pluck('course_id');

        return $query->paginate($request->input('perPage', 15))
            ->withQueryString()
            ->through(function (Course $course) use ($is_admin, $taught_course_ids) {
                $course->can_update = $is_admin || $taught_course_ids->contains($course->id);

                return $course;
            });
    }
}
