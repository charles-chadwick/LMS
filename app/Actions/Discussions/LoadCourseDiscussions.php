<?php

namespace App\Actions\Discussions;

use App\Models\Course;
use App\Models\Discussion;
use Illuminate\Database\Eloquent\Collection;

class LoadCourseDiscussions
{
    /**
     * Load a course's discussions with author, post count, and latest activity.
     *
     * @return Collection<int, Discussion>
     */
    public function execute(Course $course): Collection
    {
        return $course->discussions()
            ->with('created_by:id,first_name,last_name')
            ->withCount('posts')
            ->withMax('posts', 'created_at')
            ->latest('posts_max_created_at')
            ->get();
    }
}
