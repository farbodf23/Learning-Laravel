<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreMembership;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskEvent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // -------------------------------------------------
            // 1. Create users
            // -------------------------------------------------

            $users = [];

            $userData = [
                'alice' => ['Alice Manager', 'alice@example.com'],
                'bob' => ['Bob Staff', 'bob@example.com'],
                'dana' => ['Dana Staff', 'dana@example.com'],
                'ethan' => ['Ethan Staff', 'ethan@example.com'],
                'fiona' => ['Fiona Staff', 'fiona@example.com'],
                'charlie' => ['Charlie Manager', 'charlie@example.com'],
                'grace' => ['Grace Staff', 'grace@example.com'],
                'henry' => ['Henry Staff', 'henry@example.com'],
            ];

            foreach ($userData as $key => [$name, $email]) {
                $users[$key] = User::factory()->create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make('password123'),
                ]);
            }

            // -------------------------------------------------
            // 2. Create stores
            // -------------------------------------------------

            $storeA = Store::create([
                'name' => 'Store A',
            ]);

            $storeB = Store::create([
                'name' => 'Store B',
            ]);

            // -------------------------------------------------
            // 3. Create memberships
            // -------------------------------------------------

            $membershipsA = [
                'alice' => 'manager',
                'bob' => 'staff',
                'dana' => 'staff',
                'ethan' => 'staff',
                'fiona' => 'staff',
            ];

            $membershipsB = [
                'charlie' => 'manager',
                'alice' => 'staff',
                'grace' => 'staff',
                'henry' => 'staff',
                'bob' => 'staff',
            ];

            foreach ($membershipsA as $userKey => $role) {
                StoreMembership::create([
                    'store_id' => $storeA->id,
                    'user_id' => $users[$userKey]->id,
                    'role' => $role,
                ]);
            }

            foreach ($membershipsB as $userKey => $role) {
                StoreMembership::create([
                    'store_id' => $storeB->id,
                    'user_id' => $users[$userKey]->id,
                    'role' => $role,
                ]);
            }

            // -------------------------------------------------
            // 4. Create tasks
            // -------------------------------------------------

            $tasks = [];

            $storeConfigurations = [
                [
                    'store' => $storeA,
                    'manager' => $users['alice'],
                    'staff' => [
                        $users['bob'],
                        $users['dana'],
                        $users['ethan'],
                        $users['fiona'],
                    ],
                ],
                [
                    'store' => $storeB,
                    'manager' => $users['charlie'],
                    'staff' => [
                        $users['alice'],
                        $users['grace'],
                        $users['henry'],
                        $users['bob'],
                    ],
                ],
            ];

            foreach ($storeConfigurations as $configuration) {
                $store = $configuration['store'];
                $manager = $configuration['manager'];
                $staff = $configuration['staff'];

                for ($i = 1; $i <= 20; $i++) {
                    $status = fake()->randomElement([
                        'todo',
                        'in_progress',
                        'done',
                        'cancelled',
                    ]);

                    // Ensure completed_at matches the status constraint.
                    $completedAt = $status === 'done'
                        ? now()->subDays(fake()->numberBetween(1, 10))
                        : null;

                    // Some tasks are unassigned.
                    $assignee = fake()->boolean(85)
                        ? fake()->randomElement($staff)
                        : null;

                    // Include overdue tasks intentionally.
                    $dueAt = fake()->boolean(20)
                        ? now()->subDays(fake()->numberBetween(1, 10))
                        : now()->addDays(fake()->numberBetween(1, 30));

                    $task = Task::factory()->create([
                        'store_id' => $store->id,
                        'created_by' => $manager->id,
                        'assigned_to' => $assignee?->id,
                        'status' => $status,
                        'completed_at' => $completedAt,
                        'due_at' => $dueAt,
                    ]);

                    $tasks[] = [
                        'task' => $task,
                        'store' => $store,
                        'manager' => $manager,
                        'staff' => $staff,
                    ];
                }
            }

            // -------------------------------------------------
            // 5. Create comments and events
            // -------------------------------------------------

            foreach ($tasks as $taskData) {
                $task = $taskData['task'];
                $store = $taskData['store'];
                $manager = $taskData['manager'];
                $staff = $taskData['staff'];

                // 1 to 3 comments per task.
                $commentCount = fake()->numberBetween(1, 3);

                for ($i = 0; $i < $commentCount; $i++) {
                    $commentAuthor = fake()->randomElement(
                        array_merge([$manager], $staff)
                    );

                    TaskComment::factory()->create([
                        'store_id' => $store->id,
                        'task_id' => $task->id,
                        'user_id' => $commentAuthor->id,
                        'body' => fake()->paragraph(),
                    ]);
                }

                // Creation event.
                TaskEvent::factory()->create([
                    'store_id' => $store->id,
                    'task_id' => $task->id,
                    'actor_id' => $manager->id,
                    'event_type' => 'task_created',
                    'changes' => [
                        'title' => $task->title,
                        'assigned_to' => $task->assigned_to,
                    ],
                ]);

                // Status event for non-todo tasks.
                if ($task->status !== 'todo') {
                    TaskEvent::factory()->create([
                        'store_id' => $store->id,
                        'task_id' => $task->id,
                        'actor_id' => $manager->id,
                        'event_type' => 'status_changed',
                        'changes' => [
                            'status' => [
                                'old' => 'todo',
                                'new' => $task->status,
                            ],
                        ],
                    ]);
                }
            }
        });
    }
}
