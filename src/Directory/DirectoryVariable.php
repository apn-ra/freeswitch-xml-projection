<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Directory;

use APNTalk\FreeSwitchXmlProjection\Internal\XmlValueValidator;

final readonly class DirectoryVariable
{
    public string $name;

    public string|int|float|bool $value;

    public function __construct(string $name, string|int|float|bool $value)
    {
        $this->name = XmlValueValidator::validateNonEmptyTrimmedName($name, 'Directory variable name', 128);
        XmlValueValidator::normalizeScalarValue($value, sprintf('Directory variable "%s" value', $this->name));
        $this->value = $value;
    }
}
