## `apntalk/freeswitch-xml-projection`

Framework-agnostic PHP library for parsing FreeSWITCH `mod_xml_curl` request fields and rendering package-owned directory XML projections.

### Boundary

APNTalk owns authority.
FreeSWITCH owns provider-local runtime behavior.
This package owns only XML projection.

`v0.1.0` is intentionally narrow:

- Directory `sip_auth` parsing and projection only.
- Reverse-auth, message-count, gateways, and network-list are parsed only enough for the caller to return not-found XML.
- No Laravel dependency.
- No Symfony dependency.
- No PSR-7 dependency.
- No APNTalk core dependency.
- No database, HTTP client, filesystem writes, or FreeSWITCH ESL behavior.

### Install

```bash
composer require apntalk/freeswitch-xml-projection
```

### Public API

- `APNTalk\FreeSwitchXmlProjection\Http\XmlCurlRequest`
- `APNTalk\FreeSwitchXmlProjection\Http\XmlCurlRequestParser`
- `APNTalk\FreeSwitchXmlProjection\Http\XmlCurlResponse`
- `APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDocument`
- `APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDomain`
- `APNTalk\FreeSwitchXmlProjection\Directory\DirectoryUser`
- `APNTalk\FreeSwitchXmlProjection\Directory\DirectoryParam`
- `APNTalk\FreeSwitchXmlProjection\Directory\DirectoryVariable`
- `APNTalk\FreeSwitchXmlProjection\Directory\DirectoryCredential`
- `APNTalk\FreeSwitchXmlProjection\Directory\PlainPasswordCredential`
- `APNTalk\FreeSwitchXmlProjection\Directory\A1HashCredential`
- `APNTalk\FreeSwitchXmlProjection\Directory\DirectoryXmlRenderer`
- `APNTalk\FreeSwitchXmlProjection\Result\ResultXmlRenderer`
- `APNTalk\FreeSwitchXmlProjection\Security\Redactor`
- `APNTalk\FreeSwitchXmlProjection\Security\SensitiveFieldList`

See [docs/public-api.md](/home/grimange/apn_projects/freeswitch-xml-projection/docs/public-api.md) for the full surface.

### Usage

```php
<?php

declare(strict_types=1);

use APNTalk\FreeSwitchXmlProjection\Directory\A1HashCredential;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDocument;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDomain;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryParam;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryUser;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryXmlRenderer;
use APNTalk\FreeSwitchXmlProjection\Http\XmlCurlRequestParser;

$request = (new XmlCurlRequestParser())->parse($_POST);

if (! $request->isDirectory() || $request->action()?->value !== 'sip_auth') {
    return;
}

$document = new DirectoryDocument([
    new DirectoryDomain(
        'tenant.example.test',
        [DirectoryParam::dialStringDefault()],
        [],
        [
            new DirectoryUser(
                '1001',
                A1HashCredential::fromPlainPassword('1001', 'tenant.example.test', 'secret'),
            ),
        ],
    ),
]);

echo (new DirectoryXmlRenderer())->render($document);
```

### Security

- Prefer `a1-hash` over plaintext password.
- Never log rendered XML containing live credentials.
- Basic auth, mTLS, IP allowlists, rate limits, and audit logging belong at APNTalk's HTTP edge, not in this package.

See [docs/security.md](/home/grimange/apn_projects/freeswitch-xml-projection/docs/security.md).

### Fixture provenance

[tests/Fixture/Requests/real-directory-sip-auth-redacted.php](/home/grimange/apn_projects/freeswitch-xml-projection/tests/Fixture/Requests/real-directory-sip-auth-redacted.php) is based on a real redacted FreeSWITCH Docker `mod_xml_curl` directory `sip_auth` capture from `2026-05-09`.

The capture used the repo-local Docker environment in [docker/docker-compose.yml](/home/grimange/apn_projects/freeswitch-xml-projection/docker/docker-compose.yml), service `lab01`, container `freeswitch`, host networking, and a temporary local capture endpoint outside the repository. See [docs/fixture-provenance.md](/home/grimange/apn_projects/freeswitch-xml-projection/docs/fixture-provenance.md).
