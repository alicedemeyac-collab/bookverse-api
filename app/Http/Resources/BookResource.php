<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $primary = $this->genres->first(fn ($genre) => $genre->pivot->is_primary);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'synopsis' => $this->synopsis,
            'isbn10' => $this->isbn10,
            'isbn13' => $this->isbn13,
            'publication_date' => $this->publication_date?->toDateString(),
            'publisher' => $this->publisher,
            'page_count' => $this->page_count,
            'language' => $this->language,
            'cover_url' => $this->cover_url,
            'avg_rating' => $this->ratings_count > 0 ? (float) $this->avg_rating : null,
            'ratings_count' => $this->ratings_count,
            'authors' => AuthorResource::collection($this->whenLoaded('authors')),
            // Le genre principal avec son thème complet : la fiche s'habille à ses couleurs
            'primary_genre' => $primary ? new GenreResource($primary) : null,
            'genres' => $this->genres->map(fn ($genre) => [
                'id' => $genre->id,
                'name' => $genre->name,
                'slug' => $genre->slug,
                'parent_id' => $genre->parent_id,
                'is_primary' => (bool) $genre->pivot->is_primary,
            ])->values(),
            'similar' => BookSummaryResource::collection($this->whenLoaded('similar')),
        ];
    }
}
