<?php

declare(strict_types=1);

namespace Xentral\EvatrPhp\Response;

use Xentral\EvatrPhp\Enum\ComparisonResult;
use Xentral\EvatrPhp\Enum\StatusCode;

final class ConfirmationResult
{
    public function __construct(
        public readonly StatusCode $status,
        public readonly string $queryTimestamp,
        public readonly ?string $id = null,
        public readonly ?string $validFrom = null,
        public readonly ?string $validUntil = null,
        public readonly ?string $companyName = null,
        public readonly ?string $street = null,
        public readonly ?string $postalCode = null,
        public readonly ?string $city = null,
        public readonly ?ComparisonResult $companyNameResult = null,
        public readonly ?ComparisonResult $streetResult = null,
        public readonly ?ComparisonResult $postalCodeResult = null,
        public readonly ?ComparisonResult $cityResult = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: StatusCode::from($data['status']),
            queryTimestamp: $data['anfrageZeitpunkt'],
            id: $data['id'] ?? null,
            validFrom: $data['gueltigAb'] ?? null,
            validUntil: $data['gueltigBis'] ?? null,
            companyName: $data['ergFirmenname'] ?? null,
            street: $data['ergStrasse'] ?? null,
            postalCode: $data['ergPlz'] ?? null,
            city: $data['ergOrt'] ?? null,
            companyNameResult: isset($data['ergFirmenname']) ? ComparisonResult::tryFrom($data['ergFirmenname']) : null,
            streetResult: isset($data['ergStrasse']) ? ComparisonResult::tryFrom($data['ergStrasse']) : null,
            postalCodeResult: isset($data['ergPlz']) ? ComparisonResult::tryFrom($data['ergPlz']) : null,
            cityResult: isset($data['ergOrt']) ? ComparisonResult::tryFrom($data['ergOrt']) : null,
        );
    }

    public function isValid(): bool
    {
        return in_array($this->status, [
            StatusCode::VALID,
            StatusCode::VALID_QUALIFIED_SPECIAL,
        ], true);
    }
}
