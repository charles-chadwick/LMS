<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseCertificateController extends Controller
{
    /**
     * Show the completion certificate for a finished course.
     */
    public function show(Request $request, Course $course): Response
    {
        $this->authorize('viewCertificate', $course);

        $user = $request->user();
        $student = $course->students()->whereKey($user->id)->first();

        return Inertia::render('Courses/Certificate', [
            'course' => $course->only('id', 'title', 'code'),
            'student' => $user->only('id', 'first_name', 'last_name'),
            'completed_at' => $student->pivot->completed_at,
        ]);
    }
}
