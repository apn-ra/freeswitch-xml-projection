<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Enum;

enum DirectoryAction: string
{
    case SipAuth = 'sip_auth';
    case ReverseAuthLookup = 'reverse-auth-lookup';
    case MessageCount = 'message-count';

    public static function tryFromNormalized(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        return self::tryFrom(strtolower(trim($value)));
    }
}
