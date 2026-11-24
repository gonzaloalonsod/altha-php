<?php

declare(strict_types=1);

namespace AlthoSalud\Altha\Dto;

use AlthoSalud\Altha\Exception\InvalidResponseException;

final readonly class IntakeMessage
{
    /**
     * @param list<int> $mediaIds
     */
    public function __construct(
        public int $id,
        public string $direction,
        public string $body,
        public array $mediaIds,
        public string $createdAt,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $id = $payload['id'] ?? null;
        if (!\is_int($id)) {
            throw InvalidResponseException::field('id', 'integer');
        }

        $rawMedia = $payload['media_ids'] ?? [];
        if (!\is_array($rawMedia) || !array_is_list($rawMedia)) {
            throw InvalidResponseException::field('media_ids', 'list of integers');
        }
        $mediaIds = [];
        foreach ($rawMedia as $mediaId) {
            if (!\is_int($mediaId)) {
                throw InvalidResponseException::field('media_ids', 'list of integers');
            }
            $mediaIds[] = $mediaId;
        }

        return new self(
            id: $id,
            direction: self::string($payload, 'direction'),
            body: self::text($payload, 'body'),
            mediaIds: $mediaIds,
            createdAt: self::string($payload, 'created_at'),
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

    /**
     * @param array<string, mixed> $payload
     */
    private static function text(array $payload, string $field): string
    {
        $value = $payload[$field] ?? null;
        if (!\is_string($value)) {
            throw InvalidResponseException::field($field, 'string');
        }

        return $value;
    }
}
