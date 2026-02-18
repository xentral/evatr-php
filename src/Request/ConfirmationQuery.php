<?php

declare(strict_types=1);

namespace Xentral\EvatrPhp\Request;

final class ConfirmationQuery
{
    public function __construct(
        public readonly string $ownVatId,
        public readonly string $foreignVatId,
        public readonly ?string $companyName = null,
        public readonly ?string $city = null,
        public readonly ?string $street = null,
        public readonly ?string $postalCode = null,
    ) {
    }

    public static function simple(string $ownVatId, string $foreignVatId): self
    {
        return new self($ownVatId, $foreignVatId);
    }

    public static function qualified(
        string $ownVatId,
        string $foreignVatId,
        string $companyName,
        string $city,
        ?string $street = null,
        ?string $postalCode = null,
    ): self {
        return new self($ownVatId, $foreignVatId, $companyName, $city, $street, $postalCode);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $data = [
            'anfragendeUstid' => $this->ownVatId,
            'angefragteUstid' => $this->foreignVatId,
        ];

        if ($this->companyName !== null) {
            $data['firmenname'] = $this->companyName;
        }

        if ($this->city !== null) {
            $data['ort'] = $this->city;
        }

        if ($this->street !== null) {
            $data['strasse'] = $this->street;
        }

        if ($this->postalCode !== null) {
            $data['plz'] = $this->postalCode;
        }

        return $data;
    }
}
