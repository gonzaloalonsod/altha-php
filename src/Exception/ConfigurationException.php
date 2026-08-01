<?php

declare(strict_types=1);

namespace AlthoSalud\Altha\Exception;

final class ConfigurationException extends AlthaException
{
    public static function missingBaseUrl(): self
    {
        return new self('Altha base URL is not configured.');
    }

    public static function missingApiKey(): self
    {
        return new self('Altha API key is not configured.');
    }
}
