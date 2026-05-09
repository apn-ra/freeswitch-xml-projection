<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Directory;

use APNTalk\FreeSwitchXmlProjection\Enum\CredentialMode;

interface DirectoryCredential
{
    /**
     * @return list<DirectoryParam>
     */
    public function toParams(): array;

    public function mode(): CredentialMode;
}
