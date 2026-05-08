<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Directory;

use APNTalk\FreeSwitchXmlProjection\Exception\InvalidProjectionException;
use APNTalk\FreeSwitchXmlProjection\Internal\XmlValueValidator;

final class DirectoryUser
{
    public readonly string $id;

    public readonly ?DirectoryCredential $credential;

    /**
     * @var list<DirectoryParam>
     */
    public readonly array $params;

    /**
     * @var list<DirectoryVariable>
     */
    public readonly array $variables;

    public readonly ?string $cidr;

    public readonly bool|int|null $cacheable;

    public readonly ?string $type;

    /**
     * @param list<DirectoryParam> $params
     * @param list<DirectoryVariable> $variables
     */
    public function __construct(
        string $id,
        ?DirectoryCredential $credential = null,
        array $params = [],
        array $variables = [],
        ?string $cidr = null,
        bool|int|null $cacheable = null,
        ?string $type = null,
    ) {
        $this->id = XmlValueValidator::validateNonEmptyTrimmedName($id, 'Directory user ID', 255);
        $this->credential = $credential;
        $this->params = $params;
        $this->variables = $variables;
        $this->cidr = $cidr;
        $this->cacheable = $cacheable === false ? null : $cacheable;
        $this->type = $type === null
            ? null
            : XmlValueValidator::validateNonEmptyTrimmedName($type, 'Directory user type', 32);

        if ($this->cidr !== null) {
            XmlValueValidator::assertNoInvalidXmlControlCharacters($this->cidr, 'Directory user CIDR');
        }

        if (is_int($this->cacheable) && $this->cacheable <= 0) {
            throw new InvalidProjectionException('Directory user cacheable must be a positive integer when provided.');
        }

        if ($this->type !== null) {
            if ($this->type !== 'pointer') {
                throw new InvalidProjectionException('Directory user type must be null or "pointer".');
            }
        }
    }
}
