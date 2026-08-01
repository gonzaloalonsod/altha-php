<?php

declare(strict_types=1);

namespace AlthoSalud\Altha\Dto;

use AlthoSalud\Altha\Exception\InvalidResponseException;

final readonly class UsageReport
{
    /**
     * @param array{start: string, end: string}         $period
     * @param array{inbound: int, outbound: int}        $messages
     * @param array{booked: int, booking_allowed: bool} $appointments
     */
    public function __construct(
        public array $period,
        public array $messages,
        public array $appointments,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $period = $payload['period'] ?? null;
        $messages = $payload['messages'] ?? null;
        $appointments = $payload['appointments'] ?? null;
        if (!\is_array($period) || !\is_array($messages) || !\is_array($appointments)) {
            throw InvalidResponseException::message('usage payload missing period/messages/appointments.');
        }

        return new self(
            period: [
                'start' => self::string($period, 'start'),
                'end' => self::string($period, 'end'),
            ],
            messages: [
                'inbound' => self::int($messages, 'inbound'),
                'outbound' => self::int($messages, 'outbound'),
            ],
            appointments: [
                'booked' => self::int($appointments, 'booked'),
                'booking_allowed' => self::bool($appointments, 'booking_allowed'),
            ],
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
    private static function int(array $payload, string $field): int
    {
        $value = $payload[$field] ?? null;
        if (!\is_int($value)) {
            throw InvalidResponseException::field($field, 'integer');
        }

        return $value;
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
}
