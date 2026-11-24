<?php

declare(strict_types=1);

namespace AlthoSalud\Altha\Dto;

use AlthoSalud\Altha\Exception\InvalidResponseException;

final readonly class IntakeMediaUrl
{
    public function __construct(
        public string $url,
        public string $mimeType,
        public int $expiresInSeconds,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $expires = $payload['expires_in_seconds'] ?? null;
        if (!\is_int($expires)) {
            throw InvalidResponseException::field('expires_in_seconds', 'integer');
        }

        return new self(
            url: self::string($payload, 'url'),
            mimeType: self::string($payload, 'mime_type'),
            expiresInSeconds: $expires,
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
