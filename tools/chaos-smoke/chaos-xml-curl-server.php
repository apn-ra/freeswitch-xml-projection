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
function chaosScalarFields(): array
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

function chaosEnv(string $name, string $default): string
{
    $value = getenv($name);

    return $value === false || $value === '' ? $default : $value;
}

function chaosTmpDir(): string
{
    return chaosEnv('FREESWITCH_XML_PROJECTION_CHAOS_TMP', '/tmp/freeswitch-xml-projection-chaos-smoke');
}

/**
 * @return array{mode: string, name: string}
 */
function chaosScenario(): array
{
    $path = chaosTmpDir() . '/scenario.json';
    if (! is_file($path)) {
        return ['mode' => 'not_found', 'name' => 'unset'];
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    if (! is_array($decoded)) {
        return ['mode' => 'not_found', 'name' => 'invalid'];
    }

    return [
        'mode' => is_string($decoded['mode'] ?? null) ? $decoded['mode'] : 'not_found',
        'name' => is_string($decoded['name'] ?? null) ? $decoded['name'] : 'unnamed',
    ];
}

function chaosShouldRenderDirectory(XmlCurlRequest $request): bool
{
    if (! $request->isDirectory() || $request->action() !== DirectoryAction::SipAuth) {
        return false;
    }

    $expectedUser = chaosEnv('FREESWITCH_XML_PROJECTION_TEST_USER', '1001');
    $expectedDomain = chaosEnv('FREESWITCH_XML_PROJECTION_TEST_DOMAIN', '127.0.0.1');
    $requestDomain = $request->domain();

    if ($request->user() !== $expectedUser) {
        return false;
    }

    if ($expectedDomain === '*' || $requestDomain === $expectedDomain) {
        return true;
    }

    return $expectedDomain === '127.0.0.1' && $requestDomain !== null;
}

function chaosDirectoryResponse(XmlCurlRequest $request): XmlCurlResponse
{
    $user = $request->user() ?? chaosEnv('FREESWITCH_XML_PROJECTION_TEST_USER', '1001');
    $domain = $request->domain() ?? chaosEnv('FREESWITCH_XML_PROJECTION_TEST_DOMAIN', '127.0.0.1');
    $password = chaosEnv('FREESWITCH_XML_PROJECTION_TEST_PASSWORD', 'capture-password');

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
                        new DirectoryVariable('accountcode', 'apntalk-chaos-smoke'),
                        new DirectoryVariable('apntalk_tenant_id', 'tenant_chaos_smoke'),
                        new DirectoryVariable('apntalk_endpoint_id', 'endpoint_chaos_smoke_1001'),
                        new DirectoryVariable('apntalk_provider_binding_id', 'provider_binding_chaos_smoke'),
                    ],
                ),
            ],
        ),
    ]);

    return XmlCurlResponse::xml((new DirectoryXmlRenderer())->render($document));
}

function chaosOversizedXml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . "\n"
        . '<document type="freeswitch/xml"><section name="directory">'
        . '<!-- ' . str_repeat('oversized-chaos-smoke ', 18000) . ' -->'
        . '</section></document>';
}

$tmpDir = chaosTmpDir();
if (! is_dir($tmpDir)) {
    mkdir($tmpDir, 0700, true);
}

$scenario = chaosScenario();
$fields = chaosScalarFields();
$parser = new XmlCurlRequestParser();
$request = null;
$responseType = 'not-found';

try {
    $request = $parser->parse($fields);

    if ($scenario['mode'] === 'success' && chaosShouldRenderDirectory($request)) {
        $response = chaosDirectoryResponse($request);
        $responseType = 'directory';
    } elseif ($scenario['mode'] === 'slow') {
        sleep((int) chaosEnv('FREESWITCH_XML_PROJECTION_CHAOS_SLEEP_SECONDS', '4'));
        $response = XmlCurlResponse::notFound();
        $responseType = 'slow-not-found';
    } elseif ($scenario['mode'] === 'malformed_xml') {
        $response = XmlCurlResponse::xml('<?xml version="1.0" encoding="UTF-8"?><document><broken');
        $responseType = 'malformed_xml';
    } elseif ($scenario['mode'] === 'http_500') {
        $response = new XmlCurlResponse(
            XmlCurlResponse::notFound()->body,
            500,
            ['Content-Type' => 'application/xml; charset=utf-8'],
        );
        $responseType = 'http_500';
    } elseif ($scenario['mode'] === 'oversized') {
        $response = XmlCurlResponse::xml(chaosOversizedXml());
        $responseType = 'oversized';
    } else {
        $response = XmlCurlResponse::notFound();
        $responseType = 'not-found';
    }

    $event = [
        'captured_at' => gmdate('c'),
        'scenario' => $scenario['name'],
        'mode' => $scenario['mode'],
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
            'type' => $responseType,
            'bytes' => strlen($response->body),
        ],
    ];
} catch (Throwable $exception) {
    $response = XmlCurlResponse::notFound();
    $event = [
        'captured_at' => gmdate('c'),
        'scenario' => $scenario['name'],
        'mode' => $scenario['mode'],
        'fields' => (new Redactor())->redact($fields),
        'error' => $exception->getMessage(),
        'response' => [
            'status_code' => $response->statusCode,
            'type' => 'not-found',
            'bytes' => strlen($response->body),
        ],
    ];
}

file_put_contents($tmpDir . '/events.ndjson', json_encode($event, JSON_THROW_ON_ERROR) . "\n", FILE_APPEND | LOCK_EX);
file_put_contents($tmpDir . '/latest-response.xml', $response->body);

http_response_code($response->statusCode);
foreach ($response->headers as $name => $value) {
    header($name . ': ' . $value);
}

echo $response->body;
