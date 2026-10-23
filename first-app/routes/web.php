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
    // Get the requested filter, or use the saved preference.
    $filter = request()->query(
        'filter',
        session('task_filter', 'all')
    );

    // Only allow these three filter values.
    if (! in_array($filter, ['all', 'pending', 'completed'], true)) {
        $filter = 'all';
    }

    // Remember the user's choice in the session.
    session(['task_filter' => $filter]);

    // Retrieve the appropriate tasks from PostgreSQL.
    $tasks = match ($filter) {
        'pending' => Task::where('completed', false)->get(),
        'completed' => Task::where('completed', true)->get(),
        default => Task::all(),
    };

    return view('tasks.index', [
        'tasks' => $tasks,
        'filter' => $filter,
    ]);
})->name('tasks.index');

Route::get('/tasks/{task}', function (Task $task) {
    return view('tasks.show', [
        'task' => $task,
    ]);
})->where('task', '[0-9]+')->name('tasks.show');

Route::patch('/tasks/{task}/toggle', function (Task $task) {
    $task->completed = ! $task->completed;
    $task->save();
    return redirect()->route('tasks.index');
})->name('tasks.toggle');

Route::get('/tasks/create', function () {
    return view('tasks.create');
})->name('tasks.create');

Route::post('/tasks', function () {
    $validated = request()->validate([
        'title' => 'required|max:255',
        'description' => 'required',
        'long_description' => 'nullable',
    ]);

    // Actually save to database
    $task = Task::create($validated);

    // Redirect after success
    return redirect()->route("tasks.index")->with("success","Task created successfully");
})->name('tasks.store');