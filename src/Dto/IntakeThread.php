<?php

declare(strict_types=1);

namespace AlthoSalud\Altha\Dto;

use AlthoSalud\Altha\Exception\InvalidResponseException;

final readonly class IntakeThread
{
    /**
     * @param list<IntakeMessage> $messages
     */
    public function __construct(
        public string $phone,
        public string $attendant,
        public ?string $intakeCaseId,
        public array $messages,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $caseId = $payload['intake_case_id'] ?? null;
        if (null !== $caseId && !\is_string($caseId)) {
            throw InvalidResponseException::field('intake_case_id', 'string or null');
        }

        $rawMessages = $payload['messages'] ?? null;
        if (!\is_array($rawMessages) || !array_is_list($rawMessages)) {
            throw InvalidResponseException::field('messages', 'list');
        }
        $messages = [];
        foreach ($rawMessages as $message) {
            if (!\is_array($message)) {
                throw InvalidResponseException::field('messages', 'list of objects');
            }
            /** @var array<string, mixed> $message */
            $messages[] = IntakeMessage::fromArray($message);
        }

        return new self(
            phone: self::string($payload, 'phone'),
            attendant: self::string($payload, 'attendant'),
            intakeCaseId: $caseId,
            messages: $messages,
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
