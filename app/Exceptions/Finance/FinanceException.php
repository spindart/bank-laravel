<?php

namespace App\Exceptions\Finance;

use RuntimeException;

class FinanceException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 422
    ) {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}

