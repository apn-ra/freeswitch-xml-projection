<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Directory;

use APNTalk\FreeSwitchXmlProjection\Exception\InvalidProjectionException;

final readonly class DirectoryDocument
{
    /**
     * @param list<DirectoryDomain> $domains
     */
    public function __construct(public array $domains)
    {
        if ($this->domains === []) {
            throw new InvalidProjectionException('Directory documents require at least one domain.');
        }
    }
}
