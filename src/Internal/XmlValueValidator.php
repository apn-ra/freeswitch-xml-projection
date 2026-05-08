<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Internal;

use APNTalk\FreeSwitchXmlProjection\Exception\InvalidProjectionException;

final class XmlValueValidator
{
    private const INVALID_XML_CONTROL_CHARACTERS = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x84\x86-\x9F]/';

    private function __construct() {}

    public static function validateNonEmptyTrimmedName(string $value, string $field, int $maxLength): string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidProjectionException(sprintf('%s cannot be empty.', $field));
        }

        self::assertMaxLength($trimmed, $field, $maxLength);
        self::assertNoInvalidXmlControlCharacters($trimmed, $field);

        return $trimmed;
    }

    public static function assertMaxLength(string $value, string $field, int $maxLength): void
    {
        if (mb_strlen($value) > $maxLength) {
            throw new InvalidProjectionException(sprintf('%s cannot exceed %d characters.', $field, $maxLength));
        }
    }

    public static function assertNoInvalidXmlControlCharacters(string $value, string $field): void
    {
        if (preg_match(self::INVALID_XML_CONTROL_CHARACTERS, $value) === 1) {
            throw new InvalidProjectionException(sprintf('%s contains invalid XML control characters.', $field));
        }
    }

    public static function normalizeScalarValue(string|int|float|bool $value, string $field): string
    {
        $normalized = match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value), is_float($value) => (string) $value,
            default => $value,
        };

        self::assertNoInvalidXmlControlCharacters($normalized, $field);

        return $normalized;
    }
}
