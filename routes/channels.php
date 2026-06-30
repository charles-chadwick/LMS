<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Course discussion activity is broadcast to participants of that course.
 */
Broadcast::channel('courses.{course}.discussions', function (User $user, Course $course) {
    return $course->hasParticipant($user);
});
