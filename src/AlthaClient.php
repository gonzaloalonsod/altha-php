<?php

declare(strict_types=1);

namespace AlthoSalud\Altha;

use AlthoSalud\Altha\Contract\AlthaClientInterface;
use AlthoSalud\Altha\Contract\HttpTransportInterface;
use AlthoSalud\Altha\Dto\ApplicationCredentials;
use AlthoSalud\Altha\Dto\BotSettings;
use AlthoSalud\Altha\Dto\ChannelStatus;
use AlthoSalud\Altha\Dto\HealthStatus;
use AlthoSalud\Altha\Dto\IntakeAttendantResult;
use AlthoSalud\Altha\Dto\IntakeMediaUrl;
use AlthoSalud\Altha\Dto\IntakeSendResult;
use AlthoSalud\Altha\Dto\IntakeThread;
use AlthoSalud\Altha\Dto\OutboundMessageResult;
use AlthoSalud\Altha\Dto\PricingQuote;
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

    public function health(): HealthStatus
    {
        return HealthStatus::fromArray($this->request('GET', '/api/v1/health', authenticate: false));
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
        ?string $templateName = null,
        ?string $templateLanguage = null,
    ): OutboundMessageResult {
        $to = trim($to);
        if ('' === $to) {
            throw new \InvalidArgumentException('Recipient "to" must not be empty.');
        }

        $body = ['to' => $to];
        $trimmedText = null === $text ? null : trim($text);
        $trimmedTemplateId = null === $templateId ? null : trim($templateId);
        $trimmedTemplateName = null === $templateName ? null : trim($templateName);
        $trimmedLanguage = null === $templateLanguage ? null : trim($templateLanguage);

        if (null !== $trimmedTemplateName && '' !== $trimmedTemplateName) {
            if (null === $trimmedLanguage || '' === $trimmedLanguage) {
                throw new \InvalidArgumentException('Meta Cloud templates require templateLanguage.');
            }
            $body['template'] = [
                'name' => $trimmedTemplateName,
                'language' => $trimmedLanguage,
                'params' => $templateParams,
            ];
        } elseif (null !== $trimmedTemplateId && '' !== $trimmedTemplateId) {
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

    public function getIntakeThread(string $phone): IntakeThread
    {
        return IntakeThread::fromArray($this->request('GET', '/api/v1/intake/threads/'.$this->phoneSegment($phone)));
    }

    public function takeIntakeThread(string $phone): IntakeAttendantResult
    {
        return IntakeAttendantResult::fromArray($this->request('POST', '/api/v1/intake/threads/'.$this->phoneSegment($phone).'/take'));
    }

    public function releaseIntakeThread(string $phone): IntakeAttendantResult
    {
        return IntakeAttendantResult::fromArray($this->request('POST', '/api/v1/intake/threads/'.$this->phoneSegment($phone).'/release'));
    }

    public function sendIntakeMessage(string $phone, string $text): IntakeSendResult
    {
        $text = trim($text);
        if ('' === $text) {
            throw new \InvalidArgumentException('Intake text must not be empty.');
        }

        return IntakeSendResult::fromArray($this->request(
            'POST',
            '/api/v1/intake/threads/'.$this->phoneSegment($phone).'/send',
            ['text' => $text],
        ));
    }

    public function getIntakeMedia(int $id): IntakeMediaUrl
    {
        if ($id < 1) {
            throw new \InvalidArgumentException('Media id must be a positive integer.');
        }

        return IntakeMediaUrl::fromArray($this->request('GET', '/api/v1/intake/media/'.$id));
    }

    public function getDirectoryChannel(): array
    {
        return $this->request('GET', '/api/v1/directory/channel');
    }

    public function getDirectoryStatus(): array
    {
        return $this->request('GET', '/api/v1/directory/status');
    }

    public function getPricingQuote(string $sku): PricingQuote
    {
        $sku = trim($sku);
        if ('' === $sku) {
            throw new \InvalidArgumentException('sku must not be empty.');
        }

        return PricingQuote::fromArray($this->request('GET', '/api/v1/pricing/quote?sku='.rawurlencode($sku)));
    }

    private function phoneSegment(string $phone): string
    {
        $phone = trim($phone);
        if ('' === $phone) {
            throw new \InvalidArgumentException('Phone must not be empty.');
        }

        return rawurlencode($phone);
    }

    /**
     * @param array<string, mixed>|null $jsonBody
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, ?array $jsonBody = null, bool $authenticate = true): array
    {
        $baseUrl = rtrim(trim($this->baseUrl), '/');
        if ('' === $baseUrl) {
            throw ConfigurationException::missingBaseUrl();
        }

        $headers = [
            'Accept' => 'application/json',
        ];

        if ($authenticate) {
            $apiKey = null === $this->apiKey ? '' : trim($this->apiKey);
            if ('' === $apiKey) {
                throw ConfigurationException::missingApiKey();
            }
            $headers['Authorization'] = 'Bearer '.$apiKey;
        }

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

        if ($response->statusCode < 200 || $response->statusCode >= 300) {
            $payload = $this->decodeError($response->body);
            $errorCode = \is_string($payload['error'] ?? null) ? $payload['error'] : null;
            $message = \is_string($payload['message'] ?? null)
                ? $payload['message']
                : 'Altha returned HTTP '.$response->statusCode.'.';

            throw new ApiException($response->statusCode, $errorCode, $message, $payload);
        }

        return $this->decode($response->body);
    }

    /**
     * Error bodies may be empty or HTML. The HTTP status is still reported.
     *
     * @return array<string, mixed>
     */
    private function decodeError(string $content): array
    {
        if ('' === trim($content)) {
            return [];
        }

        try {
            $payload = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        if (!\is_array($payload) || array_is_list($payload)) {
            return [];
        }

        /** @var array<string, mixed> $payload */
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
