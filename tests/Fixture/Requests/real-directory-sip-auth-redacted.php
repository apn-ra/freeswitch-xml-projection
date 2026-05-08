<?php

declare(strict_types=1);

/**
 * Synthetic, redacted, FreeSWITCH-like mod_xml_curl directory sip_auth request.
 *
 * TODO(v0.1.0 release blocker): Replace or confirm this fixture against a real
 * redacted capture before tagging v0.1.0.
 *
 * @return array<string, scalar|null>
 */
return [
    'section' => 'directory',
    'action' => 'sip_auth',
    'purpose' => 'id',
    'user' => '1001',
    'domain' => 'tenant-123.sip.apntalk.test',
    'tag_name' => 'domain',
    'key_name' => 'name',
    'key_value' => 'tenant-123.sip.apntalk.test',
    'Event-Name' => 'REQUEST_PARAMS',
    'Core-UUID' => '0d8f3b72-5f9d-4c7f-88eb-2d176c1963f6',
    'FreeSWITCH-Hostname' => 'fs-edge-01.apntalk.test',
    'FreeSWITCH-Switchname' => 'fs-edge-01',
    'hostname' => 'fs-edge-01.apntalk.test',
    'profile' => 'internal',
    'ip' => '198.51.100.24',
    'sip_auth_username' => '1001',
    'sip_auth_realm' => 'tenant-123.sip.apntalk.test',
    'sip_auth_method' => 'REGISTER',
    'sip_auth_response' => '[redacted]',
    'sip_auth_nonce' => '[redacted]',
    'sip_auth_cnonce' => '[redacted]',
    'sip_auth_uri' => '[redacted]',
    'sip_user_agent' => 'Synthetic SIP UA 1.0',
    'variable_sip_network_ip' => '198.51.100.24',
    'Caller-Caller-ID-Number' => '1001',
    'Caller-Destination-Number' => '1001',
    'Authorization' => '[redacted]',
    'gateway-credentials' => '[redacted]',
];
