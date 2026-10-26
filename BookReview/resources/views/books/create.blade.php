
<h1>Create a Book</h1>

<form method="POST" action="{{ route('books.store') }}">
    @csrf

    <div>
        <label for="title">Title</label>

        <input
            type="text"
            id="title"
            name="title"
            value="{{ old('title') }}"
        >

        @error('title')
            <p>{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="author">Author</label>

        <input
            type="text"
            id="author"
            name="author"
            value="{{ old('author') }}"
        >

        @error('author')
            <p>{{ $message }}</p>
        @enderror
    </div>

    <button type="submit">Create Book</button>
</form>

<a href="{{ route('books.index') }}">Cancel</a>
