<?php

namespace App\Services\GoogleBooks;

use App\Exceptions\GoogleBooksException;
use App\Exceptions\GoogleBooksQuotaException;
use App\Exceptions\GoogleBooksUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;
use Throwable;

class GoogleBooksService
{
    // On ne demande à Google que les champs utiles : réponses plus légères
    private const VOLUME_FIELDS = 'volumeInfo(title,subtitle,authors,publisher,publishedDate,description,industryIdentifiers,pageCount,categories,imageLinks,language)';
    private const LIST_FIELDS = 'totalItems,items(id,'.self::VOLUME_FIELDS.')';
    private const DETAIL_FIELDS = 'id,'.self::VOLUME_FIELDS;

    private const QUOTA_REASONS = ['rateLimitExceeded', 'userRateLimitExceeded', 'dailyLimitExceeded', 'quotaExceeded'];

    public function __construct(private readonly GoogleBooksNormalizer $normalizer)
    {
    }

    // ---- Recherches ------------------------------------------------------

    /** @return array{total: int, items: list<BookData>} */
    public function searchByTitle(string $title, array $options = []): array
    {
        return $this->search('intitle:"'.$this->phrase($title).'"', $options);
    }

    public function searchByAuthor(string $author, array $options = []): array
    {
        return $this->search('inauthor:"'.$this->phrase($author).'"', $options);
    }

    public function searchBySubject(string $subject, array $options = []): array
    {
        return $this->search('subject:"'.$this->phrase($subject).'"', $options);
    }

    public function searchByIsbn(string $isbn, array $options = []): array
    {
        $isbn = strtoupper(preg_replace('/[\s-]/', '', $isbn) ?? '');

        if (! preg_match('/^(\d{13}|\d{9}[\dX])$/', $isbn)) {
            throw new InvalidArgumentException("ISBN invalide : 10 ou 13 caractères attendus.");
        }

        return $this->search('isbn:'.$isbn, $options);
    }

    /**
     * Options : limit (1 à 40), offset, order (relevance|newest), language (2 lettres).
     *
     * @return array{total: int, items: list<BookData>}
     */
    public function search(string $query, array $options = []): array
    {
        $query = trim($query);
        if ($query === '' || mb_strlen($query) > 200) {
            throw new InvalidArgumentException('La recherche doit contenir entre 1 et 200 caractères.');
        }

        $params = [
            'q' => $query,
            'maxResults' => max(1, min(40, (int) ($options['limit'] ?? 12))),
            'startIndex' => max(0, (int) ($options['offset'] ?? 0)),
            'printType' => 'books',
            'orderBy' => ($options['order'] ?? 'relevance') === 'newest' ? 'newest' : 'relevance',
            'fields' => self::LIST_FIELDS,
        ];

        $language = $options['language'] ?? null;
        if (is_string($language) && preg_match('/^[a-z]{2}$/i', $language)) {
            $params['langRestrict'] = strtolower($language);
        }

        $payload = Cache::remember(
            $this->cacheKey('search', $params),
            (int) config('services.google_books.cache_ttl'),
            function () use ($params) {
                $json = $this->request('volumes', $params);

                $items = collect($json['items'] ?? [])
                    ->map(fn ($volume) => is_array($volume) ? $this->normalizer->normalize($volume) : null)
                    ->filter()                                         // volumes inutilisables écartés
                    ->unique(fn (BookData $book) => $book->googleBooksId)
                    ->map(fn (BookData $book) => $book->toArray())     // tableaux simples : sûrs à mettre en cache
                    ->values()
                    ->all();

                return ['total' => (int) ($json['totalItems'] ?? 0), 'items' => $items];
            },
        );

        return [
            'total' => $payload['total'],
            'items' => array_map(BookData::fromArray(...), $payload['items']),
        ];
    }

    // ---- Détail d'un livre -----------------------------------------------

    public function find(string $googleBooksId): ?BookData
    {
        // L'identifiant finit dans l'URL : on le valide pour empêcher toute manipulation du chemin
        if (! preg_match('/^[A-Za-z0-9_-]{1,20}$/', $googleBooksId)) {
            throw new InvalidArgumentException('Identifiant Google Books invalide.');
        }

        $params = ['fields' => self::DETAIL_FIELDS];
        $cacheKey = $this->cacheKey('detail:'.$googleBooksId, $params);

        $payload = Cache::get($cacheKey);

        if ($payload === null) {
            $json = $this->request('volumes/'.$googleBooksId, $params, nullOn404: true);
            $book = $json === null ? null : $this->normalizer->normalize($json);

            if ($book === null) {
                return null;
            }

            $payload = $book->toArray();
            Cache::put($cacheKey, $payload, (int) config('services.google_books.detail_cache_ttl'));
        }

        return BookData::fromArray($payload);
    }

    // ---- Appels HTTP -------------------------------------------------------

    private function request(string $path, array $params, bool $nullOn404 = false): ?array
    {
        $this->guardLocalLimits();

        // La clé est ajoutée ici, jamais dans $params : elle ne finit donc ni dans le cache ni dans les logs
        if ($key = config('services.google_books.key')) {
            $params['key'] = $key;
        }

        try {
            $response = Http::baseUrl(rtrim((string) config('services.google_books.base_url'), '/'))
                ->acceptJson()
                ->timeout((int) config('services.google_books.timeout'))
                ->connectTimeout(3)
                // Une seconde tentative seulement si Google est en panne, jamais sur un 4xx (quota, clé...)
                ->retry(
                    2,
                    (int) config('services.google_books.retry_sleep'),
                    fn (Throwable $e) => $e instanceof ConnectionException
                        || ($e instanceof RequestException && $e->response->serverError()),
                    throw: false,
                )
                ->get($path, $params);
        } catch (ConnectionException $e) {
            Log::warning('Google Books injoignable', ['path' => $path]);

            throw new GoogleBooksUnavailableException('Google Books est injoignable.', 0, $e);
        }

        return $this->handle($response, $path, $nullOn404);
    }

    private function handle(Response $response, string $path, bool $nullOn404): ?array
    {
        if ($response->successful()) {
            $json = $response->json();

            if (! is_array($json)) {
                throw new GoogleBooksUnavailableException('Réponse Google Books illisible.');
            }

            return $json;
        }

        $status = $response->status();

        if ($status === 404 && $nullOn404) {
            return null;
        }

        Log::warning('Erreur Google Books', ['path' => $path, 'status' => $status]);

        if ($status === 429 || ($status === 403 && $this->isQuotaError($response))) {
            throw new GoogleBooksQuotaException(retryAfter: (int) $response->header('Retry-After') ?: null);
        }

        if ($status === 403) {
            throw new GoogleBooksException('Accès refusé par Google Books : vérifie la clé API.');
        }

        if ($status >= 500) {
            throw new GoogleBooksUnavailableException("Google Books a répondu {$status}.");
        }

        throw new GoogleBooksException("Requête refusée par Google Books ({$status}).");
    }

    private function isQuotaError(Response $response): bool
    {
        return in_array($response->json('error.errors.0.reason'), self::QUOTA_REASONS, true)
            || $response->json('error.status') === 'RESOURCE_EXHAUSTED';
    }

    // ---- Limites locales ---------------------------------------------------

    private function guardLocalLimits(): void
    {
        $limits = [
            'minute' => (int) config('services.google_books.per_minute'),
            'day' => (int) config('services.google_books.per_day'),
        ];

        foreach ($limits as $window => $max) {
            $key = 'google-books:'.$window;

            if (RateLimiter::tooManyAttempts($key, $max)) {
                throw new GoogleBooksQuotaException(
                    "Limite locale d'appels Google Books atteinte ({$window}).",
                    RateLimiter::availableIn($key),
                );
            }
        }

        RateLimiter::hit('google-books:minute', 60);
        RateLimiter::hit('google-books:day', 86400);
    }

    // ---- Utilitaires -------------------------------------------------------

    /** Saisie utilisateur insérée entre guillemets : on retire ceux qui casseraient la syntaxe de q. */
    private function phrase(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', str_replace(['"', ':'], ' ', $text)) ?? '');

        if ($text === '') {
            throw new InvalidArgumentException('La recherche ne peut pas être vide.');
        }

        return mb_substr($text, 0, 100);
    }

    private function cacheKey(string $type, array $params): string
    {
        return 'google-books:'.$type.':'.sha1(json_encode($params));
    }
}
