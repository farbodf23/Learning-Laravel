<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\StoreMembership;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => 'todo',
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'created_by' => User::factory(),
            'assigned_to' => null,
            'due_at' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'completed_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Task $task) {
            // Ensure the creator belongs to the task's store.
            StoreMembership::firstOrCreate(
                [
                    'store_id' => $task->store_id,
                    'user_id' => $task->created_by,
                ],
                [
                    'role' => 'manager',
                ]
            );

            // If assigned, ensure the assignee also belongs to this store.
            if ($task->assigned_to !== null) {
                StoreMembership::firstOrCreate(
                    [
                        'store_id' => $task->store_id,
                        'user_id' => $task->assigned_to,
                    ],
                    [
                        'role' => 'staff',
                    ]
                );
            }
        });
    }
}
