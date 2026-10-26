<h1>Books</h1>

<form method="GET" action="{{ route('books.index') }}">
    <input
        type="text"
        name="search"
        placeholder="Search by title..."
        value="{{ request('search') }}"
    >

    <button type="submit">Search</button>
</form>

<div>
    <a href="{{ route('books.index') }}">All</a>
    <a href="{{ route('books.index', ['filter' => 'popular']) }}">Popular</a>
    <a href="{{ route('books.index', ['filter' => 'highest-rated']) }}">
        Highest Rated
    </a>
</div>

@forelse ($books as $book)

    <article>
        <h2>
            <a href="{{ route('books.show', $book) }}">
                {{ $book->title }}
            </a>
        </h2>

        <p>Author: {{ $book->author }}</p>

        @if (isset($book->reviews_avg_rating))
            <p>Rating: {{ number_format($book->reviews_avg_rating, 1) }}</p>
        @endif

        @if (isset($book->reviews_count))
            <p>Reviews: {{ $book->reviews_count }}</p>
        @endif
    </article>

@empty
    <p>No books found.</p>
@endforelse

{{ $books->links() }}
