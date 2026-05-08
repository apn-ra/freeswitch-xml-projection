# Implementation Plan for `apntalk/freeswitch-xml-projection`

## Executive Summary

`apntalk/freeswitch-xml-projection` should be implemented as a pure, framework-agnostic Composer package that renders FreeSWITCH `mod_xml_curl` XML from APNTalk-friendly projection DTOs.

It should **not** be a Laravel package, database adapter, SIP account authority, provisioning engine, credential store, or FreeSWITCH management service.

The package boundary should be:

```text
APNTalk canonical SIP account model
        ↓
APNTalk provider binding / credential resolver
        ↓
apntalk/freeswitch-xml-projection
        ↓
FreeSWITCH mod_xml_curl directory XML
        ↓
FreeSWITCH SIP registration / auth
```

The first stable target should be **directory rendering only**, because that is the relevant `mod_xml_curl` section for SIP user authentication and endpoint lookup.

---

## 1. Repository Purpose

Use this as the repo description:

> Standalone PHP package for rendering APNTalk SIP endpoint projections as FreeSWITCH `mod_xml_curl` XML.

Use this as the longer README positioning:

> `apntalk/freeswitch-xml-projection` is a framework-agnostic PHP package that converts canonical APNTalk telephony endpoint projections into provider-local FreeSWITCH XML responses for `mod_xml_curl`. It is intentionally not a SIP account authority, credential store, database adapter, or FreeSWITCH management service.

The architectural rule:

```text
APNTalk owns authority.
FreeSWITCH owns provider-local runtime behavior.
This package owns only XML projection.
```

---

## 2. Initial Scope

The first stable release should support **directory rendering only**.

### In Scope for v0.1 / v1

```text
- Parse FreeSWITCH mod_xml_curl request fields from an array.
- Detect section, purpose, action, user, domain, profile, IP, hostname.
- Render directory XML for one or more domains.
- Render one or more users.
- Render params.
- Render variables.
- Render optional groups.
- Render “not found” XML response.
- Support plaintext password param.
- Support a1-hash param.
- Support deterministic XML output for fixture testing.
- Provide security-conscious redaction helpers for logs.
```

### Out of Scope for v0.1

```text
- No Laravel service provider.
- No database queries.
- No Eloquent models.
- No APNTalk core dependency.
- No FreeSWITCH Event Socket integration.
- No provisioning engine.
- No call routing / dialplan generation.
- No Sofia gateway generation.
- No XML config rendering for sofia.conf.xml.
- No CDR handling.
- No SIP password generation.
- No credential storage.
```

Future releases can add `dialplan`, `configuration`, or `phrases`, but doing that too early will make the package too broad.

---

## 3. Recommended Package Shape

Use namespace:

```php
APNTalk\FreeSwitchXmlProjection
```

Use package name:

```json
"apntalk/freeswitch-xml-projection"
```

Suggested `composer.json`:

```json
{
  "name": "apntalk/freeswitch-xml-projection",
  "description": "Standalone PHP package for rendering APNTalk SIP endpoint projections as FreeSWITCH mod_xml_curl XML.",
  "type": "library",
  "license": "proprietary",
  "require": {
    "php": "^8.2",
    "ext-dom": "*",
    "ext-libxml": "*"
  },
  "require-dev": {
    "phpunit/phpunit": "^11.0",
    "phpstan/phpstan": "^2.0",
    "friendsofphp/php-cs-fixer": "^3.0"
  },
  "autoload": {
    "psr-4": {
      "APNTalk\\FreeSwitchXmlProjection\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "APNTalk\\FreeSwitchXmlProjection\\Tests\\": "tests/"
    }
  },
  "scripts": {
    "test": "phpunit",
    "analyse": "phpstan analyse src tests --level=max",
    "cs": "php-cs-fixer fix --dry-run --diff",
    "cs:fix": "php-cs-fixer fix",
    "check": [
      "@test",
      "@analyse",
      "@cs"
    ]
  },
  "minimum-stability": "stable",
  "prefer-stable": true
}
```

Use `proprietary` while it is private. Switch to `MIT` only when APNTalk is ready to publish it publicly.

---

## 4. Proposed Directory Structure

```text
apntalk/freeswitch-xml-projection
├── composer.json
├── README.md
├── LICENSE
├── CHANGELOG.md
├── phpstan.neon
├── phpunit.xml.dist
├── .php-cs-fixer.php
├── .github/
│   └── workflows/
│       └── ci.yml
├── docs/
│   ├── directory-contract.md
│   ├── freeswitch-xml-curl-config.md
│   ├── apntalk-integration.md
│   ├── security.md
│   └── roadmap.md
├── examples/
│   ├── directory-auth.php
│   ├── not-found.php
│   └── laravel-controller-example.php
├── src/
│   ├── Contract/
│   │   ├── XmlRenderable.php
│   │   └── Redactable.php
│   ├── Enum/
│   │   ├── XmlCurlSection.php
│   │   ├── DirectoryAction.php
│   │   ├── DirectoryPurpose.php
│   │   └── CredentialMode.php
│   ├── Exception/
│   │   ├── InvalidProjectionException.php
│   │   ├── InvalidXmlCurlRequestException.php
│   │   └── XmlRenderingException.php
│   ├── Http/
│   │   ├── XmlCurlRequest.php
│   │   ├── XmlCurlRequestParser.php
│   │   ├── XmlCurlResponse.php
│   │   └── HeaderBag.php
│   ├── Directory/
│   │   ├── DirectoryDocument.php
│   │   ├── DirectoryDomain.php
│   │   ├── DirectoryUser.php
│   │   ├── DirectoryGroup.php
│   │   ├── DirectoryParam.php
│   │   ├── DirectoryVariable.php
│   │   ├── DirectoryCredential.php
│   │   ├── PlainPasswordCredential.php
│   │   ├── A1HashCredential.php
│   │   ├── ReverseAuthCredential.php
│   │   └── DirectoryXmlRenderer.php
│   ├── Result/
│   │   ├── NotFoundDocument.php
│   │   └── ResultXmlRenderer.php
│   ├── Security/
│   │   ├── Redactor.php
│   │   ├── SensitiveFieldList.php
│   │   └── BasicAuthVerifier.php
│   └── Xml/
│       ├── XmlEscaper.php
│       ├── XmlWriterFactory.php
│       └── XmlString.php
└── tests/
    ├── Unit/
    │   ├── Http/
    │   ├── Directory/
    │   ├── Result/
    │   └── Security/
    ├── Fixture/
    │   ├── Requests/
    │   └── Responses/
    └── Integration/
        └── DirectoryProjectionTest.php
```

The most important design decision: **`Directory` DTOs should be package-owned DTOs, not APNTalk domain models.**

APNTalk maps its internal objects into these projection objects.

---

## 5. Core Public API

The package should make simple things simple:

```php
use APNTalk\FreeSwitchXmlProjection\Directory\A1HashCredential;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDocument;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDomain;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryParam;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryUser;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryVariable;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryXmlRenderer;

$credential = A1HashCredential::fromPlainPassword(
    username: '1001',
    domain: 'tenant-123.sip.apntalk.internal',
    password: 'resolved-provider-local-secret'
);

$user = new DirectoryUser(
    id: '1001',
    credential: $credential,
    params: [
        new DirectoryParam('vm-password', '1001'),
    ],
    variables: [
        new DirectoryVariable('user_context', 'tenant-123'),
        new DirectoryVariable('effective_caller_id_name', 'Agent 1001'),
        new DirectoryVariable('effective_caller_id_number', '1001'),
        new DirectoryVariable('accountcode', 'tenant-123:1001'),
        new DirectoryVariable('apntalk_tenant_id', 'tenant-123'),
        new DirectoryVariable('apntalk_endpoint_id', 'endpoint-abc'),
        new DirectoryVariable('apntalk_provider_binding_id', 'binding-fs-001'),
    ],
    cacheable: false
);

$domain = new DirectoryDomain(
    name: 'tenant-123.sip.apntalk.internal',
    params: [
        DirectoryParam::dialStringDefault(),
    ],
    users: [$user],
);

$document = new DirectoryDocument(domains: [$domain]);

$xml = (new DirectoryXmlRenderer())->render($document);
```

Expected output shape:

```xml
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<document type="freeswitch/xml">
  <section name="directory">
    <domain name="tenant-123.sip.apntalk.internal">
      <params>
        <param name="dial-string" value="{presence_id=${dialed_user}@${dialed_domain}}${sofia_contact(${dialed_user}@${dialed_domain})}"/>
      </params>
      <users>
        <user id="1001">
          <params>
            <param name="a1-hash" value="..."/>
            <param name="vm-password" value="1001"/>
          </params>
          <variables>
            <variable name="user_context" value="tenant-123"/>
            <variable name="effective_caller_id_name" value="Agent 1001"/>
            <variable name="effective_caller_id_number" value="1001"/>
            <variable name="accountcode" value="tenant-123:1001"/>
            <variable name="apntalk_tenant_id" value="tenant-123"/>
            <variable name="apntalk_endpoint_id" value="endpoint-abc"/>
            <variable name="apntalk_provider_binding_id" value="binding-fs-001"/>
          </variables>
        </user>
      </users>
    </domain>
  </section>
</document>
```

The package should support both direct `<users>` rendering and optional `<groups>` rendering.

---

## 6. Request Parser Design

`mod_xml_curl` sends many request fields. The package should not try to validate every possible field. It should normalize the fields APNTalk cares about.

Implement:

```php
final readonly class XmlCurlRequest
{
    /**
     * @param array<string, scalar|null> $fields
     */
    public function __construct(private array $fields) {}

    public function section(): ?XmlCurlSection;
    public function isDirectory(): bool;

    public function purpose(): ?DirectoryPurpose;
    public function action(): ?DirectoryAction;

    public function user(): ?string;
    public function domain(): ?string;
    public function ip(): ?string;
    public function profile(): ?string;

    public function sipAuthUsername(): ?string;
    public function sipAuthRealm(): ?string;
    public function sipUserAgent(): ?string;
    public function freeSwitchHostname(): ?string;

    /**
     * @return array<string, scalar|null>
     */
    public function raw(): array;

    /**
     * @return array<string, scalar|null>
     */
    public function redacted(): array;
}
```

Parser:

```php
final class XmlCurlRequestParser
{
    /**
     * @param array<string, mixed> $input Usually $_POST, $_GET, or framework request input.
     */
    public function parse(array $input): XmlCurlRequest
    {
        // normalize scalar values
        // normalize empty strings to null where useful
        // preserve original field names in raw copy
        // support both "action" and "Action"
        // support "FreeSWITCH-Hostname"
        // reject arrays/objects as invalid request fields
    }
}
```

Important parser rules:

```text
- Treat `section` as required for routing.
- Treat non-scalar values as invalid.
- Normalize `action` and `Action`.
- Normalize empty string to null for action, purpose, user, domain.
- Keep the raw input available for debugging.
- Redact sensitive fields before logging.
- Do not throw for unknown FreeSWITCH fields.
- Do throw for malformed array/object values.
```

Sensitive fields to redact:

```text
password
vm-password
reverse-auth-pass
sip_auth_response
sip_auth_nonce
sip_auth_cnonce
sip_auth_uri
Authorization
gateway-credentials
```

---

## 7. Response Design

Create a tiny response object that can be adapted to Laravel, Symfony, Slim, native PHP, or PSR-7 later.

```php
final readonly class XmlCurlResponse
{
    public function __construct(
        public string $body,
        public int $statusCode = 200,
        /** @var array<string, string> */
        public array $headers = ['Content-Type' => 'text/xml; charset=UTF-8'],
    ) {}

    public static function xml(string $body): self;

    public static function notFound(): self;
}
```

The “not found” response should be first-class:

```php
$response = XmlCurlResponse::notFound();
```

It should render:

```xml
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<document type="freeswitch/xml">
  <section name="result">
    <result status="not found"/>
  </section>
</document>
```

Unknown but well-formed requests should generally return:

```text
HTTP 200
Content-Type: text/xml
<result status="not found"/>
```

This is the FreeSWITCH-friendly behavior and avoids bad empty XML responses.

---

## 8. Directory Model Details

### `DirectoryDocument`

```php
final readonly class DirectoryDocument
{
    /**
     * @param list<DirectoryDomain> $domains
     */
    public function __construct(
        public array $domains,
        public ?string $description = null,
    ) {
        // require at least one domain
    }
}
```

### `DirectoryDomain`

```php
final readonly class DirectoryDomain
{
    /**
     * @param list<DirectoryParam> $params
     * @param list<DirectoryVariable> $variables
     * @param list<DirectoryUser> $users
     * @param list<DirectoryGroup> $groups
     */
    public function __construct(
        public string $name,
        public array $params = [],
        public array $variables = [],
        public array $users = [],
        public array $groups = [],
    ) {
        // domain name required
        // at least one user or group for auth responses
    }
}
```

### `DirectoryUser`

```php
final readonly class DirectoryUser
{
    /**
     * @param list<DirectoryParam> $params
     * @param list<DirectoryVariable> $variables
     */
    public function __construct(
        public string $id,
        public ?DirectoryCredential $credential = null,
        public array $params = [],
        public array $variables = [],
        public ?string $cidr = null,
        public bool|int|null $cacheable = null,
        public ?string $type = null,
    ) {
        // id required
        // cidr optional
        // type supports pointer
        // cacheable supports true or millisecond integer
    }
}
```

### `DirectoryCredential`

```php
interface DirectoryCredential
{
    /**
     * @return list<DirectoryParam>
     */
    public function toParams(): array;

    public function mode(): CredentialMode;
}
```

Implement:

```php
final readonly class PlainPasswordCredential implements DirectoryCredential
{
    public function __construct(public string $password) {}

    public function toParams(): array
    {
        return [new DirectoryParam('password', $this->password)];
    }
}
```

```php
final readonly class A1HashCredential implements DirectoryCredential
{
    public function __construct(public string $hash) {}

    public static function fromPlainPassword(
        string $username,
        string $domain,
        string $password
    ): self {
        return new self(md5($username . ':' . $domain . ':' . $password));
    }

    public function toParams(): array
    {
        return [new DirectoryParam('a1-hash', $this->hash)];
    }
}
```

```php
final readonly class ReverseAuthCredential implements DirectoryCredential
{
    public function __construct(
        public string $username,
        public string $password,
    ) {}

    public function toParams(): array
    {
        return [
            new DirectoryParam('reverse-auth-user', $this->username),
            new DirectoryParam('reverse-auth-pass', $this->password),
        ];
    }
}
```

---

## 9. XML Renderer Design

Use `XMLWriter`, not string concatenation.

Renderer:

```php
final class DirectoryXmlRenderer
{
    public function render(DirectoryDocument $document): string
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('  ');
        $xml->startDocument('1.0', 'UTF-8', 'no');

        $xml->startElement('document');
        $xml->writeAttribute('type', 'freeswitch/xml');

        $xml->startElement('section');
        $xml->writeAttribute('name', 'directory');

        if ($document->description !== null) {
            $xml->writeAttribute('description', $document->description);
        }

        foreach ($document->domains as $domain) {
            $this->writeDomain($xml, $domain);
        }

        $xml->endElement(); // section
        $xml->endElement(); // document

        $xml->endDocument();

        return $xml->outputMemory();
    }

    private function writeDomain(XMLWriter $xml, DirectoryDomain $domain): void
    {
        // domain
        // params
        // variables
        // users
        // groups
    }
}
```

Validation rules before writing:

```text
- Domain name cannot be empty.
- User ID cannot be empty.
- Param name cannot be empty.
- Variable name cannot be empty.
- Values must not contain invalid XML control characters.
- `cacheable` may be null, bool true, or positive integer.
- `type` may be null or `pointer`.
- Empty document is invalid.
```

Rendering rules:

```text
- Always write XML declaration.
- Always write `<document type="freeswitch/xml">`.
- Always write `<section name="directory">`.
- Use deterministic order: params, variables, users, groups.
- Preserve caller-supplied param and variable order.
- Render credential params before extra params.
- Never include comments.
- Never log XML containing credentials.
```

---

## 10. APNTalk Integration Pattern

The package should expose only projection primitives. APNTalk owns all business decisions.

Example APNTalk-side controller:

```php
use APNTalk\FreeSwitchXmlProjection\Directory\A1HashCredential;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDocument;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDomain;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryParam;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryUser;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryVariable;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryXmlRenderer;
use APNTalk\FreeSwitchXmlProjection\Enum\DirectoryAction;
use APNTalk\FreeSwitchXmlProjection\Http\XmlCurlRequestParser;
use APNTalk\FreeSwitchXmlProjection\Http\XmlCurlResponse;

final class FreeSwitchXmlCurlController
{
    public function __invoke(Request $request): Response
    {
        $xmlCurl = (new XmlCurlRequestParser())->parse($request->all());

        if (! $xmlCurl->isDirectory()) {
            return $this->toLaravelResponse(XmlCurlResponse::notFound());
        }

        if ($xmlCurl->action() !== DirectoryAction::SipAuth) {
            return $this->toLaravelResponse(XmlCurlResponse::notFound());
        }

        $domain = $xmlCurl->domain();
        $user = $xmlCurl->user();

        if ($domain === null || $user === null) {
            return $this->toLaravelResponse(XmlCurlResponse::notFound());
        }

        // APNTalk application service, not this package:
        $endpoint = $this->endpointAuthority->findActiveSipEndpointForFreeSwitch(
            domain: $domain,
            username: $user,
            sourceIp: $xmlCurl->ip(),
        );

        if ($endpoint === null) {
            return $this->toLaravelResponse(XmlCurlResponse::notFound());
        }

        // APNTalk credential resolver, not this package:
        $plainSecret = $this->credentialResolver->resolveProviderSecret($endpoint);

        $credential = A1HashCredential::fromPlainPassword(
            username: $endpoint->sipUsername,
            domain: $endpoint->freeSwitchDomain,
            password: $plainSecret,
        );

        $directoryUser = new DirectoryUser(
            id: $endpoint->sipUsername,
            credential: $credential,
            variables: [
                new DirectoryVariable('user_context', $endpoint->tenantContextName),
                new DirectoryVariable('effective_caller_id_name', $endpoint->callerIdName),
                new DirectoryVariable('effective_caller_id_number', $endpoint->callerIdNumber),
                new DirectoryVariable('accountcode', $endpoint->accountCode),
                new DirectoryVariable('apntalk_tenant_id', $endpoint->tenantId),
                new DirectoryVariable('apntalk_endpoint_id', $endpoint->endpointId),
                new DirectoryVariable('apntalk_provider_binding_id', $endpoint->providerBindingId),
            ],
            cacheable: 60000,
        );

        $document = new DirectoryDocument([
            new DirectoryDomain(
                name: $endpoint->freeSwitchDomain,
                params: [DirectoryParam::dialStringDefault()],
                users: [$directoryUser],
            ),
        ]);

        return $this->toLaravelResponse(
            XmlCurlResponse::xml((new DirectoryXmlRenderer())->render($document))
        );
    }
}
```

That controller demonstrates the correct separation:

```text
Package responsibility:
- parse request
- render XML
- return not found XML

APNTalk responsibility:
- authorize FreeSWITCH gateway
- resolve tenant
- resolve canonical endpoint
- resolve provider credential
- decide whether endpoint is active
- audit access
```

---

## 11. `mod_xml_curl` Handling Matrix

Implement this routing behavior in APNTalk using package helpers:

| Request | Package can parse? | APNTalk should serve? | Response |
|---|---:|---:|---|
| `section=directory`, `action=sip_auth`, valid user/domain | Yes | Yes | Directory user XML |
| `section=directory`, blank `purpose`, valid auth-like lookup | Yes | Maybe | Directory user XML |
| `section=directory`, `action=message-count` | Yes | Later | Not found for v0.1 |
| `section=directory`, `Action=reverse-auth-lookup` | Yes | Later / optional | Reverse auth XML or not found |
| `section=directory`, `purpose=gateways` | Yes | No for v0.1 | Not found |
| `section=directory`, `purpose=network-list` | Yes | No for v0.1 | Not found |
| `section=dialplan` | Yes, as unknown section | No for v0.1 | Not found |
| `section=configuration` | Yes, as unknown section | No for v0.1 | Not found |
| Missing `section` | Yes | No | Not found or invalid request policy |
| Malformed fields | Parser rejects | No | Not found or 400 in APNTalk edge |

Default unknown but well-formed requests to **HTTP 200 + not found XML**.

---

## 12. FreeSWITCH Config Example for Docs

Add this to `docs/freeswitch-xml-curl-config.md`:

```xml
<configuration name="xml_curl.conf" description="cURL XML Gateway">
  <bindings>
    <binding name="apntalk-directory">
      <param name="gateway-url"
             value="https://api.apntalk.internal/freeswitch/xml-curl"
             bindings="directory"/>

      <param name="method" value="POST"/>

      <param name="gateway-credentials" value="freeswitch:CHANGE_ME"/>
      <param name="auth-scheme" value="basic"/>

      <param name="timeout" value="2"/>

      <param name="enable-cacert-check" value="true"/>
      <param name="enable-ssl-verifyhost" value="true"/>

      <param name="response-max-bytes" value="262144"/>
    </binding>
  </bindings>
</configuration>
```

Also document this operational tip:

```text
Use `xml_curl debug_on` on a test FreeSWITCH instance to inspect generated XML during integration.
```

---

## 13. Security Plan

Security should be part of the package design, but enforcement belongs mostly in APNTalk’s HTTP edge.

### Package-Level Security Features

Implement:

```text
- Redactor for request arrays.
- Sensitive field list.
- Value validation for XML-invalid characters.
- No logging inside renderer.
- No DB calls.
- No HTTP calls.
- No filesystem writes.
- Deterministic exceptions.
- No accidental `__toString()` on credential classes.
```

Credential classes should intentionally avoid exposing secrets:

```php
final readonly class PlainPasswordCredential implements DirectoryCredential
{
    public function __construct(private string $password) {}

    public function password(): string
    {
        return $this->password;
    }

    public function __debugInfo(): array
    {
        return ['password' => '[redacted]'];
    }
}
```

### APNTalk-Side Security Requirements

Document these as required integration practices:

```text
- Expose the endpoint only over HTTPS.
- Use Basic Auth, mTLS, IP allowlist, or a combination.
- Prefer private network routing between FreeSWITCH and APNTalk.
- Never log rendered XML for live credentials.
- Redact raw request fields before logging.
- Use a1-hash when possible instead of plaintext SIP password.
- Fail closed with not-found XML.
- Reject tenant/domain mismatches.
- Reject inactive endpoint bindings.
- Track request latency and error rate.
```

### Tenant Safety Requirements

For every auth request, APNTalk should verify:

```text
- FreeSWITCH gateway is trusted.
- Requested domain maps to exactly one tenant/provider binding.
- Requested user belongs to that tenant.
- SIP account is active.
- Provider binding is active.
- Credential is current.
- Source IP policy passes, if configured.
```

The package should never make those decisions.

---

## 14. Testing Plan

### Unit Tests

Create tests for:

```text
XmlCurlRequestParserTest
- parses section=directory
- parses action=sip_auth
- parses Action=reverse-auth-lookup
- parses purpose=gateways
- normalizes empty strings to null
- rejects array/object request values
- preserves raw fields
- redacts sensitive fields

DirectoryUserTest
- rejects empty user id
- accepts cidr
- accepts cacheable true
- accepts cacheable integer
- rejects invalid cacheable integer
- renders pointer user

A1HashCredentialTest
- computes md5(username:domain:password)
- renders a1-hash param
- redacts debug info

PlainPasswordCredentialTest
- renders password param
- redacts debug info

DirectoryXmlRendererTest
- renders basic user
- renders user with params
- renders user with variables
- renders domain params
- renders groups
- escapes XML special characters
- rejects invalid XML characters
- output is deterministic

ResultXmlRendererTest
- renders not found document
- uses freeswitch/xml document type
- uses section=result
- uses result status=not found

XmlCurlResponseTest
- XML response uses status 200
- XML response uses text/xml content type
- notFound returns status 200
```

### Fixture Tests

Use fixtures for the real contract:

```text
tests/Fixture/Requests/directory-sip-auth.php
tests/Fixture/Responses/directory-sip-auth-a1-hash.xml

tests/Fixture/Requests/directory-gateways.php
tests/Fixture/Responses/not-found.xml

tests/Fixture/Requests/reverse-auth-lookup.php
tests/Fixture/Responses/reverse-auth-lookup.xml
```

Fixture test style:

```php
public function test_it_renders_expected_directory_auth_xml(): void
{
    $actual = $this->renderer->render($this->makeDocument());

    self::assertXmlStringEqualsXmlFile(
        __DIR__ . '/../Fixture/Responses/directory-sip-auth-a1-hash.xml',
        $actual
    );
}
```

### Integration Tests

Do not require FreeSWITCH in normal CI. Add optional integration harness:

```text
tests/Integration/
docker/
  freeswitch/
    xml_curl.conf.xml
    vars.xml
```

Run manually:

```bash
composer test
docker compose -f docker-compose.freeswitch.yml up
```

The FreeSWITCH integration test should verify:

```text
- FreeSWITCH can request the APNTalk test endpoint.
- Package returns valid XML.
- Unknown user returns not-found XML.
- Valid user receives a1-hash response.
- XML remains under configured response size.
```

---

## 15. CI Workflow

`.github/workflows/ci.yml`:

```yaml
name: CI

on:
  push:
    branches: [main]
  pull_request:

jobs:
  tests:
    runs-on: ubuntu-latest

    strategy:
      fail-fast: false
      matrix:
        php-version: ['8.2', '8.3', '8.4']

    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
          extensions: dom, libxml
          coverage: none
          tools: composer:v2

      - run: composer validate --strict
      - run: composer install --prefer-dist --no-progress
      - run: composer check
```

Keep CI simple. No service containers in the main workflow.

---

## 16. Documentation Plan

### `README.md`

Recommended sections:

```text
# APNTalk FreeSWITCH XML Projection

- What it is
- What it is not
- Installation
- Basic directory user example
- Not found response example
- Request parser example
- APNTalk integration boundary
- Security notes
- Testing
- Versioning
```

### `docs/directory-contract.md`

Include:

```text
- Supported section: directory
- Supported actions
- Supported purposes
- Directory XML structure
- User params
- User variables
- Credentials
- Not found response
- Caching
```

### `docs/freeswitch-xml-curl-config.md`

Include:

```text
- Minimal xml_curl.conf.xml binding
- HTTPS example
- Basic auth example
- mTLS note
- timeout note
- response-max-bytes note
- enable-post-var note
- debugging with xml_curl debug_on
```

### `docs/apntalk-integration.md`

Include:

```text
- Canonical authority remains APNTalk
- Provider binding lookup
- Tenant/domain mapping
- Credential resolution
- Controller example
- Error behavior
- Observability events
```

### `docs/security.md`

Include:

```text
- Threat model
- Secret redaction
- Transport security
- FreeSWITCH gateway authentication
- Tenant mismatch handling
- Replay/logging concerns
- a1-hash recommendation
```

### `docs/roadmap.md`

Include:

```text
v0.1 - Directory auth renderer
v0.2 - Reverse auth lookup support
v0.3 - Cacheable helpers and cache flush docs
v0.4 - Optional Laravel bridge package
v0.5 - Gateway projection experiments
v1.0 - Stable directory contract
```

---

## 17. Versioning Plan

Start with pre-1.0 releases:

```text
v0.1.0
- Request parser
- Directory DTOs
- Directory XML renderer
- Not found XML response
- A1 hash
- Plain password
- Basic tests and fixtures

v0.2.0
- Reverse auth lookup DTOs
- Message-count response policy hooks
- More parser fixtures

v0.3.0
- Cacheable helpers
- XML cache docs
- FreeSWITCH debug workflow docs

v0.4.0
- Optional bridge examples for Laravel/Symfony
- No framework dependency in core

v1.0.0
- Stable API
- Stable XML fixture contract
- Production integration tested with APNTalk FreeSWITCH provider
```

Use SemVer strictly after v1.0.

---

## 18. Acceptance Criteria for v0.1.0

The first release is done when all of this is true:

```text
- `composer require apntalk/freeswitch-xml-projection` works from a private Composer source.
- Package has no Laravel dependency.
- Package has no APNTalk core dependency.
- Package can parse a real directory `sip_auth` request fixture.
- Package can render a valid directory XML response with a1-hash.
- Package can render a valid directory XML response with plaintext password.
- Package can render valid not-found XML with HTTP 200.
- Package redacts sensitive request fields.
- XML output is deterministic and fixture-tested.
- PHPStan passes at max level.
- PHPUnit passes.
- README includes quick-start example.
- Docs include FreeSWITCH config example.
- APNTalk can call the package from a controller without adapter glue inside the package.
```

---

## 19. Suggested Issue Backlog

Create these GitHub issues immediately:

```text
1. Bootstrap Composer package
2. Add CI workflow
3. Add XML writer infrastructure
4. Add result/not-found XML renderer
5. Add XmlCurlRequest and parser
6. Add directory enums
7. Add DirectoryParam and DirectoryVariable
8. Add DirectoryCredential implementations
9. Add DirectoryUser, DirectoryDomain, DirectoryDocument
10. Add DirectoryXmlRenderer
11. Add XML fixture tests
12. Add sensitive field redactor
13. Add README quick start
14. Add FreeSWITCH xml_curl config docs
15. Add APNTalk integration docs
16. Add Laravel controller example without Laravel dependency
17. Tag v0.1.0
```

---

## 20. Design Decisions to Lock Now

### Decision 1: Pure PHP Core

Do not make the first repo a Laravel package.

Reason:

```text
APNTalk can use Laravel.
The package should not require Laravel.
```

### Decision 2: APNTalk Maps into Projection DTOs

Do not typehint APNTalk domain classes.

Good:

```php
new DirectoryUser(id: $endpoint->sipUsername, ...)
```

Bad:

```php
DirectoryUser::fromSipAccount(SipAccount $account)
```

### Decision 3: Not Found Is Normal Behavior

Unknown section, unknown action, unknown purpose, inactive endpoint, tenant mismatch, or missing user should usually become:

```text
HTTP 200
Content-Type: text/xml
<result status="not found"/>
```

### Decision 4: Default to `a1-hash`

Use plaintext `password` only when explicitly requested.

### Decision 5: Render Minimal XML

For SIP auth, return only the relevant user/domain, not the whole tenant directory.

### Decision 6: No Logging Inside the Renderer

The caller may log redacted request metadata, but the package should not log secrets or XML bodies.

---

## 21. Final Recommended v0.1 API Surface

Keep the public API this small:

```text
APNTalk\FreeSwitchXmlProjection\Http\XmlCurlRequest
APNTalk\FreeSwitchXmlProjection\Http\XmlCurlRequestParser
APNTalk\FreeSwitchXmlProjection\Http\XmlCurlResponse

APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDocument
APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDomain
APNTalk\FreeSwitchXmlProjection\Directory\DirectoryUser
APNTalk\FreeSwitchXmlProjection\Directory\DirectoryGroup
APNTalk\FreeSwitchXmlProjection\Directory\DirectoryParam
APNTalk\FreeSwitchXmlProjection\Directory\DirectoryVariable
APNTalk\FreeSwitchXmlProjection\Directory\DirectoryCredential
APNTalk\FreeSwitchXmlProjection\Directory\PlainPasswordCredential
APNTalk\FreeSwitchXmlProjection\Directory\A1HashCredential
APNTalk\FreeSwitchXmlProjection\Directory\ReverseAuthCredential
APNTalk\FreeSwitchXmlProjection\Directory\DirectoryXmlRenderer

APNTalk\FreeSwitchXmlProjection\Result\NotFoundDocument
APNTalk\FreeSwitchXmlProjection\Result\ResultXmlRenderer

APNTalk\FreeSwitchXmlProjection\Security\Redactor
```

That is enough to be useful without creating a framework or provider platform inside the package.

---

## Final Recommendation

Ship v0.1 only after a real APNTalk controller can use the package to answer a FreeSWITCH `sip_auth` request with:

```text
- a fixture-tested `a1-hash` directory response
- a fixture-tested plaintext-password directory response
- a fixture-tested HTTP 200 not-found response
- no Laravel dependency inside the package
- no APNTalk domain dependency inside the package
- no database or credential authority inside the package
```

The package should be treated as a **provider-local XML projection library**, not as a new authority layer.
