<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Enum;

enum XmlCurlSection: string
{
    case Directory = 'directory';
    case Dialplan = 'dialplan';
    case Configuration = 'configuration';
    case Phrases = 'phrases';

    public static function tryFromNormalized(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        return self::tryFrom(strtolower(trim($value)));
    }
}
