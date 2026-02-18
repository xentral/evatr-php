<?php

declare(strict_types=1);

namespace Xentral\EvatrPhp\Response;

final class StatusMessage
{
    public function __construct(
        public readonly string $status,
        public readonly string $category,
        public readonly int $httpCode,
        public readonly string $message,
        public readonly ?string $field = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'],
            category: $data['kategorie'],
            httpCode: $data['httpcode'],
            message: $data['meldung'],
            field: $data['feld'] ?? null,
        );
    }
}
