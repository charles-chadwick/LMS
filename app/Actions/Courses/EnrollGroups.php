<?php

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\Group;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EnrollGroups
{
    public function __construct(private EnrollGroupMembers $enrollGroupMembers) {}

    /**
     * Bulk-enroll each group's current student members into the course.
     *
     * @param  Collection<int, Group>  $groups
     * @return int The total number of members enrolled across all groups.
     */
    public function execute(Course $course, Collection $groups): int
    {
        return DB::transaction(fn (): int => $groups->sum(
            fn (Group $group): int => $this->enrollGroupMembers->execute($course, $group)
        ));
    }
}
