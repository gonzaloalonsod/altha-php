<?php

declare(strict_types=1);

namespace AlthoSalud\Altha\Exception;

final class InvalidResponseException extends AlthaException
{
    public static function message(string $message, ?\Throwable $previous = null): self
    {
        return new self('Invalid Altha response: '.$message, 0, $previous);
    }

    public static function field(string $field, string $expected, ?\Throwable $previous = null): self
    {
        return self::message(\sprintf('field "%s" must be %s.', $field, $expected), $previous);
    }
}
