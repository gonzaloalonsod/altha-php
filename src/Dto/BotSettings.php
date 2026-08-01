<?php

declare(strict_types=1);

namespace AlthoSalud\Altha\Dto;

use AlthoSalud\Altha\Exception\InvalidResponseException;

final readonly class BotSettings
{
    /**
     * @param array<string, string>|null $textOverrides
     */
    public function __construct(
        public string $locale,
        public bool $bookingEnabled,
        public bool $remindersEnabled,
        public bool $phoneIdentityConfirmEnabled,
        public ?array $textOverrides = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $overrides = null;
        if (\array_key_exists('text_overrides', $payload) && null !== $payload['text_overrides']) {
            if (!\is_array($payload['text_overrides'])) {
                throw InvalidResponseException::field('text_overrides', 'object or null');
            }
            $overrides = [];
            foreach ($payload['text_overrides'] as $key => $value) {
                if (!\is_string($key) || !\is_string($value)) {
                    throw InvalidResponseException::field('text_overrides', 'string map');
                }
                $overrides[$key] = $value;
            }
        }

        return new self(
            locale: self::string($payload, 'locale'),
            bookingEnabled: self::bool($payload, 'booking_enabled'),
            remindersEnabled: self::bool($payload, 'reminders_enabled'),
            phoneIdentityConfirmEnabled: self::bool($payload, 'phone_identity_confirm_enabled'),
            textOverrides: $overrides,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'locale' => $this->locale,
            'booking_enabled' => $this->bookingEnabled,
            'reminders_enabled' => $this->remindersEnabled,
            'phone_identity_confirm_enabled' => $this->phoneIdentityConfirmEnabled,
            'text_overrides' => $this->textOverrides,
        ];
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
}
