<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'bio' => $this->bio,
            'photo_url' => $this->photo_url,
            'birth_date' => $this->birth_date?->toDateString(),
            'nationality' => $this->nationality,
            'books_count' => $this->whenCounted('books'),
            'books' => BookSummaryResource::collection($this->whenLoaded('books')),
            'genres' => GenreResource::collection($this->whenLoaded('genres')),
        ];
    }
}
