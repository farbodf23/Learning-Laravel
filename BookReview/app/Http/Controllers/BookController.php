<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    /**
     * Display a listing of the books.
     */
    public function index(Request $request)
    {
        $books = Book::query()
            ->when($request->search, function ($query, $search) {
                $query->search($search);
            })
            ->when($request->filter === 'popular', function ($query) {
                $query->popular();
            })
            ->when($request->filter === 'highest-rated', function ($query) {
                $query->highestRated();
            })
            ->paginate(10);

        return view('books.index', compact('books'));
    }

    /**
     * Show the specified book.
     */
    public function show(Book $book)
    {
        return view('books.show', compact('book'));
    }

    /**
     * Show the form for creating a new book.
     */
    public function create()
    {
        return view('books.create');
    }

    /**
     * Store a newly created book.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
        ]);

        $book = Book::create($validated);

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'Book created successfully.');
    }

    /**
     * Show the form for editing the specified book.
     */
    public function edit(Book $book)
    {
        return view('books.edit', compact('book'));
    }

    /**
     * Update the specified book.
     */
    public function update(Request $request, Book $book)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
        ]);

        $book->update($validated);

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'Book updated successfully.');
    }

    /**
     * Remove the specified book.
     */
    public function destroy(Book $book)
    {
        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', 'Book deleted successfully.');
    }
}
