<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Security;

final class Redactor
{
    public const REDACTED = '[redacted]';

    /**
     * @param array<string, scalar|null> $fields
     * @return array<string, scalar|null>
     */
    public function redact(array $fields): array
    {
        $redacted = [];

        foreach ($fields as $key => $value) {
            $redacted[$key] = SensitiveFieldList::contains($key) ? self::REDACTED : $value;
        }

        return $redacted;
    }
}
