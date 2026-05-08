<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Directory;

use APNTalk\FreeSwitchXmlProjection\Exception\InvalidProjectionException;
use APNTalk\FreeSwitchXmlProjection\Internal\XmlValueValidator;

final readonly class DirectoryDomain
{
    public string $name;

    /**
     * @param list<DirectoryParam> $params
     * @param list<DirectoryVariable> $variables
     * @param list<DirectoryUser> $users
     */
    public function __construct(
        string $name,
        public array $params = [],
        public array $variables = [],
        public array $users = [],
    ) {
        $this->name = XmlValueValidator::validateNonEmptyTrimmedName($name, 'Directory domain name', 255);

        if ($this->users === []) {
            throw new InvalidProjectionException('Directory domains require at least one user in v0.1.');
        }
    }
}
