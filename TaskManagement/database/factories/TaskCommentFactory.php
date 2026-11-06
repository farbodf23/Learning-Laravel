<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\TaskComment>
 */
class TaskCommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
        ];
    }
}