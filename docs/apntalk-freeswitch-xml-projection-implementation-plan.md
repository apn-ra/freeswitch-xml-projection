# Revised Implementation Plan for `apntalk/freeswitch-xml-projection`

## Executive Summary

`apntalk/freeswitch-xml-projection` should be implemented as a pure, framework-agnostic Composer package that renders FreeSWITCH `mod_xml_curl` directory XML from APNTalk-friendly projection DTOs.

It must not become a Laravel package, database adapter, SIP account authority, provisioning engine, credential store, HTTP authentication layer, FreeSWITCH management service, or Event Socket integration package.

The stable boundary is:

```text
APNTalk canonical SIP endpoint model
        ↓
APNTalk provider binding / credential resolver
        ↓
apntalk/freeswitch-xml-projection
        ↓
FreeSWITCH mod_xml_curl directory XML
        ↓
FreeSWITCH SIP registration / auth
```

Architectural rule:

```text
APNTalk owns authority.
FreeSWITCH owns provider-local runtime behavior.
This package owns only XML projection.
```

The first release target is intentionally narrow:

```text
v0.1.0 = directory sip_auth projection only
```

Reverse auth, message-count, gateways, network-list, dialplan, configuration, phrases, Laravel bridges, HTTP authentication helpers, and provider management are deferred.

---

## 1. Repository Purpose

Use this as the short repository description:

> Standalone PHP package for rendering APNTalk SIP endpoint projections as FreeSWITCH `mod_xml_curl` directory XML.

Use this as the longer README positioning:

> `apntalk/freeswitch-xml-projection` is a framework-agnostic PHP package that converts APNTalk-owned telephony endpoint projections into provider-local FreeSWITCH XML responses for `mod_xml_curl`. It is intentionally not a SIP account authority, credential store, database adapter, Laravel package, HTTP authentication layer, provisioning engine, or FreeSWITCH management service.

The package exists to make this easy and deterministic:

```text
array request fields from mod_xml_curl
        ↓
parsed request metadata
        ↓
APNTalk application decision outside the package
        ↓
package-owned directory projection DTOs
        ↓
deterministic FreeSWITCH XML
```

---

## 2. Initial Scope

### In Scope for v0.1.0

```text
- Parse FreeSWITCH mod_xml_curl request fields from an array.
- Preserve raw scalar request fields.
- Reject array/object request field values.
- Normalize section, purpose, action, user, domain, profile, IP, hostname, sip_auth_username, sip_auth_realm, and user-agent fields.
- Support action/Action alias handling.
- Parse reverse-auth-like requests only enough to identify them and allow APNTalk to return not-found in v0.1.
- Render directory XML for one or more domains.
- Render direct users under <users>.
- Render user params.
- Render user variables.
- Render domain params.
- Support a1-hash credential params.
- Support plaintext password credential params when explicitly requested.
- Render first-class not-found XML.
- Return XML response wrappers suitable for Laravel/Symfony/Slim/native PHP adaptation.
- Provide redaction helpers for request arrays.
- Reject invalid XML control characters before rendering.
- Provide deterministic XML output for fixture tests.
- Document APNTalk integration boundaries.
- Document FreeSWITCH xml_curl configuration.
- Document security posture.
```

### Explicitly Out of Scope for v0.1.0

```text
- No Laravel service provider.
- No Symfony bundle.
- No PSR-7 dependency.
- No database queries.
- No Eloquent models.
- No APNTalk core dependency.
- No FreeSWITCH Event Socket integration.
- No FreeSWITCH management commands.
- No provisioning engine.
- No call routing / dialplan generation.
- No Sofia gateway generation.
- No XML config rendering for sofia.conf.xml.
- No CDR handling.
- No SIP password generation.
- No credential storage.
- No BasicAuthVerifier in core v0.1.
- No reverse-auth response DTO in v0.1.
- No message-count response DTO in v0.1.
- No gateway projection DTOs in v0.1.
- No group rendering in v0.1 unless a real FreeSWITCH auth fixture proves it is required.
```

Future releases can add reverse-auth lookup, cache helpers, directory groups, dialplan, configuration, or phrases, but those should not be included in the first stable slice.

---

## 3. Package Shape

Use namespace:

```php
APNTalk\FreeSwitchXmlProjection
```

Use Composer package name:

```json
"apntalk/freeswitch-xml-projection"
```

Suggested `composer.json`:

```json
{
  "name": "apntalk/freeswitch-xml-projection",
  "description": "Standalone PHP package for rendering APNTalk SIP endpoint projections as FreeSWITCH mod_xml_curl directory XML.",
  "type": "library",
  "license": "proprietary",
  "require": {
    "php": "^8.2",
    "ext-dom": "*",
    "ext-libxml": "*",
    "ext-xmlwriter": "*"
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

Keep `proprietary` while the package is private. Switch to `MIT` only when APNTalk is ready to publish it publicly.

---

## 4. Proposed Directory Structure

Use a small v0.1 structure and avoid publishing speculative surfaces too early.

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
│   ├── public-api.md
│   ├── stability-policy.md
│   ├── release-checklist.md
│   ├── directory-contract.md
│   ├── freeswitch-xml-curl-config.md
│   ├── apntalk-integration.md
│   ├── security.md
│   ├── fixture-provenance.md
│   └── roadmap.md
├── examples/
│   ├── directory-auth-a1-hash.php
│   ├── directory-auth-plain-password.php
│   ├── not-found.php
│   └── laravel-controller-example.php
├── src/
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
│   │   └── XmlCurlResponse.php
│   ├── Directory/
│   │   ├── DirectoryDocument.php
│   │   ├── DirectoryDomain.php
│   │   ├── DirectoryUser.php
│   │   ├── DirectoryParam.php
│   │   ├── DirectoryVariable.php
│   │   ├── DirectoryCredential.php
│   │   ├── PlainPasswordCredential.php
│   │   ├── A1HashCredential.php
│   │   └── DirectoryXmlRenderer.php
│   ├── Result/
│   │   └── ResultXmlRenderer.php
│   ├── Security/
│   │   ├── Redactor.php
│   │   └── SensitiveFieldList.php
│   └── Internal/
│       └── XmlValueValidator.php
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

Do not expose `Contract/`, `HeaderBag`, `BasicAuthVerifier`, `ReverseAuthCredential`, `DirectoryGroup`, or public XML factory/value classes in v0.1 unless a real use case forces them.

Important design rule:

```text
Directory DTOs are package-owned projection DTOs.
They are not APNTalk domain models.
APNTalk maps its internal objects into these projection objects.
```

---

## 5. Final Recommended v0.1 Public API Surface

Keep v0.1 small and stable:

```text
APNTalk\FreeSwitchXmlProjection\Http\XmlCurlRequest
APNTalk\FreeSwitchXmlProjection\Http\XmlCurlRequestParser
APNTalk\FreeSwitchXmlProjection\Http\XmlCurlResponse

APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDocument
APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDomain
APNTalk\FreeSwitchXmlProjection\Directory\DirectoryUser
APNTalk\FreeSwitchXmlProjection\Directory\DirectoryParam
APNTalk\FreeSwitchXmlProjection\Directory\DirectoryVariable
APNTalk\FreeSwitchXmlProjection\Directory\DirectoryCredential
APNTalk\FreeSwitchXmlProjection\Directory\PlainPasswordCredential
APNTalk\FreeSwitchXmlProjection\Directory\A1HashCredential
APNTalk\FreeSwitchXmlProjection\Directory\DirectoryXmlRenderer

APNTalk\FreeSwitchXmlProjection\Result\ResultXmlRenderer

APNTalk\FreeSwitchXmlProjection\Security\Redactor
APNTalk\FreeSwitchXmlProjection\Security\SensitiveFieldList

APNTalk\FreeSwitchXmlProjection\Enum\XmlCurlSection
APNTalk\FreeSwitchXmlProjection\Enum\DirectoryAction
APNTalk\FreeSwitchXmlProjection\Enum\DirectoryPurpose
APNTalk\FreeSwitchXmlProjection\Enum\CredentialMode

APNTalk\FreeSwitchXmlProjection\Exception\InvalidProjectionException
APNTalk\FreeSwitchXmlProjection\Exception\InvalidXmlCurlRequestException
APNTalk\FreeSwitchXmlProjection\Exception\XmlRenderingException
```

Everything else should be internal, deferred, or documented as not part of the public contract.

---

## 6. Core Usage Example

The package should make simple directory auth rendering easy:

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
    password: 'resolved-provider-local-secret',
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
    cacheable: 60000,
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

Expected XML shape:

```xml
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<document type="freeswitch/xml">
  <section name="directory">
    <domain name="tenant-123.sip.apntalk.internal">
      <params>
        <param name="dial-string" value="{presence_id=${dialed_user}@${dialed_domain}}${sofia_contact(${dialed_user}@${dialed_domain})}"/>
      </params>
      <users>
        <user id="1001" cacheable="60000">
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

Plaintext password must be available but should not be the default recommendation:

```php
use APNTalk\FreeSwitchXmlProjection\Directory\PlainPasswordCredential;

$credential = new PlainPasswordCredential('resolved-provider-local-secret');
```

---

## 7. Request Parser Design

`mod_xml_curl` sends many request fields. The package should normalize only the fields APNTalk cares about and preserve the rest as raw scalar metadata.

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
        // support action and Action
        // support FreeSWITCH-Hostname
        // reject arrays/objects/resources as invalid request fields
    }
}
```

### Parser Rules

```text
- Unknown scalar fields are accepted and preserved in raw().
- Array/object/resource values are invalid and throw InvalidXmlCurlRequestException.
- Empty strings are normalized to null for known logical fields.
- `section` is needed for routing but missing section should not crash the request object.
- Missing or unknown section should allow APNTalk to return not-found XML.
- Unknown enum values return null from typed accessors.
- Request parsing does not authenticate FreeSWITCH.
- Request parsing does not authorize tenants.
- Request parsing does not resolve credentials.
```

### Alias and Precedence Rules

Lock these now to avoid drift:

```text
- `action` takes precedence over `Action` when both are present.
- `user` takes precedence over `sip_auth_username` for directory lookup.
- `domain` takes precedence over `sip_auth_realm` for directory lookup.
- `FreeSWITCH-Hostname` maps to freeSwitchHostname().
- `sip_user_agent` maps to sipUserAgent().
- `ip` maps to ip(); if absent, parser may expose null and APNTalk may use HTTP edge metadata.
- Unknown aliases should not be invented without a real captured fixture.
```

### Sensitive Fields to Redact

The default sensitive field list should include case-insensitive matching for:

```text
password
vm-password
reverse-auth-pass
sip_auth_response
sip_auth_nonce
sip_auth_cnonce
sip_auth_uri
Authorization
authorization
gateway-credentials
gateway_credentials
```

Redaction should preserve keys and replace values with `[redacted]`.

---

## 8. Response Design

Create a tiny response object that can be adapted to Laravel, Symfony, Slim, native PHP, or PSR-7 later.

```php
final readonly class XmlCurlResponse
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public string $body,
        public int $statusCode = 200,
        public array $headers = ['Content-Type' => 'text/xml; charset=UTF-8'],
    ) {}

    public static function xml(string $body): self;

    public static function notFound(): self;
}
```

The not-found response is first-class:

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

Unknown but well-formed requests should generally produce:

```text
HTTP 200
Content-Type: text/xml; charset=UTF-8
<result status="not found"/>
```

This is the FreeSWITCH-friendly behavior and avoids invalid empty XML responses.

Do not implement `__toString()` on `XmlCurlResponse`, because response bodies can contain credentials.

---

## 9. Directory Model Details

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
     */
    public function __construct(
        public string $name,
        public array $params = [],
        public array $variables = [],
        public array $users = [],
    ) {
        // name required
        // at least one user for v0.1 directory auth documents
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
        // type supports null or pointer
        // cacheable supports null, true, or positive integer milliseconds
    }
}
```

### `DirectoryParam`

```php
final readonly class DirectoryParam
{
    public function __construct(
        public string $name,
        public string|int|float|bool $value,
    ) {}

    public static function dialStringDefault(): self;
}
```

### `DirectoryVariable`

```php
final readonly class DirectoryVariable
{
    public function __construct(
        public string $name,
        public string|int|float|bool $value,
    ) {}
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

### `PlainPasswordCredential`

```php
final readonly class PlainPasswordCredential implements DirectoryCredential
{
    public function __construct(private string $password) {}

    public function toParams(): array
    {
        return [new DirectoryParam('password', $this->password)];
    }

    public function mode(): CredentialMode
    {
        return CredentialMode::PlainPassword;
    }

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['password' => '[redacted]'];
    }
}
```

Do not expose a public `password()` getter unless a real integration requires it.

### `A1HashCredential`

```php
final readonly class A1HashCredential implements DirectoryCredential
{
    public function __construct(private string $hash) {}

    public static function fromPlainPassword(
        string $username,
        string $domain,
        string $password,
    ): self {
        return new self(md5($username . ':' . $domain . ':' . $password));
    }

    public function toParams(): array
    {
        return [new DirectoryParam('a1-hash', $this->hash)];
    }

    public function mode(): CredentialMode
    {
        return CredentialMode::A1Hash;
    }

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['hash' => '[redacted]'];
    }
}
```

Do not implement `__toString()` on credentials.

---

## 10. XML Renderer Design

Use `XMLWriter`, not string concatenation.

```php
final class DirectoryXmlRenderer
{
    public function render(DirectoryDocument $document): string
    {
        $xml = new \XMLWriter();
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
}
```

### Validation Rules Before Writing

```text
- Empty document is invalid.
- Domain name cannot be empty after trim.
- Domain name max length: 255 characters.
- User ID cannot be empty after trim.
- User ID max length: 255 characters.
- Param name cannot be empty after trim.
- Param name max length: 128 characters.
- Variable name cannot be empty after trim.
- Variable name max length: 128 characters.
- Values must not contain invalid XML control characters.
- `cacheable` may be null, true, or a positive integer.
- `cacheable` must not be false in rendered XML; false should behave like null.
- `type` may be null or `pointer`.
- v0.1 directory auth documents require at least one user per rendered domain.
```

### Rendering Rules

```text
- Always write XML declaration.
- Always write <document type="freeswitch/xml">.
- Always write <section name="directory">.
- Deterministic order: domain params, domain variables, users.
- Preserve caller-supplied param order.
- Preserve caller-supplied variable order.
- Render credential params before extra user params.
- Do not include comments.
- Do not log XML.
- Do not expose streaming/file output in v0.1.
```

### XML Size Posture

The renderer does not enforce transport limits in v0.1, but tests and docs should make size visible.

```text
- Fixture tests should assert typical auth XML is comfortably below 262144 bytes.
- Docs should recommend FreeSWITCH response-max-bytes.
- APNTalk should monitor response size and latency at the HTTP edge.
```

---

## 11. APNTalk Integration Pattern

The package exposes projection primitives only. APNTalk owns all business decisions.

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
use APNTalk\FreeSwitchXmlProjection\Exception\InvalidXmlCurlRequestException;
use APNTalk\FreeSwitchXmlProjection\Http\XmlCurlRequestParser;
use APNTalk\FreeSwitchXmlProjection\Http\XmlCurlResponse;

final class FreeSwitchXmlCurlController
{
    public function __invoke(Request $request): Response
    {
        try {
            $xmlCurl = (new XmlCurlRequestParser())->parse($request->all());
        } catch (InvalidXmlCurlRequestException) {
            return $this->toLaravelResponse(XmlCurlResponse::notFound());
        }

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

        // APNTalk application service, not this package.
        $endpoint = $this->endpointAuthority->findActiveSipEndpointForFreeSwitch(
            domain: $domain,
            username: $user,
            sourceIp: $xmlCurl->ip(),
        );

        if ($endpoint === null) {
            return $this->toLaravelResponse(XmlCurlResponse::notFound());
        }

        // APNTalk credential resolver, not this package.
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

Correct separation:

```text
Package responsibility:
- Parse request fields.
- Normalize known request metadata.
- Redact request arrays.
- Render directory XML.
- Render not-found XML.
- Provide tiny XML response wrapper.

APNTalk responsibility:
- Authenticate the FreeSWITCH HTTP caller.
- Authorize the FreeSWITCH gateway.
- Resolve tenant/provider binding.
- Resolve canonical endpoint.
- Resolve provider-local credential.
- Decide whether endpoint is active.
- Enforce source IP policy.
- Audit access.
- Track latency, errors, and request rates.
```

---

## 12. `mod_xml_curl` Handling Matrix for v0.1

APNTalk should implement this routing behavior using package helpers:

| Request | Package can parse? | APNTalk should serve in v0.1? | Response |
|---|---:|---:|---|
| `section=directory`, `action=sip_auth`, valid user/domain | Yes | Yes | Directory user XML |
| `section=directory`, blank `purpose`, valid auth-like lookup | Yes | Maybe | Directory user XML only if APNTalk confirms it is equivalent to sip_auth |
| `section=directory`, `action=message-count` | Yes | No | Not found |
| `section=directory`, `Action=reverse-auth-lookup` | Yes | No | Not found |
| `section=directory`, `purpose=gateways` | Yes | No | Not found |
| `section=directory`, `purpose=network-list` | Yes | No | Not found |
| `section=dialplan` | Yes | No | Not found |
| `section=configuration` | Yes | No | Not found |
| Missing `section` | Yes | No | Not found |
| Unknown scalar fields | Yes | No effect | Continue routing by known fields |
| Malformed non-scalar fields | Parser rejects | No | Recommended production response: not found |

Default for unknown but well-formed requests:

```text
HTTP 200 + not-found XML
```

---

## 13. FreeSWITCH Config Example for Docs

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

Document operational guidance:

```text
- Use HTTPS.
- Prefer private network routing between FreeSWITCH and APNTalk.
- Use Basic Auth, mTLS, IP allowlist, or a combination at the APNTalk edge.
- Keep timeout low and monitor latency.
- Set response-max-bytes.
- Use `xml_curl debug_on` only on test FreeSWITCH instances.
- Never enable XML debug logging against live secrets unless the environment is controlled and redacted.
```

---

## 14. Security Plan

Security should be part of package design, but enforcement belongs mostly in APNTalk’s HTTP edge.

### Package-Level Security Features

Implement:

```text
- Redactor for request arrays.
- Sensitive field list.
- Case-insensitive sensitive key matching.
- Validation for invalid XML control characters.
- Deterministic exceptions.
- No logging inside renderer.
- No DB calls.
- No HTTP calls.
- No filesystem writes.
- No credential storage.
- No Basic Auth verifier in v0.1 core.
- No accidental __toString() on credentials, request objects, response objects, or directory documents.
- Credential classes redact debug output.
```

Credential classes should avoid exposing secrets:

```php
final readonly class PlainPasswordCredential implements DirectoryCredential
{
    public function __construct(private string $password) {}

    public function toParams(): array
    {
        return [new DirectoryParam('password', $this->password)];
    }

    /**
     * @return array<string, string>
     */
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
- Track request latency, error rate, and not-found rate.
- Rate-limit or isolate the route at the HTTP edge.
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

The package must never make those decisions.

---

## 15. Testing Plan

### Unit Tests

Create tests for:

```text
XmlCurlRequestParserTest
- parses section=directory
- parses action=sip_auth
- parses Action=reverse-auth-lookup but does not imply v0.1 support
- parses purpose=gateways
- normalizes empty strings to null for known fields
- preserves unknown scalar fields
- rejects array request values
- rejects object request values
- preserves raw fields
- applies action over Action precedence
- applies user over sip_auth_username precedence
- applies domain over sip_auth_realm precedence
- maps FreeSWITCH-Hostname
- redacts sensitive fields

XmlCurlRequestTest
- isDirectory true for directory section
- isDirectory false for dialplan/configuration/unknown section
- unknown enum values return null
- raw returns preserved scalar fields
- redacted returns redacted scalar fields

DirectoryParamTest
- rejects empty param name
- rejects too-long param name
- rejects invalid XML characters
- creates default dial-string param

DirectoryVariableTest
- rejects empty variable name
- rejects too-long variable name
- rejects invalid XML characters

DirectoryUserTest
- rejects empty user id
- rejects too-long user id
- accepts cidr
- accepts cacheable true
- accepts cacheable positive integer
- treats cacheable false as null/no attribute or rejects it consistently
- rejects invalid cacheable integer
- accepts type pointer
- rejects unknown type

A1HashCredentialTest
- computes md5(username:domain:password)
- renders a1-hash param
- redacts debug info
- does not expose __toString

PlainPasswordCredentialTest
- renders password param
- redacts debug info
- does not expose __toString

DirectoryXmlRendererTest
- renders basic user
- renders user with params
- renders user with variables
- renders domain params
- escapes XML special characters
- rejects invalid XML control characters
- renders credential params before extra params
- preserves caller param order
- preserves caller variable order
- output is deterministic
- output size for fixture is below documented response-max-bytes

ResultXmlRendererTest
- renders not-found document
- uses freeswitch/xml document type
- uses section=result
- uses result status=not found
- output is deterministic

XmlCurlResponseTest
- XML response uses status 200
- XML response uses text/xml charset UTF-8 content type
- notFound returns status 200
- notFound body is valid FreeSWITCH result XML
```

### Fixture Tests

Use fixtures for the real contract:

```text
tests/Fixture/Requests/real-directory-sip-auth-redacted.php
tests/Fixture/Requests/directory-sip-auth-minimal.php
tests/Fixture/Requests/directory-gateways.php
tests/Fixture/Requests/reverse-auth-lookup.php

tests/Fixture/Responses/directory-sip-auth-a1-hash.xml
tests/Fixture/Responses/directory-sip-auth-plain-password.xml
tests/Fixture/Responses/not-found.xml
```

Do not include reverse-auth response XML in v0.1 unless reverse-auth is intentionally moved into scope.

Fixture test style:

```php
public function test_it_renders_expected_directory_auth_xml(): void
{
    $actual = $this->renderer->render($this->makeDocument());

    self::assertXmlStringEqualsXmlFile(
        __DIR__ . '/../Fixture/Responses/directory-sip-auth-a1-hash.xml',
        $actual,
    );
}
```

### Real Fixture Provenance

`docs/fixture-provenance.md` should document:

```text
- How the real FreeSWITCH mod_xml_curl request fixture was captured.
- Which fields were redacted.
- Which fields were preserved because they are needed for parser compatibility.
- Why no live secrets are committed.
- Which FreeSWITCH/mod_xml_curl version or environment produced the fixture, if known.
```

Acceptance rule:

```text
At least one parser fixture must be derived from a real redacted FreeSWITCH mod_xml_curl sip_auth request.
```

### Optional Integration Harness

Do not require FreeSWITCH in normal CI. Add optional integration harness later:

```text
tests/Integration/
docker/
  freeswitch/
    xml_curl.conf.xml
    vars.xml
```

Manual validation goals:

```text
- FreeSWITCH can request the APNTalk test endpoint.
- Package-generated XML is accepted by FreeSWITCH.
- Unknown user returns not-found XML.
- Valid user receives a1-hash response.
- XML remains under configured response size.
```

---

## 16. CI Workflow

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
          extensions: dom, libxml, xmlwriter
          coverage: none
          tools: composer:v2

      - run: composer validate --strict
      - run: composer install --prefer-dist --no-progress
      - run: composer check
```

Keep CI simple. No FreeSWITCH service containers in the main workflow.

---

## 17. Documentation Plan

### `README.md`

Recommended sections:

```text
# APNTalk FreeSWITCH XML Projection

- What it is
- What it is not
- Installation
- Basic directory user example
- a1-hash example
- Plaintext password example
- Not-found response example
- Request parser example
- APNTalk integration boundary
- Security notes
- Testing
- Versioning
```

### `docs/public-api.md`

Include:

```text
- Final v0.1 public classes
- What is internal
- What is deferred
- No Laravel/APNTalk dependencies
- No framework contracts
```

### `docs/stability-policy.md`

Include:

```text
- Pre-1.0 compatibility expectations
- Public API definition
- Fixture contract expectations
- Breaking-change policy after v1.0
```

### `docs/release-checklist.md`

Include:

```text
- composer validate --strict
- composer check
- PHPUnit
- PHPStan max
- fixture review
- docs/public-api.md review
- CHANGELOG review
- real fixture provenance review
```

### `docs/directory-contract.md`

Include:

```text
- Supported section: directory
- v0.1 supported action: sip_auth
- Parsed-but-deferred actions: reverse-auth-lookup, message-count
- Parsed-but-deferred purposes: gateways, network-list
- Directory XML structure
- User params
- User variables
- Credentials
- Not-found response
- Caching / cacheable behavior
```

### `docs/freeswitch-xml-curl-config.md`

Include:

```text
- Minimal xml_curl.conf.xml binding
- HTTPS example
- Basic Auth example
- mTLS note
- timeout note
- response-max-bytes note
- enable-post-var note if needed by the chosen FreeSWITCH config
- debugging with xml_curl debug_on
- warning against live credential XML logging
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
- Why this package does not authenticate the HTTP caller
```

### `docs/security.md`

Include:

```text
- Threat model
- Secret redaction
- Transport security
- FreeSWITCH gateway authentication belongs at APNTalk edge
- Tenant mismatch handling
- Replay/logging concerns
- a1-hash recommendation
- Why plaintext password support exists but is not preferred
```

### `docs/fixture-provenance.md`

Include:

```text
- Fixture source
- Redaction rules
- Captured FreeSWITCH/mod_xml_curl request shape
- Expected parser fields
- No committed live secrets
```

### `docs/roadmap.md`

Include:

```text
v0.1 - Directory sip_auth renderer
v0.2 - Reverse-auth lookup DTOs and fixtures, if needed
v0.3 - Cache helpers and cache flush docs
v0.4 - Directory group rendering, if required by production FreeSWITCH use
v0.5 - Optional bridge examples for Laravel/Symfony, still no framework dependency in core
v0.6 - Gateway projection experiments, if APNTalk needs them
v1.0 - Stable directory contract after production APNTalk FreeSWITCH validation
```

---

## 18. Versioning Plan

Start with pre-1.0 releases:

```text
v0.1.0
- Request parser
- Directory DTOs
- Directory XML renderer
- Not-found XML response
- A1 hash credential
- Plain password credential
- Redaction helpers
- Real redacted sip_auth request fixture
- Basic docs and fixtures

v0.2.0
- Reverse-auth lookup DTOs and renderer support, if production needs it
- More parser fixtures
- Explicit reverse-auth docs

v0.3.0
- Cacheable helpers
- XML cache docs
- FreeSWITCH cache flush workflow docs

v0.4.0
- Directory group rendering, if production needs it
- Group fixtures from real FreeSWITCH behavior

v0.5.0
- Optional bridge examples for Laravel/Symfony
- No framework dependency in core

v1.0.0
- Stable public API
- Stable XML fixture contract
- Production integration tested with APNTalk FreeSWITCH provider
```

Use SemVer strictly after v1.0.

---

## 19. Acceptance Criteria for v0.1.0

The first release is done when all of this is true:

```text
- `composer require apntalk/freeswitch-xml-projection` works from a private Composer source.
- Package has no Laravel dependency.
- Package has no Symfony dependency.
- Package has no APNTalk core dependency.
- Package has no DB, HTTP client, filesystem-write, ESL, or provisioning behavior.
- Public API is documented in docs/public-api.md.
- Stability policy is documented in docs/stability-policy.md.
- At least one parser fixture is derived from a real redacted FreeSWITCH mod_xml_curl sip_auth request.
- Parser accepts unknown scalar fields.
- Parser rejects non-scalar field values.
- Parser normalizes section, purpose, action, Action, user, domain, profile, IP, hostname, sip_auth_username, sip_auth_realm, and user-agent fields.
- Parser precedence rules are tested.
- Package can render a valid directory XML response with a1-hash.
- Package can render a valid directory XML response with plaintext password.
- Package can render valid not-found XML with HTTP 200.
- Redactor covers password, vm-password, reverse-auth-pass, sip_auth_response, sip_auth_nonce, sip_auth_cnonce, sip_auth_uri, Authorization, and gateway-credentials.
- Credential classes redact debug output.
- Credential, request, response, and document classes do not implement __toString().
- XML renderer rejects invalid XML control characters.
- XML output is deterministic and fixture-tested.
- Fixture response XML is under documented response-max-bytes guidance.
- PHPStan passes at max level.
- PHPUnit passes.
- Composer validate passes.
- README includes quick-start, not-found, request parser, and APNTalk boundary examples.
- Docs include FreeSWITCH config example.
- APNTalk can call the package from a controller without adapter glue inside the package.
```

---

## 20. Suggested Issue Backlog

Create these GitHub issues immediately:

```text
1. Bootstrap Composer package
2. Add CI workflow
3. Add docs/public-api.md, docs/stability-policy.md, and docs/release-checklist.md
4. Add result/not-found XML renderer
5. Add XmlCurlResponse
6. Add SensitiveFieldList and Redactor
7. Add XmlCurlRequest and XmlCurlRequestParser
8. Add parser aliases and precedence tests
9. Add directory enums
10. Add DirectoryParam and DirectoryVariable
11. Add DirectoryCredential interface
12. Add A1HashCredential and PlainPasswordCredential
13. Add DirectoryUser, DirectoryDomain, DirectoryDocument
14. Add DirectoryXmlRenderer
15. Add XML validation for names, values, cacheable, and type
16. Add deterministic XML fixture tests
17. Add real redacted FreeSWITCH sip_auth request fixture
18. Add docs/fixture-provenance.md
19. Add README quick start
20. Add FreeSWITCH xml_curl config docs
21. Add APNTalk integration docs
22. Add security docs
23. Run full release gate
24. Tag v0.1.0
```

Do not create v0.1 issues for reverse-auth renderer, groups, gateways, BasicAuthVerifier, Laravel service provider, dialplan, configuration, or Event Socket behavior.

---

## 21. Design Decisions to Lock Now

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
Content-Type: text/xml; charset=UTF-8
<result status="not found"/>
```

### Decision 4: Default to `a1-hash`

Use plaintext `password` only when explicitly requested by the APNTalk integration.

### Decision 5: Render Minimal XML

For SIP auth, return only the relevant user/domain, not the whole tenant directory.

### Decision 6: No Logging Inside the Renderer

The caller may log redacted request metadata, but the package must not log secrets or XML bodies.

### Decision 7: No HTTP Authentication in v0.1 Core

Basic Auth, mTLS, IP allowlists, rate limits, and audit logging belong at the APNTalk HTTP edge.

### Decision 8: Parser Knows More Than Renderer Supports

The parser may identify reverse-auth/message-count/gateway-like requests, but v0.1 must route unsupported requests to not-found.

### Decision 9: Real Fixture Before Release

Do not tag v0.1.0 without at least one redacted real FreeSWITCH `sip_auth` request fixture.

---

## Final Recommendation

Ship v0.1.0 only after a real APNTalk controller can use the package to answer a FreeSWITCH `sip_auth` request with:

```text
- fixture-tested a1-hash directory response
- fixture-tested plaintext-password directory response
- fixture-tested HTTP 200 not-found response
- parser behavior proven against a real redacted FreeSWITCH request fixture
- no Laravel dependency inside the package
- no APNTalk domain dependency inside the package
- no database, credential authority, HTTP authentication, provisioning, or FreeSWITCH management behavior inside the package
```

Treat `apntalk/freeswitch-xml-projection` as a provider-local XML projection library, not a new authority layer.

