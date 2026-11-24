<?php

declare(strict_types=1);

namespace AlthoSalud\Altha\Dto;

use AlthoSalud\Altha\Exception\InvalidResponseException;

final readonly class PricingQuote
{
    public function __construct(
        public string $sku,
        public string $name,
        public int $amountCents,
        public string $currency,
        public string $product,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $amount = $payload['amount_cents'] ?? null;
        if (!\is_int($amount)) {
            throw InvalidResponseException::field('amount_cents', 'integer');
        }

        return new self(
            sku: self::string($payload, 'sku'),
            name: self::string($payload, 'name'),
            amountCents: $amount,
            currency: self::string($payload, 'currency'),
            product: self::string($payload, 'product'),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function string(array $payload, string $field): string
    {
        $value = $payload[$field] ?? null;
        if (!\is_string($value) || '' === $value) {
            throw InvalidResponseException::field($field, 'non-empty string');
        }

        return $value;
    }
}
