<?php

declare(strict_types=1);

namespace AlthoSalud\Altha\Dto;

use AlthoSalud\Altha\Exception\InvalidResponseException;

final readonly class ApplicationCredentials
{
    /**
     * @param list<string> $products
     */
    public function __construct(
        public int $id,
        public string $slug,
        public string $status,
        public array $products = [],
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $application = $payload;
        if (isset($payload['application']) && \is_array($payload['application'])) {
            $application = $payload['application'];
        }

        $products = [];
        if (array_key_exists('products', $application)) {
            $rawProducts = $application['products'];
            if (!\is_array($rawProducts)) {
                throw InvalidResponseException::field('products', 'list of strings or absent');
            }
            if (!array_is_list($rawProducts)) {
                throw InvalidResponseException::field('products', 'list of strings');
            }
            foreach ($rawProducts as $product) {
                if (!\is_string($product)) {
                    throw InvalidResponseException::field('products', 'list of strings');
                }
                $products[] = $product;
            }
        }

        return new self(
            id: self::integer($application, 'id'),
            slug: self::string($application, 'slug'),
            status: self::string($application, 'status'),
            products: $products,
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
    private static function integer(array $payload, string $field): int
    {
        $value = $payload[$field] ?? null;
        if (!\is_int($value)) {
            throw InvalidResponseException::field($field, 'integer');
        }

        return $value;
    }
}
