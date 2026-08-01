# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2026-08-01

### Added

- Typed `AlthaClient` for the Altha Platform API (`al_` Bearer / multi-tenant `withApiKey()`).
- `me()` → `GET /api/v1/me` (`ApplicationCredentials` + `products[]`).
- Secretary channel: `getSecretaryChannel()` / `updateSecretaryChannel()`.
- Secretary bot settings: `getSecretaryBot()` / `updateSecretaryBot()`.
- Secretary usage: `getSecretaryUsage()`.
- Outbound WhatsApp: `sendSecretaryMessage()` (session text or template).
- DTOs: `ChannelStatus`, `BotSettings`, `UsageReport`, `OutboundMessageResult`.
- cURL transport with injectable `HttpTransportInterface`.
- Exception hierarchy (`ApiException`, `ConfigurationException`, …).
- PHPUnit, PHPStan level 8, PHP CS Fixer, GitHub Actions CI (PHP 8.2–8.4).

[0.1.0]: https://github.com/gonzaloalonsod/altha-php/releases/tag/v0.1.0
