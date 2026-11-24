<?php

declare(strict_types=1);

namespace AlthoSalud\Altha\Contract;

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

interface AlthaClientInterface
{
    public function isConfigured(): bool;

    public function withApiKey(string $apiKey): self;

    public function health(): HealthStatus;

    public function me(): ApplicationCredentials;

    public function getSecretaryChannel(): ChannelStatus;

    /**
     * @param array{
     *     gupshup_api_key?: string|null,
     *     gupshup_app_id?: string|null,
     *     gupshup_app_name?: string|null,
     *     whatsapp_source?: string|null
     * } $payload
     */
    public function updateSecretaryChannel(array $payload): ChannelStatus;

    public function getSecretaryBot(): BotSettings;

    /**
     * @param array{
     *     locale?: string,
     *     booking_enabled?: bool,
     *     reminders_enabled?: bool,
     *     phone_identity_confirm_enabled?: bool,
     *     text_overrides?: array<string, string>|null
     * } $payload
     */
    public function updateSecretaryBot(array $payload): BotSettings;

    public function getSecretaryUsage(): UsageReport;

    /**
     * Send a session text or a template. Gupshup uses $templateId. Meta Cloud API uses $templateName and $templateLanguage.
     *
     * @param list<string> $templateParams
     */
    public function sendSecretaryMessage(
        string $to,
        ?string $text = null,
        ?string $templateId = null,
        array $templateParams = [],
        ?string $templateName = null,
        ?string $templateLanguage = null,
    ): OutboundMessageResult;

    public function getIntakeThread(string $phone): IntakeThread;

    public function takeIntakeThread(string $phone): IntakeAttendantResult;

    public function releaseIntakeThread(string $phone): IntakeAttendantResult;

    public function sendIntakeMessage(string $phone, string $text): IntakeSendResult;

    public function getIntakeMedia(int $id): IntakeMediaUrl;

    /**
     * @return array<string, mixed>
     */
    public function getDirectoryChannel(): array;

    /**
     * @return array<string, mixed>
     */
    public function getDirectoryStatus(): array;

    public function getPricingQuote(string $sku): PricingQuote;
}
