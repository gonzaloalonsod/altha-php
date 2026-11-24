<?php

declare(strict_types=1);

namespace AlthoSalud\Altha\Tests;

use AlthoSalud\Altha\AlthaClient;
use AlthoSalud\Altha\Exception\ApiException;
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

    public function test_health_omits_authorization_when_key_is_empty(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(200, json_encode(['status' => 'ok', 'service' => 'altha'], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new AlthaClient('https://altha.example', transport: $transport);

        $health = $client->health();

        self::assertSame('ok', $health->status);
        self::assertSame('altha', $health->service);
        self::assertSame('https://altha.example/api/v1/health', $transport->requests[0]['url']);
        self::assertArrayNotHasKey('Authorization', $transport->requests[0]['headers']);
    }

    public function test_missing_key_does_not_call_http(): void
    {
        $transport = new MockHttpTransport();
        $client = new AlthaClient('https://altha.example', transport: $transport);

        try {
            $client->me();
            self::fail('Expected a configuration error.');
        } catch (ConfigurationException) {
            self::assertSame([], $transport->requests);
        }
    }

    public function test_positional_session_text_matches_v01_body(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(201, json_encode([
                'status' => 'sent',
                'to' => '5491112345678',
                'message_id' => 7,
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new AlthaClient('https://altha.example', 'al_key', $transport);

        $client->sendSecretaryMessage('5491112345678', 'Hola');

        self::assertSame(
            ['to' => '5491112345678', 'text' => 'Hola'],
            json_decode((string) $transport->requests[0]['body'], true, 512, \JSON_THROW_ON_ERROR),
        );
    }

    public function test_gupshup_template_sends_id_without_name(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(201, json_encode([
                'status' => 'sent',
                'to' => '5491112345678',
                'message_id' => 8,
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new AlthaClient('https://altha.example', 'al_key', $transport);

        $client->sendSecretaryMessage('5491112345678', null, 'reminder_v1', ['Juan', '10:00']);

        self::assertSame(
            ['to' => '5491112345678', 'template' => ['id' => 'reminder_v1', 'params' => ['Juan', '10:00']]],
            json_decode((string) $transport->requests[0]['body'], true, 512, \JSON_THROW_ON_ERROR),
        );
    }

    public function test_meta_template_sends_name_and_language_without_id(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(201, json_encode([
                'status' => 'sent',
                'to' => '5491112345678',
                'message_id' => 9,
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new AlthaClient('https://altha.example', 'al_key', $transport);

        $client->sendSecretaryMessage(
            '5491112345678',
            templateName: 'reminder_v1',
            templateLanguage: 'es_AR',
            templateParams: ['Juan'],
        );

        self::assertSame(
            ['to' => '5491112345678', 'template' => ['name' => 'reminder_v1', 'language' => 'es_AR', 'params' => ['Juan']]],
            json_decode((string) $transport->requests[0]['body'], true, 512, \JSON_THROW_ON_ERROR),
        );
    }

    public function test_intake_thread_read_take_and_release(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(200, json_encode([
                'phone' => '+5491100000000',
                'attendant' => 'bot',
                'intake_case_id' => 'c_1',
                'messages' => [[
                    'id' => 4,
                    'direction' => 'inbound',
                    'body' => '2 ibuprofeno',
                    'media_ids' => [15],
                    'created_at' => '2026-11-24T12:00:00+00:00',
                ]],
            ], \JSON_THROW_ON_ERROR)),
            new HttpResponse(200, json_encode(['status' => 'taken', 'attendant' => 'human'], \JSON_THROW_ON_ERROR)),
            new HttpResponse(200, json_encode(['status' => 'released', 'attendant' => 'bot'], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new AlthaClient('https://altha.example', 'al_key', $transport);

        $thread = $client->getIntakeThread('+5491100000000');
        $taken = $client->takeIntakeThread('+5491100000000');
        $released = $client->releaseIntakeThread('+5491100000000');

        self::assertSame('bot', $thread->attendant);
        self::assertSame('c_1', $thread->intakeCaseId);
        self::assertSame('2 ibuprofeno', $thread->messages[0]->body);
        self::assertSame([15], $thread->messages[0]->mediaIds);
        self::assertSame('human', $taken->attendant);
        self::assertSame('taken', $taken->status);
        self::assertSame('bot', $released->attendant);
        self::assertSame('released', $released->status);
        self::assertSame('GET', $transport->requests[0]['method']);
        self::assertSame('https://altha.example/api/v1/intake/threads/%2B5491100000000', $transport->requests[0]['url']);
        self::assertSame('POST', $transport->requests[1]['method']);
        self::assertSame('https://altha.example/api/v1/intake/threads/%2B5491100000000/take', $transport->requests[1]['url']);
        self::assertNull($transport->requests[1]['body']);
        self::assertSame('https://altha.example/api/v1/intake/threads/%2B5491100000000/release', $transport->requests[2]['url']);
        self::assertNull($transport->requests[2]['body']);
    }

    public function test_non_json_error_keeps_http_status(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(502, '<html>bad gateway</html>'),
            new HttpResponse(403, ''),
        ]);
        $client = new AlthaClient('https://altha.example', 'al_key', $transport);

        try {
            $client->me();
            self::fail('Expected API error 502.');
        } catch (ApiException $exception) {
            self::assertSame(502, $exception->statusCode);
            self::assertNull($exception->apiErrorCode);
        }

        try {
            $client->getSecretaryUsage();
            self::fail('Expected API error 403.');
        } catch (ApiException $exception) {
            self::assertSame(403, $exception->statusCode);
            self::assertNull($exception->apiErrorCode);
        }
    }

    public function test_intake_send_posts_text_and_media_returns_url_without_fetching_it(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(201, json_encode(['status' => 'sent', 'message_id' => 15], \JSON_THROW_ON_ERROR)),
            new HttpResponse(200, json_encode([
                'url' => 'https://s3.example/presigned',
                'mime_type' => 'image/jpeg',
                'expires_in_seconds' => 900,
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new AlthaClient('https://altha.example', 'al_key', $transport);

        $sent = $client->sendIntakeMessage('5491100000000', 'Hola, te atiende la farmacia.');
        $media = $client->getIntakeMedia(15);

        self::assertSame('sent', $sent->status);
        self::assertSame(15, $sent->messageId);
        self::assertSame('POST', $transport->requests[0]['method']);
        self::assertSame('https://altha.example/api/v1/intake/threads/5491100000000/send', $transport->requests[0]['url']);
        self::assertSame(['text' => 'Hola, te atiende la farmacia.'], json_decode((string) $transport->requests[0]['body'], true, 512, \JSON_THROW_ON_ERROR));
        self::assertSame('https://s3.example/presigned', $media->url);
        self::assertSame('image/jpeg', $media->mimeType);
        self::assertSame(900, $media->expiresInSeconds);
        self::assertCount(2, $transport->requests);
        self::assertSame('https://altha.example/api/v1/intake/media/15', $transport->requests[1]['url']);
    }

    public function test_intake_send_conflict_raises_status_409(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(409, json_encode([
                'error' => 'attendant_is_bot',
                'message' => 'Take the thread before sending a message.',
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new AlthaClient('https://altha.example', 'al_key', $transport);

        try {
            $client->sendIntakeMessage('5491100000000', 'no');
            self::fail('Expected API error 409.');
        } catch (ApiException $exception) {
            self::assertSame(409, $exception->statusCode);
            self::assertSame('attendant_is_bot', $exception->apiErrorCode);
        }
    }

    public function test_pricing_quote_sends_sku_query(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(200, json_encode([
                'sku' => 'secretary.pro',
                'name' => 'Secretary Pro',
                'amount_cents' => 1500000,
                'currency' => 'ARS',
                'product' => 'secretary',
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new AlthaClient('https://altha.example', 'al_key', $transport);

        $quote = $client->getPricingQuote('secretary.pro');

        self::assertSame('secretary.pro', $quote->sku);
        self::assertSame(1500000, $quote->amountCents);
        self::assertSame('GET', $transport->requests[0]['method']);
        self::assertSame('https://altha.example/api/v1/pricing/quote?sku=secretary.pro', $transport->requests[0]['url']);
    }

    public function test_directory_not_implemented_raises_status_501(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(501, json_encode([
                'error' => 'not_implemented',
                'message' => 'The directory product API is not implemented yet.',
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new AlthaClient('https://altha.example', 'al_key', $transport);

        try {
            $client->getDirectoryChannel();
            self::fail('Expected API error 501.');
        } catch (ApiException $exception) {
            self::assertSame(501, $exception->statusCode);
            self::assertSame('not_implemented', $exception->apiErrorCode);
        }
    }

    public function test_client_source_excludes_host_and_secret_routes(): void
    {
        $source = file_get_contents(\dirname(__DIR__).'/src/AlthaClient.php');
        self::assertIsString($source);
        self::assertStringNotContainsString('/altha/v1/', $source);
        self::assertStringNotContainsString('/api/v1/webhooks/entitlements', $source);
        self::assertStringNotContainsString('/api/v1/integrations/gosalud/link', $source);
    }
}
