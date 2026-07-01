<?php

namespace Database\Factories;

use App\Enums\CourseStatus;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => fake()->randomElement(CourseStatus::cases()),
            'title' => fake()->unique()->sentence(3),
            'code' => strtoupper(fake()->unique()->bothify('???-###')),
            'description' => '<p>'.fake()->paragraph().'</p>',
            'created_by_id' => 1,
        ];
    }

    /**
     * Indicate that the course is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CourseStatus::Published,
        ]);
    }

    /**
     * Attach a generated cover image to the course.
     */
    public function withCover(): static
    {
        return $this->afterCreating(function (Course $course) {
            $course->addMedia(UploadedFile::fake()->image('cover.jpg', 800, 450))
                ->toMediaCollection('cover');
        });
    }
}
