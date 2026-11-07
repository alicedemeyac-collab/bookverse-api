<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookSummaryResource;
use App\Http\Resources\GenreResource;
use App\Models\Genre;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    public function index()
    {
        $genres = Genre::query()
            ->whereNull('parent_id')
            ->withCount('books')
            ->with(['children' => fn ($q) => $q->withCount('books')->orderBy('name')])
            ->orderBy('name')
            ->get();

        return GenreResource::collection($genres);
    }

    public function show(Genre $genre)
    {
        $genre->load([
            'parent',
            'children' => fn ($q) => $q->withCount('books')->orderBy('name'),
        ])->loadCount('books');

        return new GenreResource($genre);
    }

    public function books(Request $request, Genre $genre)
    {
        $request->validate(['per_page' => ['sometimes', 'integer', 'between:1,50']]);

        $books = $genre->books()
            ->with(['authors', 'genres'])
            ->orderBy('title')
            ->paginate($request->integer('per_page', 20));

        return BookSummaryResource::collection($books);
    }
}
