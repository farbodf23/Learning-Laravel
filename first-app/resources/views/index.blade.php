<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">

    <div class="max-w-3xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">AMIN Shamsaldini</h1>

        <div class="space-y-4">
            @foreach ($tasks as $task)
                <div class="bg-white p-5 rounded-lg shadow">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-xl font-semibold">
                                {{ $task->title }}
                            </h2>
                            <p class="text-gray-600 mt-1">
                                {{ $task->description }}
                            </p>

                            @if ($task->long_description)
                                <p class="text-gray-500 mt-2 text-sm">
                                    {{ $task->long_description }}
                                </p>
                            @endif
                        </div>

                        <div>
                            @if ($task->completed)
                                <span class="bg-green-100 text-green-800 text-sm px-3 py-1 rounded-full">
                                    Completed
                                </span>
                            @else
                                <span class="bg-yellow-100 text-yellow-800 text-sm px-3 py-1 rounded-full">
                                    Pending
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 text-sm text-gray-400">
                        Created: {{ $task->created_at }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</body>
</html>