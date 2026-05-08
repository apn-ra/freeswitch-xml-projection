<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Directory;

use APNTalk\FreeSwitchXmlProjection\Internal\XmlValueValidator;

final readonly class DirectoryParam
{
    public string $name;

    public string|int|float|bool $value;

    public function __construct(string $name, string|int|float|bool $value)
    {
        $this->name = XmlValueValidator::validateNonEmptyTrimmedName($name, 'Directory param name', 128);
        XmlValueValidator::normalizeScalarValue($value, sprintf('Directory param "%s" value', $this->name));
        $this->value = $value;
    }

    public static function dialStringDefault(): self
    {
        return new self(
            'dial-string',
            '{presence_id=${dialed_user}@${dialed_domain}}${sofia_contact(${dialed_user}@${dialed_domain})}',
        );
    }
}
