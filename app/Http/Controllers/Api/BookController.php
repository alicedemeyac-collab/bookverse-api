<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookSearchRequest;
use App\Http\Resources\AuthorResource;
use App\Http\Resources\BookResource;
use App\Http\Resources\BookSummaryResource;
use App\Models\Book;
use App\Queries\BookSearchQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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

    public function search(BookSearchRequest $request)
    {
        $books = BookSearchQuery::build($request->validated())
            ->paginate($request->integer('per_page', 12))
            ->withQueryString(); // les liens de pagination conservent les filtres

        return BookSummaryResource::collection($books);
    }

    // Valeurs proposées par les filtres (langues, éditeurs, bornes...)
    public function filters()
    {
        $data = Cache::remember('books.filters', 600, function () {
            $stats = Book::query()
                ->selectRaw('MIN(EXTRACT(YEAR FROM publication_date))::int AS min_year')
                ->selectRaw('MAX(EXTRACT(YEAR FROM publication_date))::int AS max_year')
                ->selectRaw('MAX(page_count) AS max_pages')
                ->first();

            return [
                'languages' => Book::whereNotNull('language')->distinct()->orderBy('language')->pluck('language')->all(),
                'publishers' => Book::whereNotNull('publisher')
                    ->selectRaw('publisher, COUNT(*) AS total')
                    ->groupBy('publisher')
                    ->orderByDesc('total')
                    ->orderBy('publisher')
                    ->limit(100)
                    ->pluck('publisher')
                    ->all(),
                'years' => ['min' => $stats->min_year, 'max' => $stats->max_year],
                'pages' => ['max' => $stats->max_pages],
                'sorts' => collect(BookSearchQuery::SORTS)
                    ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                    ->values()
                    ->all(),
            ];
        });

        return response()->json(['data' => $data]);
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
