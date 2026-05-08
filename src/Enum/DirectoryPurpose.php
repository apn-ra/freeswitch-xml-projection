<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Enum;

enum DirectoryPurpose: string
{
    case Gateways = 'gateways';
    case NetworkList = 'network-list';

    public static function tryFromNormalized(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        return self::tryFrom(strtolower(trim($value)));
    }
}
