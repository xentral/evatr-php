<?php

declare(strict_types=1);

namespace Xentral\EvatrPhp\Response;

final class MemberState
{
    public function __construct(
        public readonly string $countryCode,
        public readonly string $name,
        public readonly bool $available,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            countryCode: $data['alpha2'],
            name: $data['name'],
            available: $data['verfuegbar'],
        );
    }
}
