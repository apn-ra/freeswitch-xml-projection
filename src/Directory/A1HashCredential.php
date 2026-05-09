<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Directory;

use APNTalk\FreeSwitchXmlProjection\Enum\CredentialMode;
use APNTalk\FreeSwitchXmlProjection\Internal\XmlValueValidator;

final class A1HashCredential implements DirectoryCredential
{
    private readonly string $hash;

    public function __construct(string $hash)
    {
        $this->hash = $hash;
        XmlValueValidator::assertNoInvalidXmlControlCharacters($hash, 'A1 hash credential');
    }

    public static function fromPlainPassword(string $username, string $domain, string $password): self
    {
        return new self(md5($username . ':' . $domain . ':' . $password));
    }

    public function toParams(): array
    {
        return [new DirectoryParam('a1-hash', $this->hash)];
    }

    public function mode(): CredentialMode
    {
        return CredentialMode::A1Hash;
    }

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['hash' => '[redacted]'];
    }
}
