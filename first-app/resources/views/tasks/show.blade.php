<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $task->title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

    <div class="max-w-3xl mx-auto py-10 px-4">
        <a href="{{ url('/tasks') }}" class="text-blue-600 hover:underline mb-6 inline-block">
            ← Back to all tasks
        </a>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <div class="flex justify-between items-start mb-6">
                <h1 class="text-3xl font-bold text-gray-800">
                    {{ $task->title }}
                </h1>

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

            <div class="space-y-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Description</h3>
                    <p class="mt-1 text-gray-800">{{ $task->description }}</p>
                </div>

                @if ($task->long_description)
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Long Description</h3>
                        <p class="mt-1 text-gray-800 whitespace-pre-line">{{ $task->long_description }}</p>
                    </div>
                @endif

                <div class="pt-4 border-t border-gray-100 grid grid-cols-2 gap-4 text-sm text-gray-500">
                    <div>
                        <span class="font-medium">Created:</span>
                        {{ $task->created_at }}
                    </div>
                    <div>
                        <span class="font-medium">Updated:</span>
                        {{ $task->updated_at }}
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>