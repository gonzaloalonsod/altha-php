<?php

declare(strict_types=1);

namespace AlthoSalud\Altha;

use AlthoSalud\Altha\Contract\AlthaClientInterface;
use AlthoSalud\Altha\Contract\HttpTransportInterface;
use AlthoSalud\Altha\Dto\ApplicationCredentials;
use AlthoSalud\Altha\Dto\BotSettings;
use AlthoSalud\Altha\Dto\ChannelStatus;
use AlthoSalud\Altha\Dto\OutboundMessageResult;
use AlthoSalud\Altha\Dto\UsageReport;
use AlthoSalud\Altha\Exception\ApiException;
use AlthoSalud\Altha\Exception\ConfigurationException;
use AlthoSalud\Altha\Exception\InvalidResponseException;
use AlthoSalud\Altha\Http\CurlTransport;

final readonly class AlthaClient implements AlthaClientInterface
{
    public function __construct(
        private string $baseUrl,
        private ?string $apiKey = null,
        private HttpTransportInterface $transport = new CurlTransport(),
    ) {
    }

    public function isConfigured(): bool
    {
        return '' !== trim($this->baseUrl) && null !== $this->apiKey && '' !== trim($this->apiKey);
    }

    public function withApiKey(string $apiKey): AlthaClientInterface
    {
        return new self($this->baseUrl, $apiKey, $this->transport);
    }

    public function me(): ApplicationCredentials
    {
        return ApplicationCredentials::fromArray($this->request('GET', '/api/v1/me'));
    }

    public function getSecretaryChannel(): ChannelStatus
    {
        return ChannelStatus::fromArray($this->request('GET', '/api/v1/secretary/channel'));
    }

    public function updateSecretaryChannel(array $payload): ChannelStatus
    {
        return ChannelStatus::fromArray($this->request('PUT', '/api/v1/secretary/channel', $payload));
    }

    public function getSecretaryBot(): BotSettings
    {
        return BotSettings::fromArray($this->request('GET', '/api/v1/secretary/bot'));
    }

    public function updateSecretaryBot(array $payload): BotSettings
    {
        return BotSettings::fromArray($this->request('PATCH', '/api/v1/secretary/bot', $payload));
    }

    public function getSecretaryUsage(): UsageReport
    {
        return UsageReport::fromArray($this->request('GET', '/api/v1/secretary/usage'));
    }

    public function sendSecretaryMessage(
        string $to,
        ?string $text = null,
        ?string $templateId = null,
        array $templateParams = [],
    ): OutboundMessageResult {
        $to = trim($to);
        if ('' === $to) {
            throw new \InvalidArgumentException('Recipient "to" must not be empty.');
        }

        $body = ['to' => $to];
        $trimmedText = null === $text ? null : trim($text);
        $trimmedTemplateId = null === $templateId ? null : trim($templateId);

        if (null !== $trimmedTemplateId && '' !== $trimmedTemplateId) {
            $body['template'] = [
                'id' => $trimmedTemplateId,
                'params' => $templateParams,
            ];
        } elseif (null !== $trimmedText && '' !== $trimmedText) {
            $body['text'] = $trimmedText;
        } else {
            throw new \InvalidArgumentException('Provide text or templateId.');
        }

        return OutboundMessageResult::fromArray(
            $this->request('POST', '/api/v1/secretary/messages', $body),
        );
    }

    /**
     * @param array<string, mixed>|null $jsonBody
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, ?array $jsonBody = null): array
    {
        $baseUrl = rtrim(trim($this->baseUrl), '/');
        if ('' === $baseUrl) {
            throw ConfigurationException::missingBaseUrl();
        }

        $apiKey = null === $this->apiKey ? '' : trim($this->apiKey);
        if ('' === $apiKey) {
            throw ConfigurationException::missingApiKey();
        }

        $headers = [
            'Authorization' => 'Bearer '.$apiKey,
            'Accept' => 'application/json',
        ];

        $body = null;
        if (null !== $jsonBody) {
            $headers['Content-Type'] = 'application/json';
            try {
                $body = json_encode($jsonBody, \JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw InvalidResponseException::message('request body is not valid JSON.', $exception);
            }
        }

        $response = $this->transport->request($method, $baseUrl.$path, $body, $headers);
        $payload = $this->decode($response->body);

        if ($response->statusCode < 200 || $response->statusCode >= 300) {
            $errorCode = \is_string($payload['error'] ?? null) ? $payload['error'] : null;
            $message = \is_string($payload['message'] ?? null)
                ? $payload['message']
                : 'Altha returned HTTP '.$response->statusCode.'.';

            throw new ApiException($response->statusCode, $errorCode, $message, $payload);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $content): array
    {
        if ('' === $content) {
            throw InvalidResponseException::message('empty response body.');
        }

        try {
            $payload = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw InvalidResponseException::message('body is not valid JSON.', $exception);
        }

        if (!\is_array($payload)) {
            throw InvalidResponseException::message('JSON root must be an object.');
        }

        /** @var array<string, mixed> $payload */
        return $payload;
    }
}
