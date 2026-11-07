<?php

namespace App\Queries;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Database\Eloquent\Builder;

class BookSearchQuery
{
    public const SORTS = [
        'relevance' => 'Pertinence',
        'title' => 'Titre (A-Z)',
        'title_desc' => 'Titre (Z-A)',
        'newest' => 'Publication la plus récente',
        'oldest' => 'Publication la plus ancienne',
        'added' => 'Ajoutés récemment',
        'pages_asc' => 'Moins de pages',
        'pages_desc' => 'Plus de pages',
    ];

    public static function build(array $filters): Builder
    {
        $query = Book::query()->with(['authors', 'genres']);
        $text = $filters['q'] ?? null;

        if ($text) {
            self::matchText($query, $text);
        }

        foreach (['genre', 'subgenre'] as $key) {
            if (! empty($filters[$key])) {
                self::inGenre($query, $filters[$key]);
            }
        }

        if (! empty($filters['author'])) {
            $query->whereHas('authors', fn (Builder $q) => $q->whereRaw(
                'f_unaccent(authors.name) ILIKE f_unaccent(?)',
                [self::contains($filters['author'])]
            ));
        }

        if (! empty($filters['publisher'])) {
            $query->whereRaw(
                'f_unaccent(books.publisher) ILIKE f_unaccent(?)',
                [self::contains($filters['publisher'])]
            );
        }

        if (! empty($filters['language'])) {
            $query->where('books.language', $filters['language']);
        }

        // Intervalles de dates : l'index sur publication_date reste utilisable
        if (! empty($filters['year_min'])) {
            $query->where('books.publication_date', '>=', $filters['year_min'].'-01-01');
        }
        if (! empty($filters['year_max'])) {
            $query->where('books.publication_date', '<=', $filters['year_max'].'-12-31');
        }

        if (! empty($filters['pages_min'])) {
            $query->where('books.page_count', '>=', $filters['pages_min']);
        }
        if (! empty($filters['pages_max'])) {
            $query->where('books.page_count', '<=', $filters['pages_max']);
        }

        self::applySort($query, $filters['sort'] ?? null, $text);

        return $query;
    }

    /**
     * Texte libre : ISBN exact, ou chaque mot doit correspondre
     * au titre, à un auteur ou à un genre.
     */
    private static function matchText(Builder $query, string $text): void
    {
        $compact = preg_replace('/[\s-]/', '', $text);

        if (preg_match('/^(\d{13}|\d{9}[\dXx])$/', $compact)) {
            $isbn = strtoupper($compact);
            $query->where(fn (Builder $q) => $q->where('books.isbn13', $isbn)->orWhere('books.isbn10', $isbn));

            return;
        }

        $terms = array_slice(preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY), 0, 5);

        foreach ($terms as $term) {
            $pattern = self::contains($term);

            // Le groupe (...) garantit que les OR ne se mélangent pas aux autres conditions
            $query->where(function (Builder $q) use ($pattern) {
                $q->whereRaw('f_unaccent(books.title) ILIKE f_unaccent(?)', [$pattern])
                    ->orWhereHas('authors', fn (Builder $a) => $a->whereRaw('f_unaccent(authors.name) ILIKE f_unaccent(?)', [$pattern]))
                    ->orWhereHas('genres', fn (Builder $g) => $g->whereRaw('f_unaccent(genres.name) ILIKE f_unaccent(?)', [$pattern]));
            });
        }
    }

    /** Un genre inclut ses sous-genres. */
    private static function inGenre(Builder $query, string $slug): void
    {
        $ids = Genre::query()
            ->where('slug', $slug)
            ->orWhereHas('parent', fn ($q) => $q->where('slug', $slug))
            ->pluck('id');

        // Slug inconnu : $ids est vide et la requête ne renvoie rien
        $query->whereHas('genres', fn ($q) => $q->whereIn('genres.id', $ids));
    }

    private static function applySort(Builder $query, ?string $sort, ?string $text): void
    {
        $sort ??= $text ? 'relevance' : 'title';

        if ($sort === 'relevance' && ! $text) {
            $sort = 'title';
        }

        match ($sort) {
            'title_desc' => $query->orderByDesc('books.title'),
            // PostgreSQL place les NULL en premier en ordre décroissant : NULLS LAST l'évite
            'newest' => $query->orderByRaw('books.publication_date DESC NULLS LAST')->orderBy('books.title'),
            'oldest' => $query->orderByRaw('books.publication_date ASC NULLS LAST')->orderBy('books.title'),
            'added' => $query->orderByDesc('books.created_at')->orderBy('books.title'),
            'pages_asc' => $query->orderByRaw('books.page_count ASC NULLS LAST')->orderBy('books.title'),
            'pages_desc' => $query->orderByRaw('books.page_count DESC NULLS LAST')->orderBy('books.title'),
            'relevance' => $query
                ->orderByRaw(
                    'CASE WHEN f_unaccent(books.title) ILIKE f_unaccent(?) THEN 0 ELSE 1 END',
                    [self::startsWith($text)]
                )
                ->orderBy('books.title'),
            default => $query->orderBy('books.title'),
        };

        // Départage les égalités : sans cela, la pagination peut répéter ou sauter des livres
        $query->orderBy('books.id');
    }

    /** Échappe % _ \ pour que la saisie soit prise littéralement. */
    private static function escape(string $term): string
    {
        return addcslashes($term, '%_\\');
    }

    private static function contains(string $term): string
    {
        return '%'.self::escape($term).'%';
    }

    private static function startsWith(string $term): string
    {
        return self::escape($term).'%';
    }
}
