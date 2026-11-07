<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthorResource;
use App\Http\Resources\BookResource;
use App\Http\Resources\BookSummaryResource;
use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['per_page' => ['sometimes', 'integer', 'between:1,50']]);

        $books = Book::query()
            ->with(['authors', 'genres'])
            ->orderBy('title')
            ->paginate($request->integer('per_page', 20));

        return BookSummaryResource::collection($books);
    }

    public function show(Book $book)
    {
        $book->load(['authors', 'genres']);

        $genreIds = $book->genres->pluck('id');

        // Livres similaires : ceux qui partagent le plus de genres avec celui-ci
        $similar = Book::query()
            ->whereKeyNot($book->id)
            ->whereHas('genres', fn ($q) => $q->whereIn('genres.id', $genreIds))
            ->withCount(['genres as shared_genres' => fn ($q) => $q->whereIn('genres.id', $genreIds)])
            ->with(['authors', 'genres'])
            ->orderByDesc('shared_genres')
            ->orderByDesc('avg_rating')
            ->orderBy('title')
            ->limit(6)
            ->get();

        $book->setRelation('similar', $similar);

        return new BookResource($book);
    }

    public function authors(Book $book)
    {
        return AuthorResource::collection($book->authors()->orderBy('name')->get());
    }
}
