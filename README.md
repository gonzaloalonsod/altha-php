# Altha PHP SDK

Cliente PHP tipado para la [Altha Platform API](https://altha.althoapp.com).

Sin dependencias de framework: solo **PHP 8.2+** y **ext-curl**.

- Packagist: [althosalud/altha](https://packagist.org/packages/althosalud/altha)
- Repo: [gonzaloalonsod/altha-php](https://github.com/gonzaloalonsod/altha-php)
- Changelog: [CHANGELOG.md](CHANGELOG.md)

## Instalación

```bash
composer require althosalud/altha
```

## Uso

```php
use AlthoSalud\Altha\AlthaClient;

$client = new AlthaClient(
    baseUrl: 'https://altha.althoapp.com',
    apiKey: 'al_...',
);

$client->health(); // no requiere API key

$me = $client->me();
// $me->slug, $me->products (secretary|directory)

$client->sendSecretaryMessage(
    to: '5491112345678',
    text: 'Tu turno es mañana a las 10:00',
);

// Gupshup: template.id
$client->sendSecretaryMessage(
    to: '5491112345678',
    templateId: 'reminder_v1',
    templateParams: ['Juan', '10:00'],
);

// Meta Cloud API: template.name + template.language
$client->sendSecretaryMessage(
    to: '5491112345678',
    templateName: 'reminder_v1',
    templateLanguage: 'es_AR',
    templateParams: ['Juan', '10:00'],
);

$channel = $client->getSecretaryChannel();
$bot = $client->getSecretaryBot();
$usage = $client->getSecretaryUsage();

$quote = $client->getPricingQuote('secretary.pro');
// $quote->sku, $quote->amountCents

$thread = $client->getIntakeThread('5491112345678');
$client->takeIntakeThread('5491112345678');
$client->sendIntakeMessage('5491112345678', 'Hola, te atiende la farmacia.');
$media = $client->getIntakeMedia(15);
// $media->url expira en $media->expiresInSeconds. El SDK no descarga el archivo.
$client->releaseIntakeThread('5491112345678');
```

Directory (`getDirectoryChannel()`, `getDirectoryStatus()`) responde 403 si el producto no está habilitado y 501 mientras la API siga en stub. Esos estados llegan como `ApiException`.

### Multi-tenant (una key por organización)

```php
$shared = new AlthaClient(baseUrl: getenv('ALTHA_API_BASE_URL') ?: 'https://altha.althoapp.com');
$orgClient = $shared->withApiKey($decryptedOrgKey);
$orgClient->me();
```

`withApiKey()` es inmutable: no muta el cliente compartido.

### Symfony

```dotenv
ALTHA_API_BASE_URL=https://altha.althoapp.com
# Preferí key por organización cifrada en DB; no uses una sola key global en prod.
```

```yaml
services:
    AlthoSalud\Altha\AlthaClient:
        arguments:
            $baseUrl: '%env(ALTHA_API_BASE_URL)%'
            $apiKey: null
```

## API cubierta (v0.2)

| Método SDK | HTTP |
|------------|------|
| `health()` | `GET /api/v1/health` (sin API key) |
| `me()` | `GET /api/v1/me` |
| `getSecretaryChannel()` / `updateSecretaryChannel()` | `GET/PUT /api/v1/secretary/channel` |
| `getSecretaryBot()` / `updateSecretaryBot()` | `GET/PATCH /api/v1/secretary/bot` |
| `getSecretaryUsage()` | `GET /api/v1/secretary/usage` |
| `sendSecretaryMessage()` | `POST /api/v1/secretary/messages` |
| `getIntakeThread()` / `takeIntakeThread()` / `releaseIntakeThread()` / `sendIntakeMessage()` | `GET/POST /api/v1/intake/threads/{phone}` |
| `getIntakeMedia()` | `GET /api/v1/intake/media/{id}` |
| `getDirectoryChannel()` / `getDirectoryStatus()` | `GET /api/v1/directory/channel` y `/status` |
| `getPricingQuote()` | `GET /api/v1/pricing/quote?sku=` |

Host Protocol (`/altha/v1/*`) lo implementa el host. Este paquete no lo llama. El webhook de entitlements y `POST /api/v1/integrations/gosalud/link` usan otros secretos y tampoco están en este cliente.

## Desarrollo

```bash
symfony composer install
symfony composer test
symfony composer check
```

Local API: `https://altha.althoapp.wip`.
