<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $primary = $this->genres->first(fn ($genre) => $genre->pivot->is_primary);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'cover_url' => $this->cover_url,
            'publication_date' => $this->publication_date?->toDateString(),
            'page_count' => $this->page_count,
            'language' => $this->language,
            // Pas d'avis = pas de note (null), plutôt qu'un faux 0/5
            'avg_rating' => $this->ratings_count > 0 ? (float) $this->avg_rating : null,
            'ratings_count' => $this->ratings_count,
            'authors' => $this->authors->map(fn ($author) => [
                'id' => $author->id,
                'name' => $author->name,
            ])->values(),
            'primary_genre' => $primary ? [
                'id' => $primary->id,
                'name' => $primary->name,
                'slug' => $primary->slug,
                'primary_color' => $primary->primary_color,
            ] : null,
        ];
    }
}
