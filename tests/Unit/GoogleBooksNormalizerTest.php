<?php

namespace Tests\Unit;

use App\Services\GoogleBooks\GoogleBooksNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GoogleBooksNormalizerTest extends TestCase
{
    private function volume(array $info = [], string $id = 'abc123XYZ'): array
    {
        return ['id' => $id, 'volumeInfo' => array_merge(['title' => 'Dune'], $info)];
    }

    private function normalize(array $volume)
    {
        return (new GoogleBooksNormalizer())->normalize($volume);
    }

    public function test_complete_volume_is_normalized(): void
    {
        $book = $this->normalize($this->volume([
            'subtitle' => 'Roman',
            'authors' => ['Frank Herbert'],
            'publisher' => 'Robert Laffont',
            'publishedDate' => '1965-08-01',
            'description' => '<p>Un <b>désert</b>.</p><p>Deux.</p>',
            'industryIdentifiers' => [
                ['type' => 'ISBN_10', 'identifier' => '0306406152'],
                ['type' => 'ISBN_13', 'identifier' => '9780306406157'],
            ],
            'pageCount' => 688,
            'categories' => ['Fiction / Science Fiction / General'],
            'imageLinks' => ['thumbnail' => 'http://books.google.com/books/content?id=abc&zoom=1&edge=curl'],
            'language' => 'fr',
        ]));

        $this->assertSame('abc123XYZ', $book->googleBooksId);
        $this->assertSame('Dune', $book->title);
        $this->assertSame(['Frank Herbert'], $book->authors);
        $this->assertSame('1965-08-01', $book->publicationDate);
        $this->assertSame("Un désert.\nDeux.", $book->synopsis);
        $this->assertSame('0306406152', $book->isbn10);
        $this->assertSame('9780306406157', $book->isbn13);
        $this->assertSame(688, $book->pageCount);
        $this->assertSame('fr', $book->language);
        $this->assertSame('https://books.google.com/books/content?id=abc&zoom=1', $book->coverUrl);
    }

    public function test_missing_fields_become_null_without_error(): void
    {
        $book = $this->normalize($this->volume());

        $this->assertNull($book->subtitle);
        $this->assertSame([], $book->authors);
        $this->assertNull($book->synopsis);
        $this->assertNull($book->isbn10);
        $this->assertNull($book->isbn13);
        $this->assertNull($book->publicationDate);
        $this->assertNull($book->pageCount);
        $this->assertNull($book->language);
        $this->assertNull($book->coverUrl);
        $this->assertSame([], $book->categories);
    }

    public function test_unusable_volumes_are_skipped(): void
    {
        $this->assertNull($this->normalize($this->volume(['title' => '   '])));
        $this->assertNull($this->normalize(['id' => 'abc']));
        $this->assertNull($this->normalize($this->volume([], 'a/b')));
    }

    #[DataProvider('dates')]
    public function test_dates_are_normalized(string $input, ?string $expected): void
    {
        $this->assertSame($expected, $this->normalize($this->volume(['publishedDate' => $input]))->publicationDate);
    }

    public static function dates(): array
    {
        return [
            'année seule' => ['2015', '2015-01-01'],
            'année et mois' => ['2015-06', '2015-06-01'],
            'date complète' => ['2015-06-15', '2015-06-15'],
            'date inconnue' => ['0000', null],
            'date impossible' => ['2015-13-40', null],
            'texte' => ['juin 2015', null],
            'année lointaine' => ['9999', null],
        ];
    }

    public function test_invalid_isbn_is_dropped_and_valid_one_is_cleaned(): void
    {
        $book = $this->normalize($this->volume(['industryIdentifiers' => [
            ['type' => 'ISBN_13', 'identifier' => '9780306406158'], // clé de contrôle fausse
            ['type' => 'ISBN_10', 'identifier' => '0-306-40615-2'], // valide, avec tirets
            ['type' => 'OTHER', 'identifier' => 'PKEY:123'],
        ]]));

        $this->assertNull($book->isbn13);
        $this->assertSame('0306406152', $book->isbn10);
    }

    public function test_zero_pages_and_bad_language_are_rejected(): void
    {
        $this->assertNull($this->normalize($this->volume(['pageCount' => 0]))->pageCount);
        $this->assertNull($this->normalize($this->volume(['language' => 'und']))->language);
        $this->assertSame('zh', $this->normalize($this->volume(['language' => 'zh-CN']))->language);
        $this->assertSame('fr', $this->normalize($this->volume(['language' => 'FR']))->language);
    }

    public function test_cover_prefers_larger_image_and_rejects_foreign_hosts(): void
    {
        $book = $this->normalize($this->volume(['imageLinks' => [
            'thumbnail' => 'https://books.google.com/b.jpg',
            'small' => 'https://books.google.com/a.jpg',
        ]]));
        $this->assertSame('https://books.google.com/a.jpg', $book->coverUrl);

        $foreign = $this->normalize($this->volume(['imageLinks' => ['thumbnail' => 'https://evil.example/x.jpg']]));
        $this->assertNull($foreign->coverUrl);
    }

    public function test_html_is_removed_from_texts(): void
    {
        $book = $this->normalize($this->volume([
            'title' => '<b>Dune</b> &amp; co',
            'description' => '<script>alert(1)</script>Texte',
        ]));

        $this->assertSame('Dune & co', $book->title);
        $this->assertStringNotContainsString('<script>', $book->synopsis);
    }

    public function test_authors_are_deduplicated_and_cleaned(): void
    {
        $book = $this->normalize($this->volume(['authors' => ['A', 'a ', 'B', 123, '']]));

        $this->assertSame(['A', 'B'], $book->authors);
    }
}
