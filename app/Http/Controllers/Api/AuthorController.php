<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthorResource;
use App\Models\Author;
use App\Models\Genre;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['per_page' => ['sometimes', 'integer', 'between:1,50']]);

        $authors = Author::query()
            ->withCount('books')
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return AuthorResource::collection($authors);
    }

    public function show(Author $author)
    {
        $author->load([
            'books' => fn ($q) => $q->orderBy('title')->with(['authors', 'genres']),
        ])->loadCount('books');

        // Genres littéraires associés : ceux des livres de l'auteur
        $genres = Genre::query()
            ->whereHas('books', fn ($q) => $q->whereHas('authors', fn ($a) => $a->whereKey($author->id)))
            ->orderBy('name')
            ->get();

        $author->setRelation('genres', $genres);

        return new AuthorResource($author);
    }
}
