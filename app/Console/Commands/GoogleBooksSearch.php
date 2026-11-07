<?php

namespace App\Console\Commands;

use App\Exceptions\GoogleBooksException;
use App\Exceptions\GoogleBooksQuotaException;
use App\Services\GoogleBooks\GoogleBooksService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use InvalidArgumentException;

class GoogleBooksSearch extends Command
{
    protected $signature = 'google-books:search
        {query : Texte, ISBN ou identifiant Google Books}
        {--by=title : title, author, isbn, subject ou id}
        {--limit=10 : Nombre de résultats (1 à 40)}
        {--lang= : Restreindre à une langue (ex. fr)}';

    protected $description = 'Interroge Google Books et affiche les résultats normalisés';

    public function handle(GoogleBooksService $google): int
    {
        $query = (string) $this->argument('query');
        $options = ['limit' => (int) $this->option('limit'), 'language' => $this->option('lang') ?: null];

        try {
            if ($this->option('by') === 'id') {
                $book = $google->find($query);
                $result = ['total' => $book ? 1 : 0, 'items' => $book ? [$book] : []];
            } else {
                $result = match ($this->option('by')) {
                    'title' => $google->searchByTitle($query, $options),
                    'author' => $google->searchByAuthor($query, $options),
                    'isbn' => $google->searchByIsbn($query, $options),
                    'subject' => $google->searchBySubject($query, $options),
                    default => throw new InvalidArgumentException('Option --by inconnue (title, author, isbn, subject, id).'),
                };
            }
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::INVALID;
        } catch (GoogleBooksQuotaException $e) {
            $this->error($e->getMessage().($e->retryAfter ? " Réessaie dans {$e->retryAfter} s." : ''));

            return self::FAILURE;
        } catch (GoogleBooksException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($result['items'] === []) {
            $this->warn('Aucun résultat.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Titre', 'Auteurs', 'Date', 'Pages', 'Lang', 'ISBN-13', 'Couv.'],
            array_map(fn ($book) => [
                $book->googleBooksId,
                Str::limit($book->title, 40),
                Str::limit(implode(', ', $book->authors), 30) ?: '-',
                $book->publicationDate ?? '-',
                $book->pageCount ?? '-',
                $book->language ?? '-',
                $book->isbn13 ?? '-',
                $book->coverUrl ? 'oui' : 'non',
            ], $result['items']),
        );

        $this->info(count($result['items']).' affiché(s) sur environ '.$result['total'].' (total approximatif).');

        return self::SUCCESS;
    }
}
