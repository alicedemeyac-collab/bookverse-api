<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Book extends Model
{
    protected $fillable = [
        'title', 'subtitle', 'synopsis', 'isbn10', 'isbn13',
        'publication_date', 'publisher', 'page_count', 'language',
        'cover_url', 'google_books_id', 'open_library_id',
        'avg_rating', 'ratings_count',
    ];

    protected function casts(): array
    {
        return [
            'publication_date' => 'date',
            'page_count' => 'integer',
            'avg_rating' => 'decimal:2',
            'ratings_count' => 'integer',
        ];
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'book_author');
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'book_genre')->withPivot('is_primary');
    }

    public function primaryGenre(): BelongsToMany
    {
        return $this->genres()->wherePivot('is_primary', true);
    }
}
