<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CourseController;

// Course Routes
Route::prefix('courses')->name('courses.')->group(function () {
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
});
