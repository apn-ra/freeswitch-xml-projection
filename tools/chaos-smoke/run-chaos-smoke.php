<?php

declare(strict_types=1);

use APNTalk\FreeSwitchXmlProjection\Enum\DirectoryAction;
use APNTalk\FreeSwitchXmlProjection\Http\XmlCurlRequestParser;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

/**
 * @return never
 */
function chaosFail(string $message): void
{
    fwrite(STDERR, "[chaos-smoke] FAIL: " . $message . "\n");
    exit(1);
}

function chaosInfo(string $message): void
{
    fwrite(STDOUT, "[chaos-smoke] " . $message . "\n");
}

function chaosEnv(string $name, string $default): string
{
    $value = getenv($name);

    return $value === false || $value === '' ? $default : $value;
}

function chaosRun(string $command, ?string $cwd = null, bool $required = true): string
{
    $descriptorSpec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open($command, $descriptorSpec, $pipes, $cwd);

    if (! is_resource($process)) {
        chaosFail('Could not start command: ' . $command);
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    $output = trim($stdout . ($stderr === '' ? '' : "\n" . $stderr));

    if ($required && $exitCode !== 0) {
        chaosFail("Command failed ({$exitCode}): {$command}\n{$output}");
    }

    return $output;
}

function chaosAssert(bool $condition, string $message): void
{
    if (! $condition) {
        chaosFail($message);
    }
}

function chaosFsCli(string $container, string $command, bool $required = false): string
{
    $password = getenv('FREESWITCH_XML_PROJECTION_FS_CLI_PASSWORD');
    $passwordOption = $password === false || $password === '' ? '' : ' -p ' . escapeshellarg($password);

    return chaosRun(
        'docker exec ' . escapeshellarg($container) . ' fs_cli' . $passwordOption . ' -x ' . escapeshellarg($command),
        null,
        $required,
    );
}

/**
 * @return array{host: string, port: string}|null
 */
function chaosDetectInternalSofiaAddress(string $container): ?array
{
    $status = chaosFsCli($container, 'sofia status profile internal', false);

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

function chaosWaitForServer(string $url): void
{
    for ($attempt = 0; $attempt < 30; ++$attempt) {
        $context = stream_context_create(['http' => ['timeout' => 1]]);
        if (@file_get_contents($url, false, $context) !== false) {
            return;
        }

        usleep(100000);
    }

    chaosFail('PHP chaos XML curl server did not become reachable at ' . $url);
}

function chaosPatchXmlCurl(string $path, string $serverUrl): void
{
    $xml = <<<XML
<configuration name="xml_curl.conf" description="cURL XML Gateway">
  <bindings>
    <binding name="apntalk-chaos-smoke-directory">
      <param name="gateway-url" value="{$serverUrl}" bindings="directory"/>
      <param name="method" value="POST"/>
      <param name="timeout" value="2"/>
    </binding>
  </bindings>
</configuration>
XML;
    file_put_contents($path, $xml . "\n");
}

/**
 * @return list<array<string, mixed>>
 */
function chaosReadEvents(string $path): array
{
    if (! is_file($path)) {
        return [];
    }

    $events = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $decoded = json_decode($line, true);
        if (is_array($decoded)) {
            $events[] = $decoded;
        }
    }

    return $events;
}

/**
 * @return array<string, mixed>|null
 */
function chaosWaitForScenarioEvent(string $eventsPath, string $scenario, int $afterCount): ?array
{
    for ($attempt = 0; $attempt < 60; ++$attempt) {
        $events = chaosReadEvents($eventsPath);
        for ($index = $afterCount; $index < count($events); ++$index) {
            if (($events[$index]['scenario'] ?? null) === $scenario) {
                return $events[$index];
            }
        }

        usleep(250000);
    }

    return null;
}

function chaosWriteScenario(string $path, string $name, string $mode): void
{
    file_put_contents($path, json_encode(['name' => $name, 'mode' => $mode], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n");
}

function chaosTriggerRegister(string $root): string
{
    return chaosRun(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/tools/live-smoke/send-register.php'), $root, false);
}

function chaosHttpPost(string $url, array $fields): string
{
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($fields),
            'timeout' => 3,
            'ignore_errors' => true,
        ],
    ]);
    $body = @file_get_contents($url, false, $context);

    if ($body === false) {
        chaosFail('Could not POST to chaos server at ' . $url);
    }

    return $body;
}

function chaosClearTmp(string $tmpDir): void
{
    if (! is_dir($tmpDir)) {
        return;
    }

    foreach (glob($tmpDir . '/*') ?: [] as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }

    @rmdir($tmpDir);
}

function chaosAssertRedacted(array $event): void
{
    $fields = $event['fields'] ?? [];
    chaosAssert(is_array($fields), 'Scenario event did not contain fields.');

    foreach (['sip_auth_response', 'sip_auth_nonce', 'sip_auth_cnonce', 'sip_auth_uri', 'authorization', 'Authorization'] as $key) {
        if (array_key_exists($key, $fields)) {
            chaosAssert($fields[$key] === '[redacted]', 'Sensitive field was not redacted: ' . $key);
        }
    }
}

function chaosAssertTrackedSecretClean(string $root): void
{
    $fsCliPassword = getenv('FREESWITCH_XML_PROJECTION_FS_CLI_PASSWORD');
    if (is_string($fsCliPassword) && $fsCliPassword !== '') {
        chaosAssert(chaosRun('git grep -n ' . escapeshellarg($fsCliPassword), $root, false) === '', 'fs_cli password appears in tracked files.');
    }

    $testPassword = chaosEnv('FREESWITCH_XML_PROJECTION_TEST_PASSWORD', 'capture-password');
    if ($testPassword !== 'capture-password') {
        chaosAssert(chaosRun('git grep -n ' . escapeshellarg($testPassword), $root, false) === '', 'Configured chaos password appears in tracked files.');
    }

    $trackedRuntime = chaosRun("git ls-files '*latest*.json' '*.raw.json' 'latest.raw.json' 'latest.json'", $root, false);
    chaosAssert($trackedRuntime === '', 'Raw or latest capture JSON is tracked.');
}

if (getenv('FREESWITCH_XML_PROJECTION_CHAOS_SMOKE') !== '1') {
    chaosInfo('skipped; set FREESWITCH_XML_PROJECTION_CHAOS_SMOKE=1 to run against Docker FreeSWITCH.');
    exit(0);
}

$root = dirname(__DIR__, 2);
$scenarios = require __DIR__ . '/chaos-scenarios.php';
$tmpDir = chaosEnv('FREESWITCH_XML_PROJECTION_CHAOS_TMP', '/tmp/freeswitch-xml-projection-chaos-smoke');
$composeFile = chaosEnv('FREESWITCH_XML_PROJECTION_DOCKER_COMPOSE', 'docker/docker-compose.yml');
$service = chaosEnv('FREESWITCH_XML_PROJECTION_DOCKER_SERVICE', 'lab01');
$container = chaosEnv('FREESWITCH_XML_PROJECTION_CONTAINER', 'freeswitch');
$captureHost = chaosEnv('FREESWITCH_XML_PROJECTION_CAPTURE_HOST', '127.0.0.1');
$chaosPort = chaosEnv('FREESWITCH_XML_PROJECTION_CHAOS_PORT', '18081');
$serverUrl = 'http://' . $captureHost . ':' . $chaosPort . '/index.php';
$unusedServerUrl = 'http://' . $captureHost . ':' . ((int) $chaosPort + 97) . '/index.php';
$xmlCurlConfig = $root . '/docker/freeswitch/conf/autoload_configs/xml_curl.conf.xml';
$modulesConfig = $root . '/docker/freeswitch/conf/autoload_configs/modules.conf.xml';
$backupConfig = $tmpDir . '/xml_curl.conf.xml.before';
$eventsPath = $tmpDir . '/events.ndjson';
$scenarioPath = $tmpDir . '/scenario.json';
$serverPid = null;
$cleanupRan = false;

chaosInfo('preflight git status:');
chaosInfo(chaosRun('git status --short', $root, false) ?: '(clean)');

chaosAssert(is_file($root . '/' . $composeFile), 'Docker compose file is missing: ' . $composeFile);
chaosAssert(is_file($xmlCurlConfig), 'FreeSWITCH xml_curl config is missing: docker/freeswitch/conf/autoload_configs/xml_curl.conf.xml');
chaosAssert(is_file($modulesConfig), 'FreeSWITCH modules config is missing: docker/freeswitch/conf/autoload_configs/modules.conf.xml');

if (! is_dir($tmpDir)) {
    mkdir($tmpDir, 0700, true);
}

copy($xmlCurlConfig, $backupConfig);

$cleanup = static function () use (&$cleanupRan, &$serverPid, $xmlCurlConfig, $backupConfig, $container, $tmpDir): void {
    if ($cleanupRan) {
        return;
    }
    $cleanupRan = true;

    if (is_file($backupConfig)) {
        copy($backupConfig, $xmlCurlConfig);
    }

    chaosFsCli($container, 'xml_curl debug_off', false);

    if (is_int($serverPid)) {
        chaosRun('kill ' . escapeshellarg((string) $serverPid), null, false);
    }

    chaosClearTmp($tmpDir);
};
register_shutdown_function($cleanup);

chaosWriteScenario($scenarioPath, 'preflight', 'not_found');

$serverCommand = escapeshellarg(PHP_BINARY)
    . ' -S ' . escapeshellarg($captureHost . ':' . $chaosPort)
    . ' -t ' . escapeshellarg($root . '/tools/chaos-smoke')
    . ' ' . escapeshellarg($root . '/tools/chaos-smoke/chaos-xml-curl-server.php')
    . ' > ' . escapeshellarg($tmpDir . '/php-server.log')
    . ' 2>&1 & echo $!';
$serverPidOutput = chaosRun($serverCommand, $root);
$serverPid = (int) trim($serverPidOutput);
chaosAssert($serverPid > 0, 'Could not determine PHP chaos server PID.');
chaosWaitForServer($serverUrl);
@unlink($eventsPath);

chaosPatchXmlCurl($xmlCurlConfig, $serverUrl);

chaosInfo('starting Docker FreeSWITCH service ' . $service);
chaosRun('docker compose -f ' . escapeshellarg($root . '/' . $composeFile) . ' up --build -d --force-recreate ' . escapeshellarg($service), $root);
chaosInfo(chaosRun("docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'", $root, false));

for ($attempt = 1; $attempt <= 20; ++$attempt) {
    $version = chaosFsCli($container, 'version', false);
    if ($version !== '' && ! str_contains($version, 'Error Connecting')) {
        chaosInfo($version);
        break;
    }

    sleep(1);
}

chaosInfo('module_exists mod_xml_curl: ' . (chaosFsCli($container, 'module_exists mod_xml_curl', false) ?: 'unavailable'));
chaosFsCli($container, 'reloadxml', false);
chaosFsCli($container, 'reload mod_xml_curl', false);
chaosFsCli($container, 'xml_curl debug_on', false);

if (chaosEnv('FREESWITCH_XML_PROJECTION_SIP_HOST', '127.0.0.1') === '127.0.0.1') {
    $sofiaAddress = chaosDetectInternalSofiaAddress($container);
    if ($sofiaAddress !== null) {
        putenv('FREESWITCH_XML_PROJECTION_SIP_HOST=' . $sofiaAddress['host']);
        putenv('FREESWITCH_XML_PROJECTION_SIP_PORT=' . $sofiaAddress['port']);
        chaosInfo('detected internal Sofia SIP target ' . $sofiaAddress['host'] . ':' . $sofiaAddress['port']);
    }
}

$passed = [];
$defaultUser = chaosEnv('FREESWITCH_XML_PROJECTION_TEST_USER', '1001');

foreach ($scenarios as $name => $scenario) {
    chaosInfo('scenario ' . $name . ': ' . $scenario['description']);

    $mode = $scenario['mode'];
    $beforeCount = count(chaosReadEvents($eventsPath));

    if ($mode === 'unavailable') {
        chaosPatchXmlCurl($xmlCurlConfig, $unusedServerUrl);
        chaosFsCli($container, 'reloadxml', false);
        chaosFsCli($container, 'reload mod_xml_curl', false);
    } else {
        chaosPatchXmlCurl($xmlCurlConfig, $serverUrl);
        chaosWriteScenario($scenarioPath, $name, $mode);
        chaosFsCli($container, 'reloadxml', false);
        chaosFsCli($container, 'reload mod_xml_curl', false);
    }

    if ($name === 'unknown_user_not_found') {
        putenv('FREESWITCH_XML_PROJECTION_TEST_USER=unknown-chaos-smoke-user');
    } else {
        putenv('FREESWITCH_XML_PROJECTION_TEST_USER=' . $defaultUser);
    }

    $burst = isset($scenario['burst']) && is_int($scenario['burst']) ? $scenario['burst'] : 1;
    $probeOutput = '';
    for ($attempt = 0; $attempt < $burst; ++$attempt) {
        $probeOutput = chaosTriggerRegister($root);
    }
    chaosInfo($probeOutput);

    if (($scenario['expects_event'] ?? false) === false) {
        sleep(2);
        $afterEvents = chaosReadEvents($eventsPath);
        chaosAssert(count($afterEvents) === $beforeCount, 'Unavailable server scenario unexpectedly produced an XML curl event.');
        $passed[] = $name;
        continue;
    }

    $event = chaosWaitForScenarioEvent($eventsPath, $name, $beforeCount);
    chaosAssert($event !== null, 'No XML curl event captured for scenario ' . $name . '.');
    chaosAssertRedacted($event);

    if (($scenario['expects_directory'] ?? false) === true) {
        $fields = $event['fields'] ?? [];
        chaosAssert(is_array($fields), 'Captured fields missing for scenario ' . $name . '.');
        $request = (new XmlCurlRequestParser())->parse($fields);
        chaosAssert($request->isDirectory(), 'Parser did not recognize directory request for scenario ' . $name . '.');
        chaosAssert($request->action() === DirectoryAction::SipAuth, 'Parser did not recognize sip_auth for scenario ' . $name . '.');
        chaosAssert(($event['response']['type'] ?? null) === 'directory', 'Scenario did not return directory XML: ' . $name . '.');
    }

    if (($scenario['expects_not_found'] ?? false) === true) {
        $body = chaosHttpPost($serverUrl, [
            'section' => 'directory',
            'action' => 'sip_auth',
            'user' => 'unknown-chaos-smoke-user',
            'domain' => 'unknown-chaos-smoke-domain',
        ]);
        chaosAssert(str_contains($body, '<section name="result">'), 'Not-found body missing result section.');
        chaosAssert(str_contains($body, '<result status="not found"/>'), 'Not-found body missing not found status.');
        chaosAssert(($event['response']['type'] ?? null) === 'not-found', 'Scenario did not record not-found response.');
    }

    if (($scenario['expects_failure_response'] ?? false) === true) {
        chaosAssert(in_array($event['response']['type'] ?? null, ['slow-not-found', 'malformed_xml', 'http_500', 'oversized'], true), 'Failure scenario did not record expected response type: ' . $name . '.');
    }

    $passed[] = $name;
}

putenv('FREESWITCH_XML_PROJECTION_TEST_USER=' . $defaultUser);
chaosAssertTrackedSecretClean($root);

$cleanup();
$serverPid = null;
chaosAssert(chaosRun('git diff -- docker/freeswitch/conf/autoload_configs/xml_curl.conf.xml', $root, false) === '', 'Temporary xml_curl config was not restored.');

chaosInfo('passed scenarios: ' . implode(', ', $passed));
chaosInfo('passed.');
