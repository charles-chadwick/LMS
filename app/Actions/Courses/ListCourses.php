<?php

namespace App\Actions\Courses;

use App\Enums\CourseStatus;
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

        $enrolled_student_course_ids = DB::table('courses_users')
            ->where('user_id', $user->id)
            ->where('is_instructor', false)
            ->whereNull('deleted_at')
            ->pluck('course_id');

        $published_page_counts = DB::table('pages')
            ->whereIn('course_id', $enrolled_student_course_ids)
            ->where('status', CourseStatus::Published->value)
            ->whereNull('deleted_at')
            ->groupBy('course_id')
            ->selectRaw('course_id, count(*) as total')
            ->pluck('total', 'course_id');

        $completed_page_counts = DB::table('user_progress')
            ->join('pages', 'pages.id', '=', 'user_progress.page_id')
            ->where('user_progress.user_id', $user->id)
            ->whereIn('user_progress.course_id', $enrolled_student_course_ids)
            ->where('pages.status', CourseStatus::Published->value)
            ->whereNull('user_progress.deleted_at')
            ->whereNull('pages.deleted_at')
            ->groupBy('user_progress.course_id')
            ->selectRaw('user_progress.course_id as course_id, count(*) as completed')
            ->pluck('completed', 'course_id');

        return $query->paginate($request->input('perPage', 15))
            ->withQueryString()
            ->through(function (Course $course) use (
                $is_admin,
                $taught_course_ids,
                $enrolled_student_course_ids,
                $published_page_counts,
                $completed_page_counts,
            ) {
                $course->can_update = $is_admin || $taught_course_ids->contains($course->id);

                $is_enrolled_student = $enrolled_student_course_ids->contains($course->id);
                $course->can_take = $is_enrolled_student && $course->status === CourseStatus::Published;

                $total = (int) ($published_page_counts[$course->id] ?? 0);
                $completed = (int) ($completed_page_counts[$course->id] ?? 0);
                $course->progress_percent = $total > 0 ? min(100, (int) round($completed / $total * 100)) : 0;

                return $course;
            });
    }
}
