<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Page;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed each course with a handful of sequentially ordered pages.
     */
    public function run(): void
    {
        $courses = Course::all();

        if ($courses->isEmpty()) {
            $courses = Course::factory()->count(25)->create();
        }

        foreach ($courses as $course) {
            $page_count = fake()->numberBetween(3, 8);

            for ($order = 1; $order <= $page_count; $order++) {
                Page::factory()->forCourse($course, $order)->create();
            }
        }
    }
}
