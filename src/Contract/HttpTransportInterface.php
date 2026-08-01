<?php

declare(strict_types=1);

namespace AlthoSalud\Altha\Contract;

use AlthoSalud\Altha\Http\HttpResponse;

interface HttpTransportInterface
{
    /**
     * @param array<string, string> $headers
     */
    public function request(string $method, string $url, ?string $body = null, array $headers = []): HttpResponse;
}
