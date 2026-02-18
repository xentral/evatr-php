<?php

declare(strict_types=1);

namespace Xentral\EvatrPhp\Exception;

use RuntimeException;
use Xentral\EvatrPhp\Enum\StatusCode;

class EvatrException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?StatusCode $statusCode = null,
        int $httpCode = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $httpCode, $previous);
    }
}
