<?php

use App\Http\Controllers\CourseCertificateController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseInstructorController;
use App\Http\Controllers\CourseLearnController;
use App\Http\Controllers\CourseStudentController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\DiscussionPostController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupMemberController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('courses.index');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Course Routes
Route::middleware('auth')->prefix('courses')->name('courses.')->group(function () {
    // List all courses
    Route::get('/', [CourseController::class, 'index'])->name('index');

    // Show create form
    Route::get('/create', [CourseController::class, 'create'])->name('create');

    // Store new course
    Route::post('/', [CourseController::class, 'store'])->name('store');

    // Show a single course
    Route::get('/{course}', [CourseController::class, 'show'])->name('show');

    // Show edit form
    Route::get('/{course}/edit', [CourseController::class, 'edit'])->name('edit');

    // Update course
    Route::put('/{course}', [CourseController::class, 'update'])->name('update');

    // Soft delete course
    Route::delete('/{course}', [CourseController::class, 'destroy'])->name('destroy');

    // Restore soft deleted course
    Route::post('/{id}/restore', [CourseController::class, 'restore'])->name('restore');

    // Permanently delete course
    Route::delete('/{id}/force', [CourseController::class, 'forceDestroy'])->name('forceDestroy');

    // Search instructors who can still be assigned (typeahead)
    Route::get('/{course}/instructors/assignable', [CourseInstructorController::class, 'assignable'])->name('instructors.assignable');

    // Assign an instructor to a course
    Route::post('/{course}/instructors', [CourseInstructorController::class, 'store'])->name('instructors.store');

    // Remove an instructor from a course
    Route::delete('/{course}/instructors/{user}', [CourseInstructorController::class, 'destroy'])->name('instructors.destroy');

    // Take a course (player)
    Route::get('/{course}/learn', [CourseLearnController::class, 'show'])->name('learn');
    Route::get('/{course}/learn/{page}', [CourseLearnController::class, 'showPage'])
        ->scopeBindings()
        ->name('learn.page');
    Route::post('/{course}/learn/{page}/complete', [CourseLearnController::class, 'complete'])
        ->scopeBindings()
        ->name('learn.complete');

    // Search students who can still be enrolled (typeahead)
    Route::get('/{course}/students/assignable', [CourseStudentController::class, 'assignable'])->name('students.assignable');

    // Enroll a student in a course
    Route::post('/{course}/students', [CourseStudentController::class, 'store'])->name('students.store');

    // Search groups whose members can be bulk-enrolled (typeahead)
    Route::get('/{course}/students/assignable-groups', [CourseStudentController::class, 'assignableGroups'])->name('students.assignable-groups');

    // Bulk-enroll a group's members as students
    Route::post('/{course}/students/group', [CourseStudentController::class, 'storeGroup'])->name('students.storeGroup');

    // Remove a student from a course
    Route::delete('/{course}/students/{user}', [CourseStudentController::class, 'destroy'])->name('students.destroy');

    // Completion certificate
    Route::get('/{course}/certificate', [CourseCertificateController::class, 'show'])->name('certificate');

    // Course discussions
    Route::name('discussions.')->scopeBindings()->group(function () {
        // List a course's discussions
        Route::get('/{course}/discussions', [DiscussionController::class, 'index'])->name('index');

        // Show the form for starting a discussion
        Route::get('/{course}/discussions/create', [DiscussionController::class, 'create'])->name('create');

        // Store a new discussion
        Route::post('/{course}/discussions', [DiscussionController::class, 'store'])->name('store');

        // Show a single discussion thread
        Route::get('/{course}/discussions/{discussion}', [DiscussionController::class, 'show'])->name('show');

        // Show the form for editing a discussion
        Route::get('/{course}/discussions/{discussion}/edit', [DiscussionController::class, 'edit'])->name('edit');

        // Update a discussion
        Route::put('/{course}/discussions/{discussion}', [DiscussionController::class, 'update'])->name('update');

        // Open or close a discussion
        Route::patch('/{course}/discussions/{discussion}/status', [DiscussionController::class, 'setStatus'])->name('setStatus');

        // Delete a discussion
        Route::delete('/{course}/discussions/{discussion}', [DiscussionController::class, 'destroy'])->name('destroy');

        // Add a reply to a discussion
        Route::post('/{course}/discussions/{discussion}/posts', [DiscussionPostController::class, 'store'])->name('posts.store');

        // Update a reply
        Route::put('/{course}/discussions/{discussion}/posts/{post}', [DiscussionPostController::class, 'update'])->name('posts.update');

        // Delete a reply
        Route::delete('/{course}/discussions/{discussion}/posts/{post}', [DiscussionPostController::class, 'destroy'])->name('posts.destroy');
    });
});

// User Routes
Route::middleware('auth')->prefix('users')->name('users.')->group(function () {
    // List all users
    Route::get('/', [UserController::class, 'index'])->name('index');

    // Show create form
    Route::get('/create', [UserController::class, 'create'])->name('create');

    // Store new user
    Route::post('/', [UserController::class, 'store'])->name('store');

    // Show a single user
    Route::get('/{user}', [UserController::class, 'show'])->name('show');

    // Show edit form
    Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');

    // Update user
    Route::put('/{user}', [UserController::class, 'update'])->name('update');

    // Soft delete user
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');

    // Restore soft deleted user
    Route::post('/{id}/restore', [UserController::class, 'restore'])->name('restore');

    // Permanently delete user
    Route::delete('/{id}/force', [UserController::class, 'forceDestroy'])->name('forceDestroy');
});

// Group Routes
Route::middleware('auth')->prefix('groups')->name('groups.')->group(function () {
    // List all groups
    Route::get('/', [GroupController::class, 'index'])->name('index');

    // Show create form
    Route::get('/create', [GroupController::class, 'create'])->name('create');

    // Store new group
    Route::post('/', [GroupController::class, 'store'])->name('store');

    // Show a single group
    Route::get('/{group}', [GroupController::class, 'show'])->name('show');

    // Show edit form
    Route::get('/{group}/edit', [GroupController::class, 'edit'])->name('edit');

    // Update group
    Route::put('/{group}', [GroupController::class, 'update'])->name('update');

    // Soft delete group
    Route::delete('/{group}', [GroupController::class, 'destroy'])->name('destroy');

    // Restore soft deleted group
    Route::post('/{id}/restore', [GroupController::class, 'restore'])->name('restore');

    // Permanently delete group
    Route::delete('/{id}/force', [GroupController::class, 'forceDestroy'])->name('forceDestroy');

    // Search instructors and students who can still join the group (typeahead)
    Route::get('/{group}/members/assignable', [GroupMemberController::class, 'assignable'])->name('members.assignable');

    // Add a member to the group
    Route::post('/{group}/members', [GroupMemberController::class, 'store'])->name('members.store');

    // Update a member's leadership status
    Route::put('/{group}/members/{user}', [GroupMemberController::class, 'update'])->name('members.update');

    // Remove a member from the group
    Route::delete('/{group}/members/{user}', [GroupMemberController::class, 'destroy'])->name('members.destroy');
});

// Reorder the pages within a course
Route::put('courses/{course}/pages/reorder', [PageController::class, 'reorder'])
    ->middleware('auth')
    ->name('pages.reorder');

// Page Routes
Route::middleware('auth')->prefix('pages')->name('pages.')->group(function () {
    // Show create form
    Route::get('/create', [PageController::class, 'create'])->name('create');

    // Store new page
    Route::post('/', [PageController::class, 'store'])->name('store');

    // Show a single page
    Route::get('/{page}', [PageController::class, 'show'])->name('show');

    // Show edit form
    Route::get('/{page}/edit', [PageController::class, 'edit'])->name('edit');

    // Update page
    Route::put('/{page}', [PageController::class, 'update'])->name('update');

    // Soft delete page
    Route::delete('/{page}', [PageController::class, 'destroy'])->name('destroy');
});

require __DIR__.'/auth.php';
