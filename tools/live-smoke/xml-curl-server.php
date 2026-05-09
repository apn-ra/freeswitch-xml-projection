<?php

declare(strict_types=1);

use APNTalk\FreeSwitchXmlProjection\Directory\A1HashCredential;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDocument;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDomain;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryParam;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryUser;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryVariable;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryXmlRenderer;
use APNTalk\FreeSwitchXmlProjection\Enum\DirectoryAction;
use APNTalk\FreeSwitchXmlProjection\Http\XmlCurlRequest;
use APNTalk\FreeSwitchXmlProjection\Http\XmlCurlRequestParser;
use APNTalk\FreeSwitchXmlProjection\Http\XmlCurlResponse;
use APNTalk\FreeSwitchXmlProjection\Security\Redactor;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

/**
 * @return array<string, scalar|null>
 */
function liveSmokeScalarFields(): array
{
    $source = $_POST !== [] ? $_POST : $_GET;
    $fields = [];

    foreach ($source as $key => $value) {
        if (is_scalar($value) || $value === null) {
            $fields[(string) $key] = $value;
        }
    }

    return $fields;
}

function liveSmokeEnv(string $name, string $default): string
{
    $value = getenv($name);

    return $value === false || $value === '' ? $default : $value;
}

function liveSmokeShouldRenderDirectory(XmlCurlRequest $request): bool
{
    if (! $request->isDirectory() || $request->action() !== DirectoryAction::SipAuth) {
        return false;
    }

    $expectedUser = liveSmokeEnv('FREESWITCH_XML_PROJECTION_TEST_USER', '1001');
    $expectedDomain = liveSmokeEnv('FREESWITCH_XML_PROJECTION_TEST_DOMAIN', '127.0.0.1');
    $requestDomain = $request->domain();

    if ($request->user() !== $expectedUser) {
        return false;
    }

    if ($expectedDomain === '*' || $requestDomain === $expectedDomain) {
        return true;
    }

    return $expectedDomain === '127.0.0.1' && $requestDomain !== null;
}

function liveSmokeDirectoryResponse(XmlCurlRequest $request): XmlCurlResponse
{
    $user = $request->user() ?? liveSmokeEnv('FREESWITCH_XML_PROJECTION_TEST_USER', '1001');
    $domain = $request->domain() ?? liveSmokeEnv('FREESWITCH_XML_PROJECTION_TEST_DOMAIN', '127.0.0.1');
    $password = liveSmokeEnv('FREESWITCH_XML_PROJECTION_TEST_PASSWORD', 'capture-password');

    $document = new DirectoryDocument([
        new DirectoryDomain(
            $domain,
            [DirectoryParam::dialStringDefault()],
            [],
            [
                new DirectoryUser(
                    $user,
                    A1HashCredential::fromPlainPassword($user, $domain, $password),
                    [],
                    [
                        new DirectoryVariable('user_context', 'default'),
                        new DirectoryVariable('accountcode', 'apntalk-live-smoke'),
                        new DirectoryVariable('apntalk_tenant_id', 'tenant_live_smoke'),
                        new DirectoryVariable('apntalk_endpoint_id', 'endpoint_live_smoke_1001'),
                        new DirectoryVariable('apntalk_provider_binding_id', 'provider_binding_live_smoke'),
                    ],
                ),
            ],
        ),
    ]);

    return XmlCurlResponse::xml((new DirectoryXmlRenderer())->render($document));
}

$tmpDir = liveSmokeEnv('FREESWITCH_XML_PROJECTION_LIVE_SMOKE_TMP', '/tmp/freeswitch-xml-projection-live-smoke');
if (! is_dir($tmpDir)) {
    mkdir($tmpDir, 0700, true);
}

$fields = liveSmokeScalarFields();
$parser = new XmlCurlRequestParser();

try {
    $request = $parser->parse($fields);
    $response = liveSmokeShouldRenderDirectory($request)
        ? liveSmokeDirectoryResponse($request)
        : XmlCurlResponse::notFound();

    $payload = [
        'metadata' => [
            'captured_at' => gmdate('c'),
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
        ],
        'fields' => $request->redacted(),
        'parsed' => [
            'is_directory' => $request->isDirectory(),
            'action' => $request->action()?->value,
            'profile' => $request->profile(),
            'user' => $request->user(),
            'domain' => $request->domain(),
        ],
        'response' => [
            'status_code' => $response->statusCode,
            'type' => str_contains($response->body, '<section name="directory">') ? 'directory' : 'not-found',
        ],
    ];
} catch (Throwable $exception) {
    $response = XmlCurlResponse::notFound();
    $payload = [
        'metadata' => [
            'captured_at' => gmdate('c'),
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
        ],
        'fields' => (new Redactor())->redact($fields),
        'error' => $exception->getMessage(),
        'response' => [
            'status_code' => $response->statusCode,
            'type' => 'not-found',
        ],
    ];
}

file_put_contents($tmpDir . '/latest.json', json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n");
file_put_contents($tmpDir . '/latest-response.xml', $response->body);

http_response_code($response->statusCode);
foreach ($response->headers as $name => $value) {
    header($name . ': ' . $value);
}

echo $response->body;
