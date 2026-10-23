<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Task</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">

    <div class="max-w-2xl mx-auto py-10 px-4">

        <h1 class="text-3xl font-bold text-gray-800 mb-8">
            Create Task
        </h1>

        <form action="{{ route('tasks.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="title" class="block text-gray-700 font-medium mb-2">
                    Title
                </label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    class="w-full px-4 py-2 border rounded-lg"
                >
            </div>

            <div class="mb-4">
                <label for="description" class="block text-gray-700 font-medium mb-2">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    class="w-full px-4 py-2 border rounded-lg"
                ></textarea>
            </div>

            <div class="mb-4">
                <label for="long_description" class="block text-gray-700 font-medium mb-2">
                    Long Description
                </label>

                <textarea
                    id="long_description"
                    name="long_description"
                    rows="6"
                    class="w-full px-4 py-2 border rounded-lg"
                ></textarea>
            </div>

            <button
                type="submit"
                class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
                Create Task
            </button>

        </form>

    </div>

</body>
</html>