<?php

declare(strict_types=1);

use APNTalk\FreeSwitchXmlProjection\Enum\DirectoryAction;
use APNTalk\FreeSwitchXmlProjection\Http\XmlCurlRequestParser;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

/**
 * @return never
 */
function liveFail(string $message): void
{
    fwrite(STDERR, "[live-smoke] FAIL: " . $message . "\n");
    exit(1);
}

function liveInfo(string $message): void
{
    fwrite(STDOUT, "[live-smoke] " . $message . "\n");
}

function liveEnv(string $name, string $default): string
{
    $value = getenv($name);

    return $value === false || $value === '' ? $default : $value;
}

function liveRun(string $command, ?string $cwd = null, bool $required = true): string
{
    $descriptorSpec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open($command, $descriptorSpec, $pipes, $cwd);

    if (! is_resource($process)) {
        liveFail('Could not start command: ' . $command);
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    $output = trim($stdout . ($stderr === '' ? '' : "\n" . $stderr));

    if ($required && $exitCode !== 0) {
        liveFail("Command failed ({$exitCode}): {$command}\n{$output}");
    }

    return $output;
}

function liveAssert(bool $condition, string $message): void
{
    if (! $condition) {
        liveFail($message);
    }
}

function liveHttpPost(string $url, array $fields): string
{
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($fields),
            'timeout' => 3,
        ],
    ]);
    $body = @file_get_contents($url, false, $context);

    if ($body === false) {
        liveFail('Could not POST to live smoke server at ' . $url);
    }

    return $body;
}

function liveWaitForServer(string $url): void
{
    for ($attempt = 0; $attempt < 30; ++$attempt) {
        $context = stream_context_create(['http' => ['timeout' => 1]]);
        if (@file_get_contents($url, false, $context) !== false) {
            return;
        }

        usleep(100000);
    }

    liveFail('PHP XML curl server did not become reachable at ' . $url);
}

function liveWaitForCapture(string $path): array
{
    for ($attempt = 0; $attempt < 80; ++$attempt) {
        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded)
                && isset($decoded['fields'])
                && is_array($decoded['fields'])
                && ($decoded['fields']['section'] ?? null) === 'directory'
                && ($decoded['fields']['action'] ?? null) === 'sip_auth'
            ) {
                return $decoded;
            }
        }

        usleep(250000);
    }

    liveFail('No real FreeSWITCH directory sip_auth XML curl request was observed.');
}

function liveTryCapture(string $path): ?array
{
    if (! is_file($path)) {
        return null;
    }

    $decoded = json_decode((string) file_get_contents($path), true);

    if (! is_array($decoded)
        || ! isset($decoded['fields'])
        || ! is_array($decoded['fields'])
        || ($decoded['fields']['section'] ?? null) !== 'directory'
        || ($decoded['fields']['action'] ?? null) !== 'sip_auth'
    ) {
        return null;
    }

    return $decoded;
}

function liveFsCli(string $container, string $command, bool $required = false): string
{
    $password = getenv('FREESWITCH_XML_PROJECTION_FS_CLI_PASSWORD');
    $passwordOption = $password === false || $password === '' ? '' : ' -p ' . escapeshellarg($password);

    return liveRun(
        'docker exec ' . escapeshellarg($container) . ' fs_cli' . $passwordOption . ' -x ' . escapeshellarg($command),
        null,
        $required,
    );
}

/**
 * @return array{host: string, port: string}|null
 */
function liveDetectInternalSofiaAddress(string $container): ?array
{
    $status = liveFsCli($container, 'sofia status profile internal', false);

    $port = null;
    if (preg_match('/BIND-URL\s+sips?:mod_sofia@[^:]+:(\d+)/', $status, $matches) === 1) {
        $port = $matches[1];
    } elseif (preg_match('/URL\s+sips?:mod_sofia@[^:]+:(\d+)/', $status, $matches) === 1) {
        $port = $matches[1];
    }

    if ($port === null) {
        return null;
    }

    if (preg_match('/SIP-IP\s+([^\s]+)/', $status, $matches) === 1) {
        return ['host' => $matches[1], 'port' => $port];
    }

    if (preg_match('/BIND-URL\s+sips?:mod_sofia@[^;]+;maddr=([^;\s]+)/', $status, $matches) === 1) {
        return ['host' => $matches[1], 'port' => $port];
    }

    if (preg_match('/URL\s+sips?:mod_sofia@([^:\s]+):\d+/', $status, $matches) === 1) {
        return ['host' => $matches[1], 'port' => $port];
    }

    return null;
}

if (getenv('FREESWITCH_XML_PROJECTION_LIVE_SMOKE') !== '1') {
    liveInfo('skipped; set FREESWITCH_XML_PROJECTION_LIVE_SMOKE=1 to run against Docker FreeSWITCH.');
    exit(0);
}

$root = dirname(__DIR__, 2);
$tmpDir = liveEnv('FREESWITCH_XML_PROJECTION_LIVE_SMOKE_TMP', '/tmp/freeswitch-xml-projection-live-smoke');
$composeFile = liveEnv('FREESWITCH_XML_PROJECTION_DOCKER_COMPOSE', 'docker/docker-compose.yml');
$service = liveEnv('FREESWITCH_XML_PROJECTION_DOCKER_SERVICE', 'lab01');
$container = liveEnv('FREESWITCH_XML_PROJECTION_CONTAINER', 'freeswitch');
$captureHost = liveEnv('FREESWITCH_XML_PROJECTION_CAPTURE_HOST', '127.0.0.1');
$capturePort = liveEnv('FREESWITCH_XML_PROJECTION_CAPTURE_PORT', '18080');
$serverUrl = 'http://' . $captureHost . ':' . $capturePort . '/index.php';
$xmlCurlConfig = $root . '/docker/freeswitch/conf/autoload_configs/xml_curl.conf.xml';
$modulesConfig = $root . '/docker/freeswitch/conf/autoload_configs/modules.conf.xml';
$backupConfig = $tmpDir . '/xml_curl.conf.xml.before';
$serverPid = null;

liveInfo('preflight git status:');
liveInfo(liveRun('git status --short', $root, false) ?: '(clean)');

liveAssert(is_file($root . '/' . $composeFile), 'Docker compose file is missing: ' . $composeFile);
liveAssert(is_file($xmlCurlConfig), 'FreeSWITCH xml_curl config is missing: docker/freeswitch/conf/autoload_configs/xml_curl.conf.xml');
liveAssert(is_file($modulesConfig), 'FreeSWITCH modules config is missing: docker/freeswitch/conf/autoload_configs/modules.conf.xml');

if (! is_dir($tmpDir)) {
    mkdir($tmpDir, 0700, true);
}

copy($xmlCurlConfig, $backupConfig);

$cleanup = static function () use (&$serverPid, $xmlCurlConfig, $backupConfig, $container): void {
    if (is_file($backupConfig)) {
        copy($backupConfig, $xmlCurlConfig);
    }

    liveFsCli($container, 'xml_curl debug_off', false);

    if (is_int($serverPid)) {
        liveRun('kill ' . escapeshellarg((string) $serverPid), null, false);
    }
};
register_shutdown_function($cleanup);

$xmlCurlXml = <<<XML
<configuration name="xml_curl.conf" description="cURL XML Gateway">
  <bindings>
    <binding name="apntalk-live-smoke-directory">
      <param name="gateway-url" value="{$serverUrl}" bindings="directory"/>
      <param name="method" value="POST"/>
      <param name="timeout" value="2"/>
    </binding>
  </bindings>
</configuration>
XML;
file_put_contents($xmlCurlConfig, $xmlCurlXml . "\n");

$serverCommand = escapeshellarg(PHP_BINARY)
    . ' -S ' . escapeshellarg($captureHost . ':' . $capturePort)
    . ' -t ' . escapeshellarg($root . '/tools/live-smoke')
    . ' ' . escapeshellarg($root . '/tools/live-smoke/xml-curl-server.php')
    . ' > ' . escapeshellarg($tmpDir . '/php-server.log')
    . ' 2>&1 & echo $!';
$serverPidOutput = liveRun($serverCommand, $root);
$serverPid = (int) trim($serverPidOutput);
liveAssert($serverPid > 0, 'Could not determine PHP server PID.');
liveWaitForServer($serverUrl);

liveInfo('starting Docker FreeSWITCH service ' . $service);
liveRun('docker compose -f ' . escapeshellarg($root . '/' . $composeFile) . ' up --build -d --force-recreate ' . escapeshellarg($service), $root);
liveInfo(liveRun("docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", $root, false));

for ($attempt = 1; $attempt <= 20; ++$attempt) {
    $version = liveFsCli($container, 'version', false);
    if ($version !== '' && ! str_contains($version, 'Error Connecting')) {
        liveInfo($version);
        break;
    }

    sleep(1);
}

liveInfo(liveFsCli($container, 'version', false) ?: 'fs_cli version check unavailable.');
liveInfo('module_exists mod_xml_curl: ' . (liveFsCli($container, 'module_exists mod_xml_curl', false) ?: 'unavailable'));
liveFsCli($container, 'reloadxml', false);
liveFsCli($container, 'reload mod_xml_curl', false);
liveFsCli($container, 'xml_curl debug_on', false);

if (liveEnv('FREESWITCH_XML_PROJECTION_SIP_HOST', '127.0.0.1') === '127.0.0.1') {
    $sofiaAddress = liveDetectInternalSofiaAddress($container);
    if ($sofiaAddress !== null) {
        putenv('FREESWITCH_XML_PROJECTION_SIP_HOST=' . $sofiaAddress['host']);
        putenv('FREESWITCH_XML_PROJECTION_SIP_PORT=' . $sofiaAddress['port']);
        liveInfo('detected internal Sofia SIP target ' . $sofiaAddress['host'] . ':' . $sofiaAddress['port']);
    }
}

$latestJson = $tmpDir . '/latest.json';
@unlink($latestJson);
$capture = null;

for ($attempt = 1; $attempt <= 10; ++$attempt) {
    liveInfo('sending SIP REGISTER probe attempt ' . $attempt);
    $probeOutput = liveRun(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/tools/live-smoke/send-register.php'), $root, false);
    liveInfo($probeOutput);

    for ($poll = 0; $poll < 8; ++$poll) {
        $capture = liveTryCapture($latestJson);
        if ($capture !== null) {
            break 2;
        }

        usleep(250000);
    }

    sleep(1);
}

if ($capture === null) {
    $capture = liveWaitForCapture($latestJson);
}
$fields = $capture['fields'];
liveAssert(is_array($fields), 'Captured fields were not present.');

$request = (new XmlCurlRequestParser())->parse($fields);
liveAssert($request->isDirectory(), 'Parser did not recognize captured request as directory.');
liveAssert($request->action() === DirectoryAction::SipAuth, 'Parser did not recognize captured request as sip_auth.');
liveAssert($request->profile() !== null, 'Parser did not map the real sip_profile/profile field.');

$responseBody = (string) file_get_contents($tmpDir . '/latest-response.xml');
liveAssert(str_starts_with($responseBody, '<?xml'), 'Directory response did not include an XML declaration.');
liveAssert(str_contains($responseBody, '<document type="freeswitch/xml">'), 'Directory response did not include FreeSWITCH document type.');
liveAssert(str_contains($responseBody, '<section name="directory">'), 'Directory response did not include directory section.');
liveAssert(str_contains($responseBody, '<user id="' . liveEnv('FREESWITCH_XML_PROJECTION_TEST_USER', '1001') . '">'), 'Directory response did not include expected user.');
liveAssert(str_contains($responseBody, 'name="a1-hash"'), 'Directory response did not include a1-hash credential param.');

$notFoundBody = liveHttpPost($serverUrl, [
    'section' => 'directory',
    'action' => 'sip_auth',
    'user' => 'unknown-live-smoke-user',
    'domain' => 'unknown-live-smoke-domain',
]);
liveAssert(str_contains($notFoundBody, '<section name="result">'), 'Not-found response did not include result section.');
liveAssert(str_contains($notFoundBody, '<result status="not found"/>'), 'Not-found response did not include not found status.');

foreach (['sip_auth_response', 'sip_auth_nonce', 'sip_auth_cnonce', 'sip_auth_uri', 'authorization', 'Authorization'] as $key) {
    if (array_key_exists($key, $fields)) {
        liveAssert($fields[$key] === '[redacted]', 'Sensitive captured field was not redacted: ' . $key);
    }
}

$testPassword = liveEnv('FREESWITCH_XML_PROJECTION_TEST_PASSWORD', 'capture-password');
if ($testPassword !== 'capture-password') {
    $trackedSearch = liveRun('git grep -n ' . escapeshellarg($testPassword), $root, false);
    liveAssert($trackedSearch === '', 'Configured live password appears in tracked files.');
}

$cleanup();
$serverPid = null;
liveAssert(liveRun('git diff -- docker/freeswitch/conf/autoload_configs/xml_curl.conf.xml', $root, false) === '', 'Temporary xml_curl config was not restored.');

liveInfo('passed.');
