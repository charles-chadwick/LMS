<?php

namespace Database\Factories;

use App\Enums\GroupType;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(GroupType::cases()),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'created_by_id' => 1,
        ];
    }

    /**
     * Indicate that the group is for instructors.
     */
    public function instructors(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => GroupType::Instructor,
        ]);
    }

    /**
     * Indicate that the group is for students.
     */
    public function students(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => GroupType::Student,
        ]);
    }
}
