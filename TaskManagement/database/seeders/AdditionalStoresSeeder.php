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

class AdditionalStoresSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            for ($i = 1; $i <= 3; $i++) {

                // 1. Create a store
                $store = Store::factory()->create([
                    'name' => "Additional Store {$i}",
                ]);

                // 2. Create its manager
                $manager = User::factory()->create();

                StoreMembership::create([
                    'store_id' => $store->id,
                    'user_id' => $manager->id,
                    'role' => 'manager',
                ]);

                // 3. Create 3 staff members
                $staff = collect();

                for ($j = 1; $j <= 3; $j++) {

                    $user = User::factory()->create();

                    StoreMembership::create([
                        'store_id' => $store->id,
                        'user_id' => $user->id,
                        'role' => 'staff',
                    ]);

                    $staff->push($user);
                }

                // 4. Create 15 tasks
                for ($j = 1; $j <= 15; $j++) {

                    $assignee = $staff->random();

                    $task = Task::factory()->create([
                        'store_id' => $store->id,
                        'created_by' => $manager->id,
                        'assigned_to' => $assignee->id,
                    ]);

                    // 5. Create a comment
                    TaskComment::factory()->create([
                        'store_id' => $store->id,
                        'task_id' => $task->id,
                        'user_id' => $assignee->id,
                    ]);

                    // 6. Create a task event
                    TaskEvent::factory()->create([
                        'store_id' => $store->id,
                        'task_id' => $task->id,
                        'actor_id' => $manager->id,
                    ]);
                }
            }
        });
    }
}
