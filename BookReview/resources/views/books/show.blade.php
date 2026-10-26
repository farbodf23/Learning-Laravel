
<h1>{{ $book->title }}</h1>

<p>Author: {{ $book->author }}</p>

@if ($book->reviews->count())
    <h2>Reviews</h2>

    @foreach ($book->reviews as $review)
        <article>
            <h3>{{ $review->title }}</h3>

            <p>By: {{ $review->author }}</p>

            <p>Rating: {{ $review->rating }}/5</p>

            <p>{{ $review->review }}</p>
        </article>
    @endforeach
@else
    <p>This book has no reviews yet.</p>
@endif

<a href="{{ route('books.index') }}">Back to books</a>

<a href="{{ route('books.edit', $book) }}">Edit</a>

<form
    method="POST"
    action="{{ route('books.destroy', $book) }}"
>
    @csrf
    @method('DELETE')

    <button type="submit">Delete</button>
</form>
