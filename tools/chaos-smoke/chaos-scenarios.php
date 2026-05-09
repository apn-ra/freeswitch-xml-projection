<?php

declare(strict_types=1);

return [
    'baseline_success' => [
        'mode' => 'success',
        'description' => 'package-rendered directory XML for the configured fake user',
        'expects_event' => true,
        'expects_directory' => true,
    ],
    'unknown_user_not_found' => [
        'mode' => 'not_found',
        'description' => 'package-rendered not-found XML for a directory sip_auth request',
        'expects_event' => true,
        'expects_not_found' => true,
    ],
    'server_unavailable' => [
        'mode' => 'unavailable',
        'description' => 'FreeSWITCH points at an unused local port',
        'expects_event' => false,
    ],
    'slow_response' => [
        'mode' => 'slow',
        'description' => 'XML curl endpoint sleeps longer than the configured timeout',
        'expects_event' => true,
        'expects_failure_response' => true,
    ],
    'malformed_xml' => [
        'mode' => 'malformed_xml',
        'description' => 'XML curl endpoint returns malformed XML',
        'expects_event' => true,
        'expects_failure_response' => true,
    ],
    'http_500' => [
        'mode' => 'http_500',
        'description' => 'XML curl endpoint returns HTTP 500',
        'expects_event' => true,
        'expects_failure_response' => true,
    ],
    'oversized' => [
        'mode' => 'oversized',
        'description' => 'XML curl endpoint returns an oversized non-secret XML document',
        'expects_event' => true,
        'expects_failure_response' => true,
    ],
    'concurrent_burst' => [
        'mode' => 'success',
        'description' => 'small burst of fake REGISTER attempts against package-rendered XML',
        'expects_event' => true,
        'expects_directory' => true,
        'burst' => 6,
    ],
];
