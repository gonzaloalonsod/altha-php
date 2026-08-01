<?php

declare(strict_types=1);

namespace AlthoSalud\Altha\Dto;

use AlthoSalud\Altha\Exception\InvalidResponseException;

final readonly class OutboundMessageResult
{
    public function __construct(
        public string $status,
        public string $to,
        public int|string|null $messageId = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $messageId = $payload['message_id'] ?? null;
        if (null !== $messageId && !\is_int($messageId) && !\is_string($messageId)) {
            throw InvalidResponseException::field('message_id', 'integer, string, or null');
        }

        return new self(
            status: self::string($payload, 'status'),
            to: self::string($payload, 'to'),
            messageId: $messageId,
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
