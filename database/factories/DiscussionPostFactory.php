<?php

namespace Database\Factories;

use App\Enums\DiscussionPostStatus;
use App\Models\Discussion;
use App\Models\DiscussionPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscussionPost>
 */
class DiscussionPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'discussion_id' => Discussion::factory(),
            'status' => DiscussionPostStatus::Published,
            'content' => '<p>'.fake()->paragraph().'</p>',
            'created_by_id' => 1,
        ];
    }
}
