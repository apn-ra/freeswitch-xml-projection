<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Enum;

enum CredentialMode: string
{
    case PlainPassword = 'password';
    case A1Hash = 'a1-hash';
}
