<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\Task;
use App\Models\TaskEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskEvent>
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
