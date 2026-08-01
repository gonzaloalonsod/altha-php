<?php

declare(strict_types=1);

namespace AlthoSalud\Altha\Dto;

use AlthoSalud\Altha\Exception\InvalidResponseException;

final readonly class ChannelStatus
{
    public function __construct(
        public bool $channelConfigured,
        public string $connectionStatus,
        public ?string $gupshupAppId = null,
        public ?string $gupshupAppName = null,
        public ?string $whatsappSource = null,
        public ?string $gupshupApiKeyMask = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            channelConfigured: self::bool($payload, 'channel_configured'),
            connectionStatus: self::string($payload, 'connection_status'),
            gupshupAppId: self::nullableString($payload, 'gupshup_app_id'),
            gupshupAppName: self::nullableString($payload, 'gupshup_app_name'),
            whatsappSource: self::nullableString($payload, 'whatsapp_source'),
            gupshupApiKeyMask: self::nullableString($payload, 'gupshup_api_key_mask'),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function bool(array $payload, string $field): bool
    {
        $value = $payload[$field] ?? null;
        if (!\is_bool($value)) {
            throw InvalidResponseException::field($field, 'boolean');
        }

        return $value;
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
    private static function nullableString(array $payload, string $field): ?string
    {
        if (!\array_key_exists($field, $payload) || null === $payload[$field]) {
            return null;
        }
        if (!\is_string($payload[$field])) {
            throw InvalidResponseException::field($field, 'string or null');
        }

        return $payload[$field];
    }
}
