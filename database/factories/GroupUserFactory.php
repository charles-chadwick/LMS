<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\GroupUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class GroupUserFactory extends Factory
{
    protected $model = GroupUser::class;

    public function definition()
    {
        return [
            'is_leader' => $this->faker->boolean(),

            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'group_id' => Group::factory(),
            'user_id' => User::factory(),
        ];
    }
}
