<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Tasks</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

    <div class="max-w-4xl mx-auto py-10 px-4">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Task List</h1>
        </div>

        <div class="space-y-4">
           
@forelse ($tasks as $task)
    <div class="bg-white p-5 rounded-xl shadow-sm hover:shadow-md transition border border-gray-200">

        <div class="flex justify-between items-start gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    <a href="{{ route('tasks.show', $task) }}"
                       class="hover:text-blue-600">
                        {{ $task->title }}
                    </a>
                </h2>

                <p class="text-gray-600 mt-1">
                    {{ $task->description }}
                </p>
            </div>

            <div>
                @if ($task->completed)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        Completed
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                        Pending
                    </span>
                @endif
            </div>
        </div>

        <div class="mt-4">
            <form action="{{ route('tasks.toggle', $task) }}" method="POST">
                @csrf
                @method('PATCH')

                @if ($task->completed)
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-yellow-500 text-white hover:bg-yellow-600">
                        Mark as Pending
                    </button>
                @else
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700">
                        Mark as Completed
                    </button>
                @endif
            </form>
        </div>

    </div>
@empty
    <div class="bg-white p-8 rounded-xl text-center text-gray-500">
        No tasks found.
    </div>
@endforelse

        </div>
    </div>

</body>
</html>