<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Author extends Model
{
    protected $fillable = [
        'name', 'bio', 'photo_url', 'birth_date', 'nationality', 'open_library_id',
    ];

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'book_author');
    }
}
