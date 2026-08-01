<?php

declare(strict_types=1);

namespace AlthoSalud\Altha\Contract;

use AlthoSalud\Altha\Dto\ApplicationCredentials;
use AlthoSalud\Altha\Dto\BotSettings;
use AlthoSalud\Altha\Dto\ChannelStatus;
use AlthoSalud\Altha\Dto\OutboundMessageResult;
use AlthoSalud\Altha\Dto\UsageReport;

interface AlthaClientInterface
{
    public function isConfigured(): bool;

    public function withApiKey(string $apiKey): self;

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
     * Send a session text message. Provide either $text or $templateId (+ optional $templateParams).
     *
     * @param list<string> $templateParams
     */
    public function sendSecretaryMessage(
        string $to,
        ?string $text = null,
        ?string $templateId = null,
        array $templateParams = [],
    ): OutboundMessageResult;
}
