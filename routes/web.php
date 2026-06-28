<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseInstructorController;
use App\Http\Controllers\PageController;
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

    // Assign an instructor to a course
    Route::post('/{course}/instructors', [CourseInstructorController::class, 'store'])->name('instructors.store');

    // Remove an instructor from a course
    Route::delete('/{course}/instructors/{user}', [CourseInstructorController::class, 'destroy'])->name('instructors.destroy');
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
