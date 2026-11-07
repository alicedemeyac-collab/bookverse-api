<?php

namespace App\Exceptions;

use Throwable;

class GoogleBooksQuotaException extends GoogleBooksException
{
    public function __construct(
        string $message = 'Le quota Google Books est dépassé.',
        public readonly ?int $retryAfter = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
