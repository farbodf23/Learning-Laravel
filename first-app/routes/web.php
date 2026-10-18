<?php

use App\Models\Task;
use Illuminate\Support\Facades\Route;

Route::get('/', function (
) {

    return 'Howdy';
})->name('home');

Route::get('/about', function () {
    return redirect()->route('home');
});

Route::get('/tasks', function () {
    $tasks = Task::all();

    return view('tasks.index', [
        'tasks' => $tasks,
    ]);
})->name('tasks.index');

Route::get('/tasks/{task}', function (Task $task) {
    return view('tasks.show', [
        'task' => $task,
    ]);
})->name('tasks.show');

Route::patch('/tasks/{task}/toggle', function (Task $task) {
    $task->completed = ! $task->completed;
    $task->save();
    return redirect()->route('tasks.index');
})->name('tasks.toggle');
