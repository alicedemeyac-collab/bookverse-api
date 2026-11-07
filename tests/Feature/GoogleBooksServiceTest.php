<?php

namespace Tests\Feature;

use App\Exceptions\GoogleBooksException;
use App\Exceptions\GoogleBooksQuotaException;
use App\Exceptions\GoogleBooksUnavailableException;
use App\Services\GoogleBooks\GoogleBooksService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class GoogleBooksServiceTest extends TestCase
{
    private const SEARCH_URL = 'www.googleapis.com/books/v1/volumes*';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'services.google_books.key' => null,
            'services.google_books.retry_sleep' => 0, // pas d'attente entre les tentatives
            'services.google_books.per_minute' => 100,
            'services.google_books.per_day' => 1000,
        ]);
    }

    private function volume(array $info = []): array
    {
        return ['id' => 'abc123XYZ', 'volumeInfo' => array_merge(['title' => 'Dune'], $info)];
    }

    private function fakeSearch(?array $items = null): void
    {
        $items ??= [$this->volume()];

        Http::fake([self::SEARCH_URL => Http::response(['totalItems' => count($items), 'items' => $items])]);
    }

    private function service(): GoogleBooksService
    {
        return app(GoogleBooksService::class);
    }

    // ---- Construction des requêtes ----

    public function test_search_by_title_builds_the_query_and_normalizes(): void
    {
        $this->fakeSearch();

        $result = $this->service()->searchByTitle('Le Hobbit');

        $this->assertSame(1, $result['total']);
        $this->assertSame('Dune', $result['items'][0]->title);
        Http::assertSent(fn (Request $r) => $r['q'] === 'intitle:"Le Hobbit"'
            && (int) $r['maxResults'] === 12
            && $r['printType'] === 'books');
    }

    public function test_search_by_author_and_subject(): void
    {
        $this->fakeSearch();

        $this->service()->searchByAuthor('Tolkien');
        $this->service()->searchBySubject('fantasy');

        Http::assertSent(fn (Request $r) => $r['q'] === 'inauthor:"Tolkien"');
        Http::assertSent(fn (Request $r) => $r['q'] === 'subject:"fantasy"');
    }

    public function test_search_by_isbn_strips_dashes(): void
    {
        $this->fakeSearch();

        $this->service()->searchByIsbn('978-0-306-40615-7');

        Http::assertSent(fn (Request $r) => $r['q'] === 'isbn:9780306406157');
    }

    public function test_invalid_input_never_reaches_google(): void
    {
        Http::fake();

        foreach ([
                     fn () => $this->service()->searchByIsbn('abc'),
                     fn () => $this->service()->searchByTitle('   '),
                     fn () => $this->service()->search(''),
                     fn () => $this->service()->find('../users'),
                 ] as $call) {
            try {
                $call();
                $this->fail('Une InvalidArgumentException était attendue.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }

        Http::assertNothingSent();
    }

    public function test_quotes_in_user_input_cannot_break_the_query(): void
    {
        $this->fakeSearch();

        $this->service()->searchByTitle('Dune" OR inauthor:"x');

        Http::assertSent(fn (Request $r) => $r['q'] === 'intitle:"Dune OR inauthor x"');
    }

    public function test_limit_and_language_options_are_sanitized(): void
    {
        $this->fakeSearch();

        $this->service()->searchByTitle('a1', ['limit' => 500, 'language' => 'FR']);
        $this->service()->searchByTitle('a2', ['limit' => 0, 'language' => 'francais']);

        Http::assertSent(fn (Request $r) => $r['q'] === 'intitle:"a1"'
            && (int) $r['maxResults'] === 40 && $r['langRestrict'] === 'fr');
        Http::assertSent(fn (Request $r) => $r['q'] === 'intitle:"a2"'
            && (int) $r['maxResults'] === 1 && ! isset($r['langRestrict']));
    }

    public function test_api_key_is_sent_only_when_configured(): void
    {
        $this->fakeSearch();
        $this->service()->searchByTitle('sans cle');
        Http::assertSent(fn (Request $r) => ! str_contains($r->url(), 'key='));

        config(['services.google_books.key' => 'secret-key']);
        $this->service()->searchByTitle('avec cle');
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'key=secret-key'));
    }

    // ---- Résultats ----

    public function test_empty_result_is_not_an_error(): void
    {
        Http::fake([self::SEARCH_URL => Http::response(['totalItems' => 0])]);

        $result = $this->service()->searchByTitle('introuvable');

        $this->assertSame(0, $result['total']);
        $this->assertSame([], $result['items']);
    }

    public function test_unusable_volumes_are_skipped(): void
    {
        $this->fakeSearch([
            ['id' => 'bad1', 'volumeInfo' => ['title' => '']],
            $this->volume(),
            'garbage',
        ]);

        $this->assertCount(1, $this->service()->searchByTitle('x')['items']);
    }

    public function test_find_returns_a_book_or_null(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes/abc123XYZ*' => Http::response($this->volume()),
            'www.googleapis.com/books/v1/volumes/unknown1*' => Http::response([], 404),
        ]);

        $this->assertSame('Dune', $this->service()->find('abc123XYZ')->title);
        $this->assertNull($this->service()->find('unknown1'));
    }

    // ---- Erreurs ----

    public function test_server_error_is_retried_then_reported_as_unavailable(): void
    {
        Http::fake([self::SEARCH_URL => Http::response([], 503)]);

        try {
            $this->service()->searchByTitle('x');
            $this->fail('Une exception était attendue.');
        } catch (GoogleBooksUnavailableException) {
            Http::assertSentCount(2); // 1 appel + 1 nouvelle tentative
        }
    }

    public function test_network_failure_is_reported_as_unavailable(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->expectException(GoogleBooksUnavailableException::class);

        $this->service()->searchByTitle('x');
    }

    public function test_quota_errors_are_reported_and_not_retried(): void
    {
        Http::fake([self::SEARCH_URL => Http::response(
            ['error' => ['errors' => [['reason' => 'rateLimitExceeded']]]],
            403,
        )]);

        try {
            $this->service()->searchByTitle('x');
            $this->fail('Une exception était attendue.');
        } catch (GoogleBooksQuotaException) {
            Http::assertSentCount(1); // un 4xx n'est jamais réessayé
        }
    }

    public function test_429_is_a_quota_error(): void
    {
        Http::fake([self::SEARCH_URL => Http::response([], 429)]);

        $this->expectException(GoogleBooksQuotaException::class);

        $this->service()->searchByTitle('x');
    }

    public function test_invalid_key_is_not_reported_as_quota(): void
    {
        Http::fake([self::SEARCH_URL => Http::response(
            ['error' => ['errors' => [['reason' => 'keyInvalid']]]],
            403,
        )]);

        try {
            $this->service()->searchByTitle('x');
            $this->fail('Une exception était attendue.');
        } catch (GoogleBooksException $e) {
            $this->assertNotInstanceOf(GoogleBooksQuotaException::class, $e);
        }
    }

    // ---- Cache et limites locales ----

    public function test_identical_searches_are_served_from_cache(): void
    {
        $this->fakeSearch();

        $this->service()->searchByTitle('Dune');
        $this->service()->searchByTitle('Dune');

        Http::assertSentCount(1);
    }

    public function test_errors_are_never_cached(): void
    {
        Http::fake([self::SEARCH_URL => Http::sequence()
            ->pushStatus(503)
            ->pushStatus(503)
            ->push(['totalItems' => 1, 'items' => [$this->volume()]])]);

        try {
            $this->service()->searchByTitle('Dune');
        } catch (GoogleBooksUnavailableException) {
        }

        $this->assertSame('Dune', $this->service()->searchByTitle('Dune')['items'][0]->title);
    }

    public function test_local_daily_limit_blocks_new_calls(): void
    {
        config(['services.google_books.per_day' => 1]);
        $this->fakeSearch();

        $this->service()->searchByTitle('premier');

        try {
            $this->service()->searchByTitle('second');
            $this->fail('La limite locale aurait dû bloquer cet appel.');
        } catch (GoogleBooksQuotaException) {
            Http::assertSentCount(1);
        }
    }

    public function test_cached_results_do_not_use_the_local_quota(): void
    {
        config(['services.google_books.per_day' => 1]);
        $this->fakeSearch();

        $this->service()->searchByTitle('Dune');
        $this->service()->searchByTitle('Dune'); // servi par le cache : aucune exception

        Http::assertSentCount(1);
    }
}
