<?php

use App\Http\Controllers\Api\AuthorController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\GenreController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:120,1')->group(function () {

    // Vérifie que l'API et la base de données répondent
    Route::get('/health', function () {
        DB::select('select 1');

        return response()->json([
            'status' => 'ok',
            'database' => DB::connection()->getDatabaseName(),
        ]);
    });

    // Utilisateur connecté (protégée par token Sanctum)
    Route::get('/user', fn (Request $request) => $request->user())
        ->middleware('auth:sanctum');

    // Catalogue (public)
    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/search', [BookController::class, 'search']);
    Route::get('/books/filters', [BookController::class, 'filters']);
    Route::get('/books/{book}', [BookController::class, 'show'])->whereNumber('book');
    Route::get('/books/{book}/authors', [BookController::class, 'authors'])->whereNumber('book');

    Route::get('/authors', [AuthorController::class, 'index']);
    Route::get('/authors/{author}', [AuthorController::class, 'show'])->whereNumber('author');

    Route::get('/genres', [GenreController::class, 'index']);
    Route::get('/genres/{genre}', [GenreController::class, 'show']);
    Route::get('/genres/{genre}/books', [GenreController::class, 'books']);
});
