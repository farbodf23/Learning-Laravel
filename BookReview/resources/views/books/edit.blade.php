
<h1>Edit Book</h1>

<form method="POST" action="{{ route('books.update', $book) }}">
    @csrf
    @method('PUT')

    <div>
        <label for="title">Title</label>

        <input
            type="text"
            id="title"
            name="title"
            value="{{ old('title', $book->title) }}"
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
            value="{{ old('author', $book->author) }}"
        >

        @error('author')
            <p>{{ $message }}</p>
        @enderror
    </div>

    <button type="submit">Update Book</button>
</form>

<a href="{{ route('books.show', $book) }}">Cancel</a>
