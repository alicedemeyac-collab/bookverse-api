<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GenreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'parent_id' => $this->parent_id,
            'theme' => [
                'primary_color' => $this->primary_color,
                'secondary_color' => $this->secondary_color,
                'accent_color' => $this->accent_color,
                'heading_font' => $this->heading_font,
                'body_font' => $this->body_font,
                'banner_url' => $this->banner_url,
                'mood' => $this->mood,
            ],
            'books_count' => $this->whenCounted('books'),
            'parent' => $this->whenLoaded('parent', fn () => $this->parent ? new GenreResource($this->parent) : null),
            'children' => GenreResource::collection($this->whenLoaded('children')),
        ];
    }
}
