<?php

namespace App\Services\GoogleBooks;

final readonly class BookData
{
    /**
     * @param  list<string>  $authors
     * @param  list<string>  $categories
     */
    public function __construct(
        public string $googleBooksId,
        public string $title,
        public ?string $subtitle = null,
        public array $authors = [],
        public ?string $synopsis = null,
        public ?string $isbn10 = null,
        public ?string $isbn13 = null,
        public ?string $publicationDate = null,
        public ?string $publisher = null,
        public ?int $pageCount = null,
        public ?string $language = null,
        public ?string $coverUrl = null,
        public array $categories = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(...$data);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /** Colonnes de la table books (les auteurs et genres passent par les tables de liaison). */
    public function toBookAttributes(): array
    {
        return [
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'synopsis' => $this->synopsis,
            'isbn10' => $this->isbn10,
            'isbn13' => $this->isbn13,
            'publication_date' => $this->publicationDate,
            'publisher' => $this->publisher,
            'page_count' => $this->pageCount,
            'language' => $this->language,
            'cover_url' => $this->coverUrl,
            'google_books_id' => $this->googleBooksId,
        ];
    }
}
