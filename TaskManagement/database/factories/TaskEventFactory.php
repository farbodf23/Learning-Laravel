<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\TaskEvent>
 */
class TaskEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'task_id' => Task::factory(),
            'actor_id' => User::factory(),
            'event_type' => 'task_created',
            'changes' => null,
        ];
    }
}