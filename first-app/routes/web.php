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
    return redirect('/tasks/' . $task->id);
})->name('tasks.store');