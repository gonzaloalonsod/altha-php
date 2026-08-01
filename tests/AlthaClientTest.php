<?php

declare(strict_types=1);

namespace AlthoSalud\Altha\Tests;

use AlthoSalud\Altha\AlthaClient;
use AlthoSalud\Altha\Exception\ConfigurationException;
use AlthoSalud\Altha\Http\HttpResponse;
use PHPUnit\Framework\TestCase;

final class AlthaClientTest extends TestCase
{
    public function test_me_returns_application_credentials(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(200, json_encode([
                'application' => [
                    'id' => 3,
                    'slug' => 'clinic-demo',
                    'status' => 'active',
                    'products' => ['secretary', 'directory'],
                ],
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new AlthaClient('https://altha.example/', 'al_secret', $transport);

        $credentials = $client->me();

        self::assertSame(3, $credentials->id);
        self::assertSame('clinic-demo', $credentials->slug);
        self::assertSame('active', $credentials->status);
        self::assertSame(['secretary', 'directory'], $credentials->products);
        self::assertSame('https://altha.example/api/v1/me', $transport->requests[0]['url']);
        self::assertSame('Bearer al_secret', $transport->requests[0]['headers']['Authorization']);
    }

    public function test_with_api_key_is_immutable(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(200, json_encode([
                'id' => 1,
                'slug' => 'tenant',
                'status' => 'active',
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $shared = new AlthaClient('https://altha.example', transport: $transport);
        $tenant = $shared->withApiKey('tenant-key');

        self::assertFalse($shared->isConfigured());
        self::assertTrue($tenant->isConfigured());

        $tenant->me();
        self::assertSame('Bearer tenant-key', $transport->requests[0]['headers']['Authorization']);
    }

    public function test_missing_key_throws_configuration_exception(): void
    {
        $client = new AlthaClient('https://altha.example', transport: new MockHttpTransport());

        $this->expectException(ConfigurationException::class);
        $client->me();
    }

    public function test_get_and_update_secretary_channel(): void
    {
        $channelPayload = [
            'channel_configured' => true,
            'connection_status' => 'connected',
            'gupshup_app_id' => 'app-1',
            'gupshup_app_name' => 'Clinic',
            'whatsapp_source' => '549111',
            'gupshup_api_key_mask' => '****abcd',
        ];
        $transport = new MockHttpTransport([
            new HttpResponse(200, json_encode($channelPayload, \JSON_THROW_ON_ERROR)),
            new HttpResponse(200, json_encode($channelPayload, \JSON_THROW_ON_ERROR)),
        ]);
        $client = new AlthaClient('https://altha.example', 'al_key', $transport);

        $status = $client->getSecretaryChannel();
        self::assertTrue($status->channelConfigured);
        self::assertSame('connected', $status->connectionStatus);

        $updated = $client->updateSecretaryChannel([
            'gupshup_api_key' => 'secret',
            'gupshup_app_id' => 'app-1',
        ]);
        self::assertSame('PUT', $transport->requests[1]['method']);
        self::assertSame('https://altha.example/api/v1/secretary/channel', $transport->requests[1]['url']);
        self::assertSame('app-1', $updated->gupshupAppId);
    }

    public function test_get_and_update_secretary_bot(): void
    {
        $botPayload = [
            'locale' => 'es',
            'booking_enabled' => true,
            'reminders_enabled' => false,
            'phone_identity_confirm_enabled' => true,
            'text_overrides' => ['menu.hello' => 'Hola'],
        ];
        $transport = new MockHttpTransport([
            new HttpResponse(200, json_encode($botPayload, \JSON_THROW_ON_ERROR)),
            new HttpResponse(200, json_encode($botPayload, \JSON_THROW_ON_ERROR)),
        ]);
        $client = new AlthaClient('https://altha.example', 'al_key', $transport);

        $bot = $client->getSecretaryBot();
        self::assertSame('es', $bot->locale);
        self::assertTrue($bot->bookingEnabled);

        $client->updateSecretaryBot(['booking_enabled' => false]);
        self::assertSame('PATCH', $transport->requests[1]['method']);
        self::assertStringContainsString('"booking_enabled":false', (string) $transport->requests[1]['body']);
    }

    public function test_get_secretary_usage(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(200, json_encode([
                'period' => ['start' => '2026-08-01', 'end' => '2026-08-31'],
                'messages' => ['inbound' => 10, 'outbound' => 12],
                'appointments' => ['booked' => 3, 'booking_allowed' => true],
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new AlthaClient('https://altha.example', 'al_key', $transport);

        $usage = $client->getSecretaryUsage();
        self::assertSame(10, $usage->messages['inbound']);
        self::assertSame(3, $usage->appointments['booked']);
        self::assertTrue($usage->appointments['booking_allowed']);
    }

    public function test_send_secretary_text_message(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(201, json_encode([
                'status' => 'sent',
                'to' => '5491112345678',
                'message_id' => 42,
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new AlthaClient('https://altha.example', 'al_key', $transport);

        $result = $client->sendSecretaryMessage('5491112345678', text: 'Recordatorio de turno');

        self::assertSame('sent', $result->status);
        self::assertSame(42, $result->messageId);
        self::assertSame('POST', $transport->requests[0]['method']);
        self::assertStringContainsString('Recordatorio de turno', (string) $transport->requests[0]['body']);
    }

    public function test_send_secretary_template_message(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(201, json_encode([
                'status' => 'sent',
                'to' => '5491112345678',
                'message_id' => 'tmpl-1',
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new AlthaClient('https://altha.example', 'al_key', $transport);

        $result = $client->sendSecretaryMessage(
            '5491112345678',
            templateId: 'reminder_v1',
            templateParams: ['Juan', '10:00'],
        );

        self::assertSame('tmpl-1', $result->messageId);
        self::assertStringContainsString('reminder_v1', (string) $transport->requests[0]['body']);
    }

    public function test_send_secretary_message_requires_text_or_template(): void
    {
        $client = new AlthaClient('https://altha.example', 'al_key', new MockHttpTransport());

        $this->expectException(\InvalidArgumentException::class);
        $client->sendSecretaryMessage('549111');
    }
}
