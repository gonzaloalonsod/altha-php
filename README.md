# Altha PHP SDK

Cliente PHP tipado para la [Altha WhatsApp Platform API](https://altha.althoapp.com).

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

$me = $client->me();
// $me->slug, $me->products (secretary|directory)

$client->sendSecretaryMessage(
    to: '5491112345678',
    text: 'Tu turno es mañana a las 10:00',
);

$client->sendSecretaryMessage(
    to: '5491112345678',
    templateId: 'reminder_v1',
    templateParams: ['Juan', '10:00'],
);

$channel = $client->getSecretaryChannel();
$bot = $client->getSecretaryBot();
$usage = $client->getSecretaryUsage();
```

### Multi-tenant (una key por organización)

```php
$shared = new AlthaClient(baseUrl: getenv('ALTHA_API_BASE_URL'));
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

## API cubierta (v0.1)

| Método SDK | HTTP |
|------------|------|
| `me()` | `GET /api/v1/me` |
| `getSecretaryChannel()` / `updateSecretaryChannel()` | `GET/PUT /api/v1/secretary/channel` |
| `getSecretaryBot()` / `updateSecretaryBot()` | `GET/PATCH /api/v1/secretary/bot` |
| `getSecretaryUsage()` | `GET /api/v1/secretary/usage` |
| `sendSecretaryMessage()` | `POST /api/v1/secretary/messages` |

Directory stubs y Host Protocol (AHP) no van en este paquete: AHP lo implementa el host (Gosalud); directory llega en una versión posterior.

## Desarrollo

```bash
symfony composer install
symfony composer test
symfony composer check
```

Local API: `https://altha.althoapp.wip`.
