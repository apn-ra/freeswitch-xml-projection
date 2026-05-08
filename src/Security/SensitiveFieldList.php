<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Security;

final class SensitiveFieldList
{
    /**
     * @var list<string>
     */
    private const FIELDS = [
        'password',
        'vm-password',
        'reverse-auth-pass',
        'sip_auth_response',
        'sip_auth_nonce',
        'sip_auth_cnonce',
        'sip_auth_uri',
        'authorization',
        'gateway-credentials',
        'gateway_credentials',
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return self::FIELDS;
    }

    public static function contains(string $field): bool
    {
        return in_array(strtolower($field), self::FIELDS, true);
    }
}
