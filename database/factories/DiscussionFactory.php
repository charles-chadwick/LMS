<?php

namespace Database\Factories;

use App\Enums\DiscussionStatus;
use App\Enums\DiscussionType;
use App\Models\Course;
use App\Models\Discussion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discussion>
 */
class DiscussionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'on' => Course::factory(),
            'on_type' => Course::class,
            'type' => DiscussionType::General,
            'title' => fake()->sentence(5),
            'status' => DiscussionStatus::Open,
            'created_by_id' => 1,
        ];
    }

    /**
     * Attach the discussion to a specific course.
     */
    public function forCourse(Course $course): static
    {
        return $this->state(fn (array $attributes) => [
            'on' => $course->id,
            'on_type' => Course::class,
        ]);
    }

    /**
     * Indicate that the discussion is an announcement.
     */
    public function announcement(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DiscussionType::Announcement,
        ]);
    }

    /**
     * Indicate that the discussion is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DiscussionStatus::Closed,
        ]);
    }
}
