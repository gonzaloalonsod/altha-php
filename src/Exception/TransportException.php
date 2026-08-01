<?php

declare(strict_types=1);

namespace AlthoSalud\Altha\Exception;

final class TransportException extends AlthaException
{
    public static function fromPrevious(\Throwable $previous): self
    {
        return new self('Could not contact Altha.', 0, $previous);
    }
}
