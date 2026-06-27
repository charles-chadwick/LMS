<?php

namespace Database\Factories;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'order' => fake()->numberBetween(1, 20),
            'status' => fake()->randomElement(CourseStatus::cases()),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(3, true),
            'created_by_id' => 1,
        ];
    }
}
