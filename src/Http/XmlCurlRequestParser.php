<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Http;

use APNTalk\FreeSwitchXmlProjection\Exception\InvalidXmlCurlRequestException;
use APNTalk\FreeSwitchXmlProjection\Security\Redactor;

final readonly class XmlCurlRequestParser
{
    /**
     * @var list<string>
     */
    private const NORMALIZED_FIELDS = [
        'section',
        'purpose',
        'action',
        'Action',
        'user',
        'domain',
        'profile',
        'ip',
        'hostname',
        'FreeSWITCH-Hostname',
        'sip_user_agent',
        'sip_auth_username',
        'sip_auth_realm',
    ];

    public function __construct(
        private Redactor $redactor = new Redactor(),
    ) {}

    /**
     * @param array<string, mixed> $fields
     */
    public function parse(array $fields): XmlCurlRequest
    {
        $raw = [];
        $normalized = [];

        foreach ($fields as $key => $value) {
            if (is_array($value) || is_object($value) || is_resource($value)) {
                throw new InvalidXmlCurlRequestException(sprintf(
                    'The XML curl request field "%s" must be scalar or null.',
                    $key,
                ));
            }

            /** @var scalar|null $value */
            $raw[$key] = $value;

            if (in_array($key, self::NORMALIZED_FIELDS, true)) {
                $normalized[$key] = $this->normalizeLogicalField($value);
            }
        }

        return new XmlCurlRequest($raw, $this->redactor->redact($raw), $normalized);
    }

    private function normalizeLogicalField(bool|int|float|string|null $value): ?string
    {
        $stringValue = is_string($value) ? $value : (string) $value;

        return $stringValue === '' ? null : $stringValue;
    }
}
