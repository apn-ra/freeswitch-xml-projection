<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Directory;

use APNTalk\FreeSwitchXmlProjection\Enum\CredentialMode;

abstract class DirectoryCredential
{
    /**
     * @return list<DirectoryParam>
     */
    abstract public function toParams(): array;

    abstract public function mode(): CredentialMode;
}
