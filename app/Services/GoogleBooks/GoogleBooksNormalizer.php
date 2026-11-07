<?php

namespace App\Services\GoogleBooks;

class GoogleBooksNormalizer
{
    /** Transforme un « volume » Google en BookData, ou null s'il est inutilisable. */
    public function normalize(array $volume): ?BookData
    {
        $id = $volume['id'] ?? null;
        if (! is_string($id) || ! preg_match('/^[A-Za-z0-9_-]{1,20}$/', $id)) {
            return null;
        }

        $info = $volume['volumeInfo'] ?? null;
        if (! is_array($info)) {
            return null;
        }

        // Sans titre, le livre est inutilisable
        $title = $this->text($info['title'] ?? null, 255);
        if ($title === null) {
            return null;
        }

        [$isbn10, $isbn13] = $this->isbns($info['industryIdentifiers'] ?? null);

        return new BookData(
            googleBooksId: $id,
            title: $title,
            subtitle: $this->text($info['subtitle'] ?? null, 255),
            authors: $this->names($info['authors'] ?? null, 150, 10),
            synopsis: $this->description($info['description'] ?? null),
            isbn10: $isbn10,
            isbn13: $isbn13,
            publicationDate: $this->date($info['publishedDate'] ?? null),
            publisher: $this->text($info['publisher'] ?? null, 150),
            pageCount: $this->pageCount($info['pageCount'] ?? null),
            language: $this->language($info['language'] ?? null),
            coverUrl: $this->cover($info['imageLinks'] ?? null),
            categories: $this->names($info['categories'] ?? null, 100, 10),
        );
    }

    /** Texte sur une ligne : sans HTML, espaces réduits, longueur bornée. */
    private function text(mixed $value, int $max): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    /** Résumé : le HTML devient du texte avec retours à la ligne. */
    private function description(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = preg_replace('#<br\s*/?>|</p>|</div>|</li>#i', "\n", $value) ?? '';
        $value = strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $value = preg_replace('/[ \t]+/u', ' ', $value) ?? '';
        $value = preg_replace('/ *\n */', "\n", $value) ?? '';
        $value = trim(preg_replace("/\n{3,}/", "\n\n", $value) ?? '');

        return $value === '' ? null : mb_substr($value, 0, 10000);
    }

    /** Liste de noms nettoyés, sans doublon (insensible à la casse), bornée. */
    private function names(mixed $values, int $maxLength, int $maxCount): array
    {
        if (! is_array($values)) {
            return [];
        }

        $names = [];
        foreach ($values as $value) {
            $name = $this->text($value, $maxLength);
            if ($name !== null) {
                $names[mb_strtolower($name)] ??= $name;
            }
        }

        return array_slice(array_values($names), 0, $maxCount);
    }

    /** Accepte 2015, 2015-06 et 2015-06-15 ; renvoie une date complète valide ou null. */
    private function date(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^(\d{4})(?:-(\d{2}))?(?:-(\d{2}))?$/', trim($value), $m)) {
            return null;
        }

        $year = (int) $m[1];
        $month = (int) ($m[2] ?? 1);
        $day = (int) ($m[3] ?? 1);

        // Google renvoie parfois « 0000 » pour une date inconnue
        if ($year < 1000 || $year > (int) date('Y') + 2 || ! checkdate($month, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /** @return array{0: ?string, 1: ?string} [ISBN-10, ISBN-13], seulement s'ils sont valides */
    private function isbns(mixed $identifiers): array
    {
        $isbn10 = null;
        $isbn13 = null;

        if (! is_array($identifiers)) {
            return [null, null];
        }

        foreach ($identifiers as $item) {
            if (! is_array($item)) {
                continue;
            }

            $code = strtoupper(preg_replace('/[\s-]/', '', (string) ($item['identifier'] ?? '')) ?? '');
            $type = $item['type'] ?? null;

            if ($type === 'ISBN_13' && $isbn13 === null && $this->validIsbn13($code)) {
                $isbn13 = $code;
            } elseif ($type === 'ISBN_10' && $isbn10 === null && $this->validIsbn10($code)) {
                $isbn10 = $code;
            }
        }

        return [$isbn10, $isbn13];
    }

    // Clé de contrôle : somme pondérée 1,3,1,3... divisible par 10
    private function validIsbn13(string $code): bool
    {
        if (! preg_match('/^\d{13}$/', $code)) {
            return false;
        }

        $sum = 0;
        foreach (str_split(substr($code, 0, 12)) as $i => $digit) {
            $sum += (int) $digit * ($i % 2 === 0 ? 1 : 3);
        }

        return (10 - $sum % 10) % 10 === (int) $code[12];
    }

    // Clé de contrôle : somme pondérée 10,9,...,1 divisible par 11 (le X vaut 10)
    private function validIsbn10(string $code): bool
    {
        if (! preg_match('/^\d{9}[\dX]$/', $code)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += ($code[$i] === 'X' ? 10 : (int) $code[$i]) * (10 - $i);
        }

        return $sum % 11 === 0;
    }

    // pageCount vaut parfois 0 : la colonne exige une valeur > 0
    private function pageCount(mixed $value): ?int
    {
        return is_int($value) && $value > 0 && $value <= 10000 ? $value : null;
    }

    // Garde les deux premières lettres (« zh-CN » devient « zh ») ; « und » est rejeté
    private function language(mixed $value): ?string
    {
        if (is_string($value) && preg_match('/^([a-z]{2})(?:-|$)/i', $value, $m)) {
            return strtolower($m[1]);
        }

        return null;
    }

    /** Meilleure couverture disponible, en https, sur un domaine Google uniquement. */
    private function cover(mixed $links): ?string
    {
        if (! is_array($links)) {
            return null;
        }

        foreach (['extraLarge', 'large', 'medium', 'small', 'thumbnail', 'smallThumbnail'] as $size) {
            $url = $links[$size] ?? null;
            if (! is_string($url)) {
                continue;
            }

            $url = preg_replace('#^http://#i', 'https://', trim($url)) ?? '';
            $url = str_replace('&edge=curl', '', $url); // supprime l'effet de page cornée
            $host = parse_url($url, PHP_URL_HOST);

            if (
                str_starts_with($url, 'https://')
                && strlen($url) <= 500
                && is_string($host)
                && preg_match('/(^|\.)(google\.com|googleusercontent\.com)$/i', $host)
            ) {
                return $url;
            }
        }

        return null;
    }
}
