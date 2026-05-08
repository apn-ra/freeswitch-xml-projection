<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Directory;

use APNTalk\FreeSwitchXmlProjection\Enum\CredentialMode;
use APNTalk\FreeSwitchXmlProjection\Internal\XmlValueValidator;

final class PlainPasswordCredential extends DirectoryCredential
{
    private readonly string $password;

    public function __construct(string $password)
    {
        $this->password = $password;
        XmlValueValidator::assertNoInvalidXmlControlCharacters($password, 'Plain password credential');
    }

    public function toParams(): array
    {
        return [new DirectoryParam('password', $this->password)];
    }

    public function mode(): CredentialMode
    {
        return CredentialMode::PlainPassword;
    }

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['password' => '[redacted]'];
    }
}
