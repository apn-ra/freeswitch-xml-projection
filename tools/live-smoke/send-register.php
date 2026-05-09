<?php

declare(strict_types=1);

/**
 * @return never
 */
function fail(string $message): void
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function envString(string $name, string $default): string
{
    $value = getenv($name);

    return $value === false || $value === '' ? $default : $value;
}

function envInt(string $name, int $default): int
{
    $value = getenv($name);

    return $value === false || $value === '' ? $default : (int) $value;
}

/**
 * @return array{response: string, local_port: int}
 */
function sendSip(string $host, int $port, string $message, int $timeoutSeconds = 3): array
{
    $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    if ($socket === false) {
        fail('Could not create UDP socket: ' . socket_strerror(socket_last_error()));
    }

    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $timeoutSeconds, 'usec' => 0]);
    socket_bind($socket, '0.0.0.0', 0);
    socket_getsockname($socket, $localAddress, $localPort);

    $sent = socket_sendto($socket, $message, strlen($message), 0, $host, $port);
    if ($sent === false) {
        fail('Could not send SIP REGISTER: ' . socket_strerror(socket_last_error($socket)));
    }

    $buffer = '';
    $from = '';
    $fromPort = 0;
    $received = socket_recvfrom($socket, $buffer, 8192, 0, $from, $fromPort);
    socket_close($socket);

    if ($received === false) {
        return ['response' => '', 'local_port' => $localPort];
    }

    return ['response' => $buffer, 'local_port' => $localPort];
}

function headerParameter(string $headerValue, string $name): ?string
{
    if (preg_match('/' . preg_quote($name, '/') . '="([^"]+)"/i', $headerValue, $matches) === 1) {
        return $matches[1];
    }

    if (preg_match('/' . preg_quote($name, '/') . '=([^,\s]+)/i', $headerValue, $matches) === 1) {
        return $matches[1];
    }

    return null;
}

function wwwAuthenticate(string $response): ?string
{
    if (preg_match('/^WWW-Authenticate:\s*(.+)$/mi', $response, $matches) === 1) {
        return trim($matches[1]);
    }

    return null;
}

function registerMessage(
    string $host,
    int $port,
    string $username,
    string $domain,
    string $callId,
    string $branch,
    int $cseq,
    int $localPort,
    ?string $authorization = null,
): string {
    $requestUri = 'sip:' . $domain;
    $contact = 'sip:' . $username . '@127.0.0.1:' . $localPort;
    $lines = [
        'REGISTER ' . $requestUri . ' SIP/2.0',
        'Via: SIP/2.0/UDP 127.0.0.1:' . $localPort . ';branch=' . $branch . ';rport',
        'Max-Forwards: 70',
        'From: <sip:' . $username . '@' . $domain . '>;tag=apntalk-live-smoke',
        'To: <sip:' . $username . '@' . $domain . '>',
        'Call-ID: ' . $callId,
        'CSeq: ' . $cseq . ' REGISTER',
        'Contact: <' . $contact . '>',
        'Expires: 60',
        'User-Agent: apntalk-freeswitch-xml-projection-live-smoke',
    ];

    if ($authorization !== null) {
        $lines[] = 'Authorization: ' . $authorization;
    }

    $lines[] = 'Content-Length: 0';
    $lines[] = '';
    $lines[] = '';

    return implode("\r\n", $lines);
}

$host = envString('FREESWITCH_XML_PROJECTION_SIP_HOST', '127.0.0.1');
$port = envInt('FREESWITCH_XML_PROJECTION_SIP_PORT', 5060);
$username = envString('FREESWITCH_XML_PROJECTION_TEST_USER', '1001');
$domain = envString('FREESWITCH_XML_PROJECTION_TEST_DOMAIN', '127.0.0.1');
$password = envString('FREESWITCH_XML_PROJECTION_TEST_PASSWORD', 'capture-password');
$callId = bin2hex(random_bytes(8)) . '@apntalk-live-smoke';
$branch = 'z9hG4bK' . bin2hex(random_bytes(6));

$first = sendSip($host, $port, registerMessage($host, $port, $username, $domain, $callId, $branch, 1, 5062));
$challenge = wwwAuthenticate($first['response']);

if ($challenge === null) {
    echo json_encode([
        'status' => 'no-challenge',
        'first_response' => substr($first['response'], 0, 160),
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
    exit(0);
}

$realm = headerParameter($challenge, 'realm') ?? $domain;
$nonce = headerParameter($challenge, 'nonce') ?? '';
$cnonce = bin2hex(random_bytes(8));
$nc = '00000001';
$qop = headerParameter($challenge, 'qop');
$uri = 'sip:' . $domain;
$ha1 = md5($username . ':' . $realm . ':' . $password);
$ha2 = md5('REGISTER:' . $uri);
$digestResponse = $qop === null
    ? md5($ha1 . ':' . $nonce . ':' . $ha2)
    : md5($ha1 . ':' . $nonce . ':' . $nc . ':' . $cnonce . ':auth:' . $ha2);

$authorization = 'Digest username="' . $username . '", realm="' . $realm . '", nonce="' . $nonce . '", uri="' . $uri . '", response="' . $digestResponse . '", algorithm=MD5';
if ($qop !== null) {
    $authorization .= ', qop=auth, nc=' . $nc . ', cnonce="' . $cnonce . '"';
}

$second = sendSip(
    $host,
    $port,
    registerMessage($host, $port, $username, $domain, $callId, 'z9hG4bK' . bin2hex(random_bytes(6)), 2, 5062, $authorization),
);

echo json_encode([
    'status' => 'sent-authenticated-register',
    'realm' => $realm,
    'first_response' => substr($first['response'], 0, 160),
    'second_response' => substr($second['response'], 0, 160),
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
