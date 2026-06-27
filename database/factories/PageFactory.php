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
            'content' => '<p>'.fake()->paragraphs(3, true).'</p>',
            'created_by_id' => 1,
        ];
    }

    /**
     * Attach the page to a specific course at a given order position.
     */
    public function forCourse(Course $course, int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'course_id' => $course->id,
            'order' => $order,
        ]);
    }
}
